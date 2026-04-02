<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Reports\Service;

use OCA\Done\Models\TimesModel;
use OCA\Done\Models\UserModel;
use OCA\Done\Models\UsersRolesInProjectsModel;
use OCA\Done\Models\UsersToDirectionsModel;
use OCA\Done\Modules\BaseModuleService;
use OCA\Done\Modules\Projects\Models\ProjectModel;
use OCA\Done\Modules\Teams\Models\EmployeesToTeamsModel;
use OCA\Done\Service\BaseService;
use OCA\Done\Service\TranslateService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Server;

class ReportsService
{
    /** @var BaseService */
    private BaseService $baseService;

    /** @var TranslateService */
    private TranslateService $translateService;

    /** @var ReportsService */
    private static ReportsService $instance;

    public function __construct()
    {
        $this->baseService = BaseService::getInstance();
        $this->translateService = TranslateService::getInstance();
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = Server::get(self::class);
        }

        return self::$instance;
    }

    /**
     * Get common report data
     *
     * @param array<string, mixed> $filter
     *
     * @return array
     */
    public function getCommonReportData(array $filter = []): array
    {
        $timesModel = new TimesModel();

        $result = [];
        $dataLinked = $timesModel->getLinkedList($filter, ['*'], false, true);
        $data = $timesModel->getListByFilter($filter);
        $projects = BaseService::getField($data, 'project_id', true);
        $roles = (new UsersRolesInProjectsModel())->getUsersRolesInProjects($projects);
        $usersIds = BaseService::getField($data, 'user_id', true);
        $users = (new UserModel())->getIndexedListByFilter(
            'id',
            ['id' => ['IN', $usersIds, IQueryBuilder::PARAM_STR_ARRAY]],
            ['id', 'name', 'user_display_name', 'deleted'],
            [],
            [],
            true
        );

        foreach ($data as $idx => $item) {
            $linkedItem = $dataLinked[$idx] ?? [];
            $projectId = $item['project_id'];
            $userId = $item['user_id'];
            $userSlug = $users[$userId]['slug'] ?? $userId ?? '';
            $minutes = (int)$item['minutes'];

            $result[$projectId]['users'] ??= [];
            $result[$projectId]['project_title'] ??= $linkedItem['project_id'] ?? '';
            $result[$projectId]['project_id'] ??= $projectId ?? '';
            $result[$projectId]['total'] ??= 0;
            $result[$projectId]['total'] += $minutes;
            $result[$projectId]['users'][$userId]['user_name'] ??= $linkedItem['user_id'] ?? '';
            $result[$projectId]['users'][$userId]['user_id'] ??= $item['user_id'] ?? '';
            $result[$projectId]['users'][$userId]['user_slug'] ??= $userSlug;
            $result[$projectId]['users'][$userId]['user_roles_in_project'] ??= $roles[$projectId][$userId] ?? [];

            $result[$projectId]['users'][$userId]['total'] ??= 0;
            $result[$projectId]['users'][$userId]['total'] += $minutes;
        }

        usort($result, static fn ($a, $b) => strnatcmp($a['project_title'], $b['project_title']));

        foreach ($result as $idx => $projectData) {
            usort($projectData['users'], static fn ($a, $b) => strnatcmp($a['user_name'], $b['user_name']));
            $projectData['users'] = array_map(function ($item) {
                $item['total'] = $this->baseService->getTimeView($item['total']);

                return $item;
            }, $projectData['users']);
            $projectData['total'] = $this->baseService->getTimeView($projectData['total']);
            $result[$idx] = $projectData;
        }

        return $result;
    }

    public function getProjectsStatistics(
        \DateTimeImmutable $dateFrom,
        \DateTimeImmutable $dateTo,
        ?string $projectId = null,
        ?string $userId = null,
        array $systemFilter = [],
    ): array {
        $checked = $periodsPrepared = $totals = $weeks = [];
        $timesModel = new TimesModel();
        $projectModel = new ProjectModel();

        $dateFromFormatted = $dateFrom->format('Y-m-d');
        $dateToFormatted = $dateTo->format('Y-m-d');
        $yearFrom = $dateFrom->format('Y');
        $yearTo = $dateTo->format('Y');
        $totalDateFrom = "{$yearFrom}-01-01";
        $totalDateTo = "{$yearTo}-12-31";

        $filter = [
            'date' => ['BETWEEN', [$dateFromFormatted, $dateToFormatted]],
        ];

        $totalsFilter = [
            'date' => ['BETWEEN', [$totalDateFrom, $totalDateTo]],
        ];

        if ($projectId) {
            $filter['project_id'] = $totalsFilter['project_id'] = $projectId;
        }

        if ($userId) {
            $filter['user_id'] = $totalsFilter['user_id'] = $userId;
        }

        if (!empty($systemFilter) && !isset($filter['project_id'])) {
            $filter = array_merge($filter, $systemFilter);
            $totalsFilter = array_merge($totalsFilter, $systemFilter);
        }

        $days = BaseService::getWeekDays();

        $period = new \DatePeriod(
            $dateFrom,
            new \DateInterval('P1D'),
            $dateTo->setTime(0, 0, 1)
        );

        [$data, $projectsIds] = $this->getProjectsReportData($timesModel, $filter);
        $projectDeletedMessage = $this->translateService->getTranslate('Project deleted');
        $totalData = $timesModel->getListByFilter($totalsFilter);
        $projects = $projectModel->getListByFilter(
            ['id' => ['IN', array_keys($projectsIds), IQueryBuilder::PARAM_STR_ARRAY]],
            ['*'],
            [],
            [],
            true
        );

        // Calculate common statistics for selected period
        foreach ($totalData as $item) {
            $projectItemId = $item['project_id'];
            $userId = $item['user_id'];
            $date = new \DateTime($item['date']);
            $minutes = $item['minutes'] ?? 0;

            $year = $date->format('Y');
            $quarter = (string)BaseService::getQuarter($date);
            $month = $date->format('m');
            $week = $date->format('o_W');
            $day = $date->format('d');

            $projectKey = "{$projectItemId}";
            $yearKey = "{$projectItemId}-{$year}";
            $quarterKey = "{$projectItemId}-{$year}-{$quarter}";
            $monthKey = "{$projectItemId}-{$year}-{$quarter}-{$month}";
            $weekKey = "{$projectItemId}-{$year}-week-{$week}";
            $dayKey = "{$projectItemId}-{$year}-{$quarter}-{$month}-{$week}-{$day}";
            $userKey = "{$projectItemId}-{$year}-{$quarter}-{$month}-{$week}-{$day}-{$userId}";

            $totals[$projectKey] ??= 0;
            $totals[$yearKey] ??= 0;
            $totals[$quarterKey] ??= 0;
            $totals[$monthKey] ??= 0;
            $totals[$weekKey] ??= 0;
            $totals[$dayKey] ??= 0;
            $totals[$userKey] ??= 0;

            $totals[$projectKey] += $minutes;
            $totals[$yearKey] += $minutes;
            $totals[$quarterKey] += $minutes;
            $totals[$monthKey] += $minutes;
            $totals[$weekKey] += $minutes;
            $totals[$dayKey] += $minutes;
            $totals[$userKey] += $minutes;
        }

        // Build hierarchy
        foreach ($projects as $project) {
            $projectItemId = $project['id'];
            $deleted = (bool)$project['deleted'];
            $projectName = $project['name'] ?? '-';
            $projectName = $deleted ? "{$projectName} ({$projectDeletedMessage})" : $projectName;

            foreach ($period as $date) {
                $year = (string)$date->format('Y');
                $quarter = (string)BaseService::getQuarter($date);
                $month = (string)$date->format('m');
                $week = $date->format('o_W');
                $day = (string)$date->format('d');

                $dateFormatted = $date->format('Y-m-d\TH:i:s.v\Z');

                $projectKey = "{$projectItemId}";
                $yearKey = "{$projectItemId}-{$year}";
                $quarterKey = "{$projectItemId}-{$year}-{$quarter}";
                $monthKey = "{$projectItemId}-{$year}-{$quarter}-{$month}";
                $weekKey = "{$projectItemId}-{$year}-{$quarter}-{$month}-{$week}";
                $dayKey = "{$projectItemId}-{$year}-{$quarter}-{$month}-{$week}-{$day}";

                $items = $data["{$projectItemId}-{$dateFormatted}"] ?? [];

                if (!isset($checked[$projectKey])) {
                    $periodsPrepared[] = [
                        'id'             => $projectKey,
                        'type'           => 'project',
                        'parent'         => null,
                        'parent_type'    => null,
                        'parent_project' => null,
                        'project_title'  => $projectName,
                    ];

                    $checked[$projectKey] = $projectKey;
                }

                if (!isset($checked[$yearKey])) {
                    $periodsPrepared[] = [
                        'id'             => $year,
                        'type'           => 'year',
                        'parent'         => $projectKey,
                        'parent_type'    => 'project',
                        'parent_project' => $projectItemId,
                    ];

                    $checked[$yearKey] = $yearKey;
                }

                if (!isset($checked[$quarterKey])) {
                    $periodsPrepared[] = [
                        'id'             => $quarter,
                        'type'           => 'quarter',
                        'parent'         => $year,
                        'parent_type'    => 'year',
                        'parent_project' => $projectItemId,
                    ];
                    $checked[$quarterKey] = $quarterKey;
                }

                if (!isset($checked[$monthKey])) {
                    $periodsPrepared[] = [
                        'id'             => $month,
                        'type'           => 'month',
                        'parent'         => $quarter,
                        'parent_type'    => 'quarter',
                        'parent_project' => $projectItemId,
                    ];
                    $checked[$monthKey] = $monthKey;
                }

                if (!isset($checked[$weekKey])) {
                    if ($week == '01' && $month == '12') {
                        return [$date];
                    }

                    $periodsPrepared[] = [
                        'id'             => $week,
                        'type'           => 'week',
                        'parent'         => $month,
                        'parent_type'    => 'month',
                        'parent_project' => $projectItemId,
                        'isHiddenTitle'  => isset($weeks["{$week}-{$projectItemId}"]),
                    ];
                    $checked[$weekKey] = $weekKey;
                    $weeks["{$week}-{$projectItemId}"] = "{$week}-{$projectItemId}";
                }

                if (!isset($checked[$dayKey])) {
                    $periodsPrepared[] = [
                        'id'             => $day,
                        'day_name'       => $this->translateService->getTranslate($days[$date->format('w')]),
                        'type'           => 'day',
                        'parent'         => $week,
                        'parent_type'    => 'week',
                        'parent_project' => $projectItemId,
                        'parent_month'   => $month,
                        'children'       => $items,
                    ];
                }

                $checked[$dayKey] = $dayKey;
            }
        }

        $result = BaseService::toTree(
            $periodsPrepared,
            null,
            null,
            null,
            null,
            'children',
            'project'
        );

        foreach ($totals as $key => $value) {
            $value = $value ?: 0;

            $totals[$key] = $this->baseService->getTimeView($value);
        }

        return ['data' => $result, 'totals' => $totals];
    }

    /**
     * Get projects report data
     *
     * @param array<string, mixed> $filter
     *
     * @return array
     */
    public function getProjectsReportData(TimesModel $timesModel, array $filter = []): array
    {
        $result = $projectIds = [];
        $dataLinked = $timesModel->getLinkedList($filter, ['*'], false, true);
        $data = $timesModel->getListByFilter($filter);
        $projects = BaseService::getField($data, 'project_id', true);
        $roles = (new UsersRolesInProjectsModel())->getUsersRolesInProjects($projects);
        $usersIds = BaseService::getField($data, 'user_id', true);
        $users = (new UserModel())->getIndexedListByFilter(
            'id',
            ['id' => ['IN', $usersIds, IQueryBuilder::PARAM_STR_ARRAY]],
            ['id', 'name', 'user_display_name']
        );

        foreach ($data as $idx => $item) {
            $itemLinked = $dataLinked[$idx];
            $itemLinked['time'] = $this->baseService->getTimeView($itemLinked['minutes'] ?? 0);
            $date = $item['date'];
            $projectId = $item['project_id'];
            $userId = $item['user_id'];
            $userSlug = $users[$userId]['slug'] ?? $userId ?? '';
            $projectDateKey = "{$projectId}-{$date}";

            $result[$projectDateKey][$userId]['user_name'] ??= $itemLinked['user_id'];
            $result[$projectDateKey][$userId]['user_slug'] ??= $userSlug;
            $result[$projectDateKey][$userId]['user_roles_in_project'] ??= $roles[$projectId][$userId] ?? [];
            $result[$projectDateKey][$userId]['user_id'] ??= $userId ?? '';
            $result[$projectDateKey][$userId]['reports'] ??= [];
            $result[$projectDateKey][$userId]['reports'][] = $itemLinked;
            $projectIds[$projectId] = $projectId;
        }

        foreach ($result as $key => $items) {
            usort($items, static fn ($a, $b) => strnatcmp($a['user_name'], $b['user_name']));
            $result[$key] = $items;
        }

        return [$result, $projectIds];
    }

    /**
     * Get users and their projects data
     *
     * @param array<string, mixed> $filter
     *
     * @return array
     */
    public function getUsersCommonReportData(array $filter = []): array
    {
        $result = [];
        $timesModel = new TimesModel();
        $dataLinked = $timesModel->getLinkedList($filter, ['*'], false, true);
        $data = $timesModel->getListByFilter($filter);
        $projects = BaseService::getField($data, 'project_id', true);
        $roles = (new UsersRolesInProjectsModel())->getUsersRolesInProjects($projects);
        $usersIds = BaseService::getField($data, 'user_id', true);
        $users = (new UserModel())->getIndexedListByFilter(
            'id',
            ['id' => ['IN', $usersIds, IQueryBuilder::PARAM_STR_ARRAY]],
            ['id', 'name', 'user_display_name']
        );

        foreach ($data as $idx => $item) {
            $linkedItem = $dataLinked[$idx] ?? [];
            $projectId = $item['project_id'];
            $reportId = $item['id'];
            $userId = $item['user_id'];
            $userSlug = $users[$userId]['slug'] ?? $userId ?? '';
            $minutes = (int)$item['minutes'];
            $userRolesInProject = $roles[$projectId][$userId] ?? [];

            $result[$userId]['user_name'] ??= $linkedItem['user_id'] ?? '';
            $result[$userId]['user_slug'] ??= $userSlug;
            $result[$userId]['total'] ??= 0;
            $result[$userId]['total'] += $minutes;
            $result[$userId]['projects'] ??= [];

            if (!isset($result[$userId]['projects'][$projectId])) {
                $result[$userId]['projects'][$projectId] = [
                    'project_title'         => $linkedItem['project_id'] ?? '',
                    'project_id'            => $projectId,
                    'user_roles_in_project' => $userRolesInProject,
                    'reports'               => [],
                    'total'                 => 0,
                ];
            }

            if (!isset($result[$userId]['projects'][$projectId]['reports'][$reportId])) {
                $result[$userId]['projects'][$projectId]['reports'][$reportId] = [
                    'description' => $item['description'],
                    'comment'     => $item['comment'],
                    'is_downtime' => $item['is_downtime'],
                    'time'        => $minutes,
                    'report_id'   => $reportId,
                ];
            }

            $result[$userId]['projects'][$projectId]['total'] += $minutes;
        }

        usort($result, static fn ($a, $b) => strnatcmp($a['user_name'], $b['user_name']));

        foreach ($result as $idx => $userData) {
            usort($userData['projects'], static fn ($a, $b) => strnatcmp($a['project_title'], $b['project_title']));
            $userData['projects'] = array_map(function ($item) {
                $item['total'] = $this->baseService->getTimeView($item['total']);

                return $item;
            }, $userData['projects']);

            foreach ($userData['projects'] as $key => $projectData) {
                $projectData['reports'] = array_map(function ($item) {
                    $item['time'] = $this->baseService->getTimeView($item['time']);

                    return $item;
                }, $projectData['reports']);
                $projectData['reports'] = array_values($projectData['reports']);
                $userData['projects'][$key] = $projectData;
            }

            $userData['total'] = $this->baseService->getTimeView($userData['total']);
            $result[$idx] = $userData;
        }

        return $result;
    }

    public static function prepareFilterForUsersCommonStatistics(
        string $dateFrom,
        string $dateTo,
        array $projects,
        array $teams,
        array $contractTypes,
        array $directions,
    ): array {
        $intersectArrays = [];

        $filter = [
            'date' => [
                'BETWEEN',
                [
                    (new \DateTimeImmutable($dateFrom))->format('Y-m-d'),
                    (new \DateTimeImmutable($dateTo))->format('Y-m-d'),
                ],
            ],
        ];

        if (!empty($projects)) {
            $filter['project_id'] = ['IN', $projects, IQueryBuilder::PARAM_STR_ARRAY];
        }

        if (!empty($teams) && BaseModuleService::moduleExists('teams')) {
            $intersectArrays[] = (new EmployeesToTeamsModel())->getEmployeesByTeams($teams);
        }

        if (!empty($contractTypes)) {
            $intersectArrays[] = (new UserModel())->getEmployeesByContractTypes($contractTypes);
        }

        if (!empty($directions)) {
            $intersectArrays[] = (new UsersToDirectionsModel())->getEmployeesByDirections($directions);
        }

        if (!empty($intersectArrays)) {
            $uniqueUsers = array_values(array_unique(array_intersect(...$intersectArrays)));
            $filter['user_id'] = ['IN', $uniqueUsers, IQueryBuilder::PARAM_STR_ARRAY];
        }

        return $filter;
    }
}
