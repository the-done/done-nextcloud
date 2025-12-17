<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Service;

use OCA\Done\Models\Dictionaries\GlobalRoles_Model;
use OCA\Done\Models\Times_Model;
use OCA\Done\Models\User_Model;
use OCA\Done\Models\UsersGlobalRoles_Model;
use OCA\Done\Modules\Projects\Models\Project_Model;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IUserSession;
use OCP\Server;

class UserService extends EntitiesService
{
    protected BaseService $baseService;
    protected IUserSession $userSession;
    protected TranslateService $translateService;

    /** @var UserService */
    private static UserService $instance;

    public function __construct(
        BaseService $baseService,
        IUserSession $userSession,
        TranslateService $translateService,
    ) {
        $this->userSession = $userSession;
        $this->translateService = $translateService;
        $this->baseService = $baseService;
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = Server::get(self::class);
        }

        return self::$instance;
    }

    /**
     * Get user time tracking statistics for specified period
     *
     * @param string        $dateFrom Start date of the period (any format accepted by DateTimeImmutable)
     * @param string        $dateTo   End date of the period (any format accepted by DateTimeImmutable)
     * @param string        $userId   User ID to get statistics for
     * @param array<string> $projects Optional array of project IDs to filter time entries
     *
     * @return array{data: array<mixed>, totals: array<string, int>, lastProjectId: string, firstReportDate: string}
     */
    public function getUserStatistics(string $dateFrom, string $dateTo, string $userId, array $projects = []): array
    {
        $dateFrom = (new \DateTimeImmutable($dateFrom))->format('Y-m-d');
        $dateTo = (new \DateTimeImmutable($dateTo))->format('Y-m-d');
        $yearFrom = (new \DateTimeImmutable($dateFrom))->format('Y');
        $yearTo = (new \DateTimeImmutable($dateTo))->format('Y');

        $timesModel = new Times_Model();
        $currentUserId = $this->getCurrentUserId();

        $totalData = $timesModel->getTimeData(
            "{$yearFrom}-01-01",
            "{$yearTo}-12-31",
            $userId,
            $projects,
            false,
            false
        );
        [$timeData, $projectsIds] = $timesModel->getTimeData($dateFrom, $dateTo, $userId, $projects);
        $lastProjectId = !empty($userId) ? $timesModel->getLastProjectId($userId) : '';
        $firstReportDate = !empty($userId) ? $timesModel->getFirstReportDate($userId) : '';
        $totals = $this->getTotalsForUserStatistics($totalData);

        $projects = (new Project_Model())->getIndexedListByFilter(
            'id',
            ['id' => ['IN', $projectsIds, IQueryBuilder::PARAM_STR_ARRAY]],
            ['name', 'id']
        );

        $period = new \DatePeriod(
            new \DateTime($dateFrom),
            new \DateInterval('P1D'),
            (new \DateTime($dateTo))->setTime(0, 0, 1)
        );

        $periodsPrepared = self::buildHierarchy($period, $currentUserId, $timeData, $projects);
        $data = BaseService::toTree($periodsPrepared);

        return [
            'data'            => $data,
            'totals'          => $totals,
            'lastProjectId'   => $lastProjectId,
            'firstReportDate' => $firstReportDate,
        ];
    }

    /**
     * Build hierarchical time period structure with time entries
     *
     * @param \DatePeriod<\DateTime, \DateTime, null|int> $period        Date period to iterate over
     * @param string                                      $currentUserId Current user ID for determining available actions
     * @param array<string, array<array<mixed>>>          $data          Time entries indexed by date (Y-m-d\TH:i:s.v\Z format)
     * @param array<string, array<mixed>>                 $projects      Projects array indexed by project ID
     *
     * @return array<array<mixed>> Hierarchical array of periods with structure:
     *                             - id: Period identifier (year/quarter/month/week/day number)
     *                             - type: Period type ('year'|'quarter'|'month'|'week'|'day')
     *                             - parent: Parent period ID
     *                             - parent_type: Parent period type
     *                             - parent_year: Year of the period
     *                             - children: Time entries (for 'day' type only)
     */
    public function buildHierarchy(
        \DatePeriod $period,
        string $currentUserId,
        array $data = [],
        array $projects = []
    ): array {
        $days = BaseService::getWeekDays();
        $periodsPrepared = $checked = $weeks = [];

        foreach ($period as $date) {
            $year = (string)$date->format('Y');
            $quarter = (string)BaseService::getQuarter($date);
            $month = (string)$date->format('m');
            $week = $date->format('o_W');
            $day = (string)$date->format('d');

            $dateFormatted = $date->format('Y-m-d\TH:i:s.v\Z');

            $yearKey = "{$year}";
            $quarterKey = "{$year}-{$quarter}";
            $monthKey = "{$year}-{$quarter}-{$month}";
            $weekKey = "{$year}-{$quarter}-{$month}-{$week}";
            $dayKey = "{$year}-{$quarter}-{$month}-{$week}-{$day}";

            $items = $data[$dateFormatted] ?? [];

            if (!empty($items)) {
                usort($items, static fn ($a, $b) => (($a['sort'] ?? 999) <=> ($b['sort'] ?? 999)));
            }

            foreach ($items as $idx => $item) {
                $availableActions = Times_Model::getAvailableActions(
                    $item['status_id'] ?? 0,
                    $item['user_id'] ?? 0,
                    $currentUserId,
                );
                $minutes = $item['minutes'] ?? 0;

                $items[$idx]['time'] = $this->baseService->getTimeView($minutes);
                $items[$idx]['pname'] = $projects[$item['project_id']]['name'] ?? '';
                $items[$idx]['available_actions'] = $availableActions;
            }

            if (!isset($checked[$yearKey])) {
                $periodsPrepared[] = [
                    'id'          => $year,
                    'type'        => 'year',
                    'parent'      => null,
                    'parent_type' => null,
                    'parent_year' => null,
                ];

                $checked[$yearKey] = $yearKey;
            }

            if (!isset($checked[$quarterKey])) {
                $periodsPrepared[] = [
                    'id'          => $quarter,
                    'type'        => 'quarter',
                    'parent'      => $year,
                    'parent_type' => 'year',
                    'parent_year' => $year,
                ];
                $checked[$quarterKey] = $quarterKey;
            }

            if (!isset($checked[$monthKey])) {
                $periodsPrepared[] = [
                    'id'          => $month,
                    'type'        => 'month',
                    'parent'      => $quarter,
                    'parent_type' => 'quarter',
                    'parent_year' => $year,
                ];
                $checked[$monthKey] = $monthKey;
            }

            if (!isset($checked[$weekKey])) {
                if ($week == '01' && $month == '12') {
                    /** @phpstan-ignore-next-line Special edge case for year boundary */
                    return [$date];
                }

                $periodsPrepared[] = [
                    'id'            => $week,
                    'type'          => 'week',
                    'parent'        => $month,
                    'parent_type'   => 'month',
                    'parent_year'   => $year,
                    'isHiddenTitle' => isset($weeks[$week]),
                ];
                $checked[$weekKey] = $weekKey;
                $weeks[$week] = $week;
            }

            if (!isset($checked[$dayKey])) {
                $periodsPrepared[] = [
                    'id'           => $day,
                    'day_name'     => $this->translateService->getTranslate($days[$date->format('w')]),
                    'type'         => 'day',
                    'parent'       => $week,
                    'parent_type'  => 'week',
                    'parent_year'  => $year,
                    'parent_month' => $month,
                    'children'     => $items,
                ];
            }

            $checked[$dayKey] = $dayKey;
        }

        return $periodsPrepared;
    }

    public function getUserGlobalRoles(string $currentUserId): array
    {
        return (new UsersGlobalRoles_Model())->getUserRoles($currentUserId);
    }

    /**
     * Get current user ID in Done
     *
     * @return string
     */
    public function getCurrentUserId(): string
    {
        $currentUserObj = $this->userSession->getUser();

        if (!$currentUserObj) {
            return '';
        }

        $currentUserUid = $currentUserObj->getUID();
        $currentUser = (new User_Model())->getUserByUuid($currentUserUid);

        return $currentUser['id'] ?? '';
    }

    /**
     * Get user statistics totals
     *
     * @param array<array<mixed>> $totalData Time entries data with date and minutes
     *
     * @return array<string, int> Totals indexed by period key (year, quarter, month, week, day)
     */
    public function getTotalsForUserStatistics(
        array $totalData = []
    ): array {
        $totals = [];

        foreach ($totalData as $item) {
            $date = new \DateTime($item['date']);
            $minutes = $item['minutes'] ?? 0;

            $year = $date->format('Y');
            $quarter = (string)BaseService::getQuarter($date);
            $month = $date->format('m');
            $week = $date->format('o_W');
            $day = $date->format('d');

            $yearKey = "{$year}";
            $quarterKey = "{$year}-{$quarter}";
            $monthKey = "{$year}-{$quarter}-{$month}";
            $weekKey = "{$year}-week-{$week}";
            $dayKey = "{$year}-{$quarter}-{$month}-{$week}-{$day}";

            $totals[$yearKey] ??= 0;
            $totals[$quarterKey] ??= 0;
            $totals[$monthKey] ??= 0;
            $totals[$weekKey] ??= 0;
            $totals[$dayKey] ??= 0;

            $totals[$yearKey] += $minutes;
            $totals[$quarterKey] += $minutes;
            $totals[$monthKey] += $minutes;
            $totals[$weekKey] += $minutes;
            $totals[$dayKey] += $minutes;
        }

        return $totals;
    }

    /**
     * Check if user has required roles
     *
     * @param array<string> $requirementRoles Array of role names to check
     *
     * @return bool True if user has at least one of the required roles
     */
    public function can(array $requirementRoles = []): bool
    {
        $currentUserId = $this->getCurrentUserId();

        if (empty($currentUserId)) {
            return false;
        }

        $globalRoles = $this->getUserGlobalRoles($currentUserId);

        return !empty(array_intersect($requirementRoles, $globalRoles));
    }

    /**
     * Check if user has required permission
     *
     * @param string $action
     *
     * @return bool
     */
    public function canDoAction(string $action = ''): bool
    {
        $availableActions = $this->getActionsAvailableToUser();

        return $availableActions[$action] ?? false;
    }

    /**
     * Get the actions available to the user
     *
     * @return array
     */
    public function getActionsAvailableToUser(): array
    {
        $currentUserId = $this->getCurrentUserId();
        $defaultRights = GlobalRoles_Model::getUsersDefaultRights();

        return $currentUserId
            ? (new UsersGlobalRoles_Model())->getRights($this->getUserGlobalRoles($currentUserId))
            : $defaultRights;
    }
}
