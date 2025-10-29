<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


declare(strict_types=1);

namespace OCA\Done\Service;

use OCA\Done\Modules\Projects\Models\Project_Model;
use OCA\Done\Models\Times_Model;
use OCA\Done\Models\User_Model;
use OCA\Done\Models\UsersGlobalRoles_Model;
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
        $this->userSession      = $userSession;
        $this->translateService = $translateService;
        $this->baseService      = $baseService;
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = Server::get(UserService::class);
        }

        return self::$instance;
    }

    public function getUserStatistics(string $dateFrom, string $dateTo, string $userId, array $projects = []): array
    {
        $dateFrom      = (new \DateTimeImmutable($dateFrom))->format('Y-m-d');
        $dateTo        = (new \DateTimeImmutable($dateTo))->format('Y-m-d');
        $timesModel    = new Times_Model();
        $currentUserId = $this->getCurrentUserId();
        $days          = BaseService::getWeekDays();

        $periodsPrepared = $checked = $weeks = [];

        $totals = $this->getTotalsForUserStatistics($dateFrom, $dateTo, $userId, $projects);
        [$data, $projectsIds] = $timesModel->getTimeData($dateFrom, $dateTo, $userId, $projects);
        $lastProjectId = !empty($userId) ? $timesModel->getLastProjectId($userId) : '';

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

        // Build hierarchy
        foreach ($period as $date) {
            $year    = (string)$date->format('Y');
            $quarter = (string)BaseService::getQuarter($date);
            $month   = (string)$date->format('m');
            $week    = $date->format('o_W');
            $day     = (string)$date->format('d');

            $dateFormatted = $date->format('Y-m-d\TH:i:s.v\Z');

            $yearKey    = "{$year}";
            $quarterKey = "{$year}-{$quarter}";
            $monthKey   = "{$year}-{$quarter}-{$month}";
            $weekKey    = "{$year}-{$quarter}-{$month}-{$week}";
            $dayKey     = "{$year}-{$quarter}-{$month}-{$week}-{$day}";

            $items = $data[$dateFormatted] ?? [];

            if (!empty($items)) {
                usort($items, fn($a, $b) => (($a['sort'] ?? 999) <=> ($b['sort'] ?? 999)));
            }

            foreach ($items as $idx => $item) {
                $availableActions = $timesModel->getAvailableActions(
                    $item['status_id'] ?? 0,
                    $item['user_id'] ?? 0,
                    $currentUserId,
                );
                $minutes          = $item['minutes'] ?? 0;

                $items[$idx]['time']              = $this->baseService->getTimeView($minutes);
                $items[$idx]['pname']             = $projects[$item['project_id']]['name'] ?? '';
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
                $periodsPrepared[]    = [
                    'id'          => $quarter,
                    'type'        => 'quarter',
                    'parent'      => $year,
                    'parent_type' => 'year',
                    'parent_year' => $year,
                ];
                $checked[$quarterKey] = $quarterKey;
            }

            if (!isset($checked[$monthKey])) {
                $periodsPrepared[]  = [
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
                $weeks[$week]      = $week;
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

        $result = BaseService::toTree($periodsPrepared);

        return ['data' => $result, 'totals' => $totals, 'lastProjectId' => $lastProjectId];
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
        $currentUser    = (new User_Model())->getUserByUuid($currentUserUid);

        return $currentUser['id'] ?? '';
    }

    /**
     * Get user statistics totals
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $userId
     * @param string[] $projects
     *
     * @return array
     */
    public function getTotalsForUserStatistics(
        string $dateFrom,
        string $dateTo,
        string $userId,
        array $projects = []
    ): array {
        $totals = [];

        $yearFrom = (new \DateTimeImmutable($dateFrom))->format('Y');
        $yearTo   = (new \DateTimeImmutable($dateTo))->format('Y');

        $totalData = (new Times_Model())->getTimeData(
            "{$yearFrom}-01-01",
            "{$yearTo}-12-31",
            $userId,
            $projects,
            false,
            false
        );

        foreach ($totalData as $item) {
            $date    = new \DateTime($item['date']);
            $minutes = $item['minutes'] ?? 0;

            $year    = $date->format('Y');
            $quarter = (string)BaseService::getQuarter($date);
            $month   = $date->format('m');
            $week    = $date->format('o_W');
            $day     = $date->format('d');

            $yearKey    = "{$year}";
            $quarterKey = "{$year}-{$quarter}";
            $monthKey   = "{$year}-{$quarter}-{$month}";
            $weekKey    = "{$year}-week-{$week}";
            $dayKey     = "{$year}-{$quarter}-{$month}-{$week}-{$day}";

            $totals[$yearKey]    ??= 0;
            $totals[$quarterKey] ??= 0;
            $totals[$monthKey]   ??= 0;
            $totals[$weekKey]    ??= 0;
            $totals[$dayKey]     ??= 0;

            $totals[$yearKey]    += $minutes;
            $totals[$quarterKey] += $minutes;
            $totals[$monthKey]   += $minutes;
            $totals[$weekKey]    += $minutes;
            $totals[$dayKey]     += $minutes;
        }

        return $totals;
    }

    /**
     * Check if user has required roles
     *
     * @param array $requirementRoles
     *
     * @return bool
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
}
