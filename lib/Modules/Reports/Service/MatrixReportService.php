<?php

/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Modules\Reports\Service;

use OCA\Done\Models\BaseModel;
use OCA\Done\Models\TimesModel;
use OCA\Done\Models\UserModel;
use OCA\Done\Modules\Projects\Models\ProjectModel;
use OCA\Done\Service\TranslateService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Server;

/** Time allocation matrix. Historical hours retain deleted employee/project names. */
class MatrixReportService
{
    /** Every time entry except soft-deleted reports. */
    private const REPORTABLE = ['OR', [['!=', TimesModel::DELETED], ['IS NULL']]];

    private const PAGE_SIZE = 100;

    private static ?self $instance = null;

    private TranslateService $translateService;
    private TimesModel $timesModel;
    private UserModel $userModel;
    private ProjectModel $projectModel;

    public function __construct(
        ?TranslateService $translateService = null,
        ?TimesModel $timesModel = null,
        ?UserModel $userModel = null,
        ?ProjectModel $projectModel = null,
    ) {
        $this->translateService = $translateService ?? TranslateService::getInstance();
        $this->timesModel = $timesModel ?? new TimesModel();
        $this->userModel = $userModel ?? new UserModel();
        $this->projectModel = $projectModel ?? new ProjectModel();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = Server::get(self::class);
        }

        return self::$instance;
    }

    /**
     * null means unrestricted; an empty selection deliberately matches nothing.
     *
     * @param mixed $value
     */
    public static function selection($value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (\is_string($value)) {
            $value = $value === '' ? [] : explode(',', $value);
        }

        if (!\is_array($value) || !array_is_list($value) || \count($value) > 1000) {
            throw new \InvalidArgumentException('Invalid filter selection');
        }

        foreach ($value as $id) {
            if (!\is_string($id) || !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $id)) {
                throw new \InvalidArgumentException('Invalid filter ID');
            }
        }

        return array_values(array_unique($value));
    }

    public function filterOptions(): array
    {
        $projects = array_map(
            static fn (array $project) => [
                'id'      => $project['id'],
                'name'    => $project['name'],
                'deleted' => self::intOrNull($project['deleted']),
            ],
            $this->withReportableHours($this->projectModel, 'project_id', ['id', 'name', 'deleted'])
        );

        foreach ($projects as &$project) {
            $project['name'] = ($project['name'] ?: $this->translateService->getTranslate('Unknown project'))
                . (!empty($project['deleted']) ? ' (' . $this->translateService->getTranslate('Deleted') . ')' : '');
        }
        unset($project);
        usort($projects, static fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));
        $users = array_map(
            static fn (array $user) => [
                'id'           => $user['id'],
                'name'         => $user['name'],
                'deleted'      => self::intOrNull($user['deleted']),
                'last_name'    => $user['lastname'],
                'first_name'   => $user['name'],
                'display_name' => $user['user_display_name'],
            ],
            $this->withReportableHours($this->userModel, 'user_id', ['id', 'name', 'lastname', 'user_display_name', 'deleted'])
        );

        foreach ($users as &$user) {
            $user['name'] = $this->name($user)
                . (!empty($user['deleted']) ? ' (' . $this->translateService->getTranslate('Deleted') . ')' : '');
            $user['sort_name'] = trim($user['last_name'] . ' ' . $user['first_name']) ?: $user['name'];
        }
        unset($user);
        usort($users, static fn ($a, $b) => strnatcasecmp($a['sort_name'], $b['sort_name']));

        return ['project_ids' => $projects, 'user_ids' => array_map(
            static fn ($user) => ['id' => $user['id'], 'name' => $user['name']],
            $users
        )];
    }

    private function name(array $row): string
    {
        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

        return $name ?: ($row['display_name'] ?? '') ?: $this->translateService->getTranslate('Unknown employee');
    }

    private function profileSlugs(array &$row): void
    {
        $row['user_slug'] = empty($row['profile_user_id']) ? null
            : (($row['display_name'] ?? '') ?: $row['profile_user_id']);
        $row['project_slug'] = $row['profile_project_id'] ?? null;
        unset($row['profile_user_id'], $row['profile_project_id']);
    }

    public function matrix(
        string $date,
        string $period,
        bool $byDates,
        string $mode,
        ?array $projects = null,
        ?array $users = null
    ): array {
        $calendar = MatrixReportBuilder::period($date, $period, $byDates);
        $daily = $this->daily($calendar['start'], $calendar['end'], $projects, $users);

        foreach ($daily as &$row) {
            $this->profileSlugs($row);
            $row['user_name'] = $this->name($row);
            $row['user_sort_name'] = trim(($row['last_name'] ?? '') . ' ' . ($row['first_name'] ?? ''))
                ?: $row['user_name'];
            $row['project_name'] = $row['project_name'] ?: $this->translateService->getTranslate('Unknown project');
        }
        unset($row);
        $matrix = MatrixReportBuilder::build($calendar, $mode, $daily);

        $matrix['company']['name'] = $this->translateService->getTranslate('Company total');
        $matrix += $this->neighbors($calendar['start'], $calendar['end'], $projects, $users);

        return $matrix;
    }

    public function entries(
        string $start,
        string $end,
        string $user,
        string $project,
        int $offset,
        ?array $projects = null,
        ?array $users = null
    ): array {
        $first = MatrixReportBuilder::date($start);
        $last = MatrixReportBuilder::date($end);

        if ($first > $last || $first->diff($last)->days > 366 || $offset < 0) {
            throw new \InvalidArgumentException('Invalid detail range');
        }

        foreach ([$user, $project] as $id) {
            if ($id !== '' && !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $id)) {
                throw new \InvalidArgumentException('Invalid record ID');
            }
        }
        $result = $this->entryPage($start, $end, $user, $project, $offset, $projects, $users);
        $entries = $result['entries'];

        foreach ($entries as &$row) {
            $this->profileSlugs($row);
            $row['user_name'] = $this->name($row);
            $row['minutes'] = (int)$row['minutes'];
            $row['project_name'] = $row['project_name'] ?: $this->translateService->getTranslate('Unknown project');

            if (!preg_match('~^https?://~i', $row['task_link'] ?? '')) {
                $row['task_link'] = '';
            }
        }

        $result['entries'] = $entries;

        return $result;
    }

    /**
     * Filter for reportable time entries; null when the selection can match nothing.
     *
     * @param array<string, mixed> $filter  Extra conditions (e.g. the date range)
     * @param string               $user    Exact employee id of a clicked cell, '' for any
     * @param string               $project Exact project id of a clicked cell, '' for any
     */
    private function timesFilter(
        ?array $projects,
        ?array $users,
        array $filter = [],
        string $user = '',
        string $project = ''
    ): ?array {
        foreach (['project_id' => [$projects, $project], 'user_id' => [$users, $user]] as $field => [$selection, $id]) {
            if ($id !== '') {
                if ($selection !== null && !\in_array($id, $selection, true)) {
                    return null;
                }
                $filter[$field] = ['=', $id];
            } elseif ($selection !== null) {
                if ($selection === []) {
                    return null;
                }
                $filter[$field] = ['IN', $selection, IQueryBuilder::PARAM_STR_ARRAY];
            }
        }
        $filter['status_id'] = self::REPORTABLE;

        return $filter;
    }

    /**
     * Minutes per date, employee and project, with the employee/project name columns.
     */
    private function daily(string $start, string $end, ?array $projects, ?array $users): array
    {
        $filter = $this->timesFilter($projects, $users, ['date' => ['BETWEEN', [$start, $end]]]);

        if ($filter === null) {
            return [];
        }
        $sums = [];

        foreach ($this->timesModel->getListByFilter($filter, ['date', 'user_id', 'project_id', 'minutes']) as $time) {
            $date = self::day($time['date']);
            $key = $date . '|' . $time['user_id'] . '|' . $time['project_id'];
            $sums[$key] ??= ['date' => $date, 'user_id' => $time['user_id'], 'project_id' => $time['project_id'], 'minutes' => 0];
            $sums[$key]['minutes'] += (int)$time['minutes'];
        }
        $sums = array_values($sums);
        $names = $this->nameColumns($sums);

        return array_map(
            static fn (array $sum, array $name) => [
                'date'       => $sum['date'],
                'user_id'    => $sum['user_id'],
                'project_id' => $sum['project_id'],
            ] + $name + ['minutes' => $sum['minutes']],
            $sums,
            $names
        );
    }

    /**
     * Closest dates with data before and after the period.
     */
    private function neighbors(string $start, string $end, ?array $projects, ?array $users): array
    {
        $neighbors = [];

        foreach (['previous' => ['<', $start, 'DESC'], 'next' => ['>', $end, 'ASC']] as $key => [$operator, $date, $direction]) {
            $filter = $this->timesFilter($projects, $users, ['date' => [$operator, $date]]);
            $time = $filter === null ? null
                : ($this->timesModel->getPagedListByFilter($filter, ['date'], [['date', $direction]], 1)[0] ?? null);
            $neighbors[$key] = $time === null ? null : self::day($time['date']);
        }

        return $neighbors;
    }

    /**
     * One page of a cell's time entries plus the cell's entry count and minutes.
     */
    private function entryPage(
        string $start,
        string $end,
        string $user,
        string $project,
        int $offset,
        ?array $projects,
        ?array $users
    ): array {
        $filter = $this->timesFilter($projects, $users, ['date' => ['BETWEEN', [$start, $end]]], $user, $project);

        if ($filter === null) {
            return ['entries' => [], 'count' => 0, 'minutes' => 0];
        }
        $all = $this->timesModel->getListByFilter($filter, ['minutes']);
        $page = $this->timesModel->getPagedListByFilter(
            $filter,
            ['id', 'date', 'description', 'comment', 'task_link', 'minutes', 'is_downtime', 'user_id', 'project_id'],
            [['date', 'ASC'], ['user_id', 'ASC'], ['sort', 'ASC'], ['id', 'ASC']],
            self::PAGE_SIZE,
            $offset
        );
        $names = $this->nameColumns($page);
        $entries = array_map(
            static fn (array $time, array $name) => [
                'id'          => $time['id'],
                'date'        => self::day($time['date']),
                'description' => $time['description'],
                'comment'     => $time['comment'],
                'task_link'   => $time['task_link'],
                'minutes'     => $time['minutes'],
                'is_downtime' => self::intOrNull($time['is_downtime']),
            ] + $name,
            array_values($page),
            $names
        );

        return [
            'entries' => $entries,
            'count'   => \count($all),
            'minutes' => array_sum(array_map(static fn (array $time) => (int)$time['minutes'], $all)),
        ];
    }

    /**
     * Employee and project name columns for each row (keys in the same order as the former SQL joins).
     *
     * @param array<int, array{user_id: string, project_id: string}> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function nameColumns(array $rows): array
    {
        $rows = array_values($rows);
        $userIds = array_values(array_unique(array_column($rows, 'user_id')));
        $projectIds = array_values(array_unique(array_column($rows, 'project_id')));
        $users = $userIds === [] ? [] : $this->userModel->getIndexedListByFilter(
            'id',
            ['id' => ['IN', $userIds, IQueryBuilder::PARAM_STR_ARRAY]],
            ['id', 'name', 'lastname', 'user_display_name'],
            [],
            [],
            true
        );
        $projects = $projectIds === [] ? [] : $this->projectModel->getIndexedListByFilter(
            'id',
            ['id' => ['IN', $projectIds, IQueryBuilder::PARAM_STR_ARRAY]],
            ['id', 'name'],
            [],
            [],
            true
        );

        return array_map(static function (array $row) use ($users, $projects): array {
            $user = $users[$row['user_id']] ?? null;
            $project = $projects[$row['project_id']] ?? null;

            return [
                'first_name'         => $user['name'] ?? null,
                'last_name'          => $user['lastname'] ?? null,
                'display_name'       => $user['user_display_name'] ?? null,
                'project_name'       => $project['name'] ?? null,
                'profile_user_id'    => $user['id'] ?? null,
                'profile_project_id' => $project['id'] ?? null,
            ];
        }, $rows);
    }

    /**
     * Active records plus the others that still have reportable hours.
     * Like the former SQL `deleted = 0 OR <has hours>`, a NULL `deleted` is not active.
     *
     * @param string   $timesField TimesModel column that references the model (project_id/user_id)
     * @param string[] $fields
     */
    private function withReportableHours(BaseModel $model, string $timesField, array $fields): array
    {
        $records = $model->getListByFilter([], $fields, [], [], true);
        $active = static fn (array $record) => $record['deleted'] !== null && !$record['deleted'];
        $inactiveIds = array_column(array_filter($records, static fn (array $record) => !$active($record)), 'id');
        $withHours = [];

        if ($inactiveIds !== []) {
            $times = $this->timesModel->getListByFilter(
                [
                    $timesField => ['IN', $inactiveIds, IQueryBuilder::PARAM_STR_ARRAY],
                    'status_id' => self::REPORTABLE,
                ],
                [$timesField]
            );
            $withHours = array_flip(array_column($times, $timesField));
        }

        return array_values(array_filter(
            $records,
            static fn (array $record) => $active($record) || isset($withHours[$record['id']])
        ));
    }

    /**
     * Models format dates as ISO timestamps; the report works with Y-m-d.
     */
    private static function day(string $date): string
    {
        return substr($date, 0, 10);
    }

    /**
     * Models cast flags to bool; the report API keeps the database integers.
     */
    private static function intOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int)$value;
    }
}
