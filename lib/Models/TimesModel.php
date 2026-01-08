<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Models;

use OCA\Done\Modules\Projects\Models\ProjectModel;
use OCA\Done\Service\BaseService;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class TimesModel.
 */
class TimesModel extends BaseModel
{
    public string $table = 'done_times_data';
    public string $modelTitle = 'Employee time tracking';
    public string $modelName = 'timesModel';
    public string $dbTableComment = 'Employee time tracking: reports on time spent on projects and tasks.';

    protected array $hashFields = [
        'date',
        'user_id',
        'project_id',
        'description',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'required'   => true,
            'db_comment' => 'Unique identifier for a time tracking entry',
        ],
        'date' => [
            'type'       => IQueryBuilder::PARAM_DATE_IMMUTABLE,
            'title'      => 'Report date',
            'required'   => true,
            'db_comment' => 'Date for which the time report is made',
        ],
        'user_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'User',
            'required'   => true,
            'link'       => UserModel::class,
            'db_comment' => 'User (employee) ID. References oc_done_users_data.id',
        ],
        'project_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Project',
            'required'   => true,
            'link'       => ProjectModel::class,
            'db_comment' => 'ID of the project the work was done for. References oc_done_projects.id',
        ],
        'task_link' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Task link',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'URL to a task in an external system (e.g., Yandex Tracker)',
        ],
        'description' => [
            'type'             => IQueryBuilder::PARAM_LOB,
            'title'            => 'Description',
            'required'         => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Description of the work performed',
        ],
        'comment' => [
            'type'             => IQueryBuilder::PARAM_LOB,
            'title'            => 'Comment',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Additional comment on the time report',
        ],
        'minutes' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'unsigned'   => true,
            'title'      => 'Minutes',
            'required'   => true,
            'db_comment' => 'Number of minutes spent',
        ],
        'is_downtime' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Downtime',
            'required'   => false,
            'db_comment' => 'Downtime flag (1 - yes, this was downtime, 0 - no, this was work time)',
        ],
        'status_id' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Report status',
            'required'   => false,
            'db_comment' => '[FUTURE] Status of the time report. Possible value: 1 (Sent). Other statuses are reserved.',
        ],
        'sort' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Report sort order in day',
            'required'   => false,
            'db_comment' => 'Sort order for time report entries within a single day',
        ],
        'created_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC',
        ],
        'updated_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Updated at',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC',
        ],
    ];

    public const SENT = 1;
    public const APPROVED = 2;
    public const ON_CLARIFICATION = 3;
    public const CLARIFIED = 4;
    public const DELETED = 5;

    /**
     * Get report statuses
     *
     * @return array<int, string>
     */
    public function getReportStatuses(): array
    {
        return [
            self::SENT             => 'Sent',
            self::APPROVED         => 'Approved',
            self::ON_CLARIFICATION => 'On clarification',
            self::CLARIFIED        => 'Clarified',
            self::DELETED          => 'Deleted',
        ];
    }

    /**
     * Get reports for period
     *
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $userId
     * @param array  $projects
     * @param bool   $needYearGrouping
     * @param bool   $needProjects
     *
     * @return array
     */
    public function getTimeData(
        string $dateFrom,
        string $dateTo,
        string $userId,
        array $projects = [],
        bool $needYearGrouping = true,
        bool $needProjects = true,
    ): array {
        $filter = [
            'date'    => ['BETWEEN', [$dateFrom, $dateTo]],
            'user_id' => $userId,
        ];

        if (!empty($projects)) {
            $filter['project_id'] = ['IN', $projects, IQueryBuilder::PARAM_STR_ARRAY];
        }

        $list = $this->getListByFilter($filter, ['*'], ['date', 'ASC']);

        $data = $needYearGrouping ? BaseService::makeHash($list, 'date', true) : $list;

        if ($needProjects) {
            $projectsIds = BaseService::getField($list, 'project_id');

            return [$data, $projectsIds];
        }

        return $data;
    }

    /**
     * Get last project selected by user
     *
     * @param string $userId
     *
     * @return string
     */
    public function getLastProjectId(string $userId): string
    {
        $data = $this->getItemByFilter(
            ['user_id' => $userId],
            ['id', 'user_id', 'project_id'],
            ['created_at', 'DESC']
        );

        return !empty($data) ? ($data['project_id'] ?? '') : '';
    }

    /**
     * Get first report date by user
     *
     * @param string $userId
     *
     * @return string
     */
    public function getFirstReportDate(string $userId): string
    {
        $data = $this->getItemByFilter(
            ['user_id' => $userId],
            ['id', 'user_id', 'date'],
            ['date', 'ASC']
        );

        return !empty($data) ? ($data['date'] ?? '') : '';
    }

    /**
     * Get report status
     *
     * @param string $reportId
     *
     * @return int
     */
    public function getCurrentStatusId(string $reportId): int
    {
        $report = $this->getItem($reportId);

        return $report ? ($report['status_id'] ?? 0) : 0;
    }

    /**
     * Get available next report statuses
     *
     * @param int $statusId
     *
     * @return int[]
     */
    public function getAvailableNextStatuses(int $statusId): array
    {
        return match ($statusId) {
            self::SENT, self::CLARIFIED => [
                self::APPROVED,
                self::ON_CLARIFICATION,
                self::DELETED,
            ],
            self::ON_CLARIFICATION => [
                self::CLARIFIED,
            ],
            default => [],
        };
    }

    /**
     * Get available report actions
     *
     * @param int    $statusId
     * @param string $ownerId
     * @param string $userId
     *
     * @return int[]
     */
    public static function getAvailableActions(int $statusId, string $ownerId, string $userId): array
    {
        if (!$statusId || !$ownerId || !$userId) {
            return [];
        }

        if ($ownerId == $userId) {
            return match ($statusId) {
                self::ON_CLARIFICATION => [
                    self::CLARIFIED,
                ],
                default => [],
            };
        }

        return match ($statusId) {
            self::SENT, self::CLARIFIED => [
                self::APPROVED,
                self::ON_CLARIFICATION,
            ],
            default => [],
        };
    }

    /**
     * Get requirements for report transition to next status
     *
     * @param int $statusId
     *
     * @return array
     */
    public function getRequirementsForNextStatus(int $statusId): array
    {
        $defaultRequirements = [
            'comment' => false,
        ];

        return match ($statusId) {
            self::ON_CLARIFICATION, self::CLARIFIED, self::DELETED => [
                'comment' => true,
            ],
            default => $defaultRequirements,
        };
    }

    /**
     * @override
     */
    public function getListByFilter(
        array $filter = [],
        array $fields = ['*'],
        array $orderBy = [],
        array $additionalOrderBy = [],
        bool $needDeleted = false,
    ): array {
        $data = parent::getListByFilter($filter, $fields, $orderBy, $additionalOrderBy, $needDeleted);

        return array_map(static function ($item) {
            if (isset($item['is_downtime'])) {
                $item['is_downtime'] = (bool)$item['is_downtime'];
            }

            return $item;
        }, $data);
    }

    /**
     * @override
     */
    public function validateData(array $data, bool $save = false, array $ignoreFields = []): array
    {
        if ($data['is_downtime']) {
            $this->fields['comment']['required'] = true;
        }

        return parent::validateData($data, $save, $ignoreFields);
    }

    /**
     * @override
     *
     * @throws Exception
     */
    public function update(array $data, string $id): string
    {
        $preparedLogItems = [];

        $this->unsetIndexField = false;
        $item = $this->getItem($id);

        try {
            foreach ($data as $field => $value) {
                if ($field == 'date') {
                    $value = $value->format('Y-m-d H:i:s');
                    $item[$field] = (new \DateTimeImmutable($item[$field]))->format('Y-m-d H:i:s');
                }

                if ($field == 'is_downtime') {
                    $value = (bool)$value;
                    $item[$field] = (bool)$item[$field];
                }

                if ($item[$field] !== $value) {
                    $preparedLogItems[] = [
                        'report_id' => $item['id'],
                        'datetime'  => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                        'action'    => 'update',
                        'field'     => $field,
                        'valold'    => $item[$field],
                        'valnew'    => $value,
                    ];
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $result = parent::update($data, $id);

        if (!empty($preparedLogItems)) {
            $timesHistoryModel = new TimesHistoryModel();

            foreach ($preparedLogItems as $logItem) {
                $timesHistoryModel->addData($logItem);
            }
        }

        return $result;
    }

    /**
     * @override
     */
    public function delete(string $id): void
    {
        $item = $this->getItem($id);

        $preparedLogItems = [
            'report_id' => $item['id'],
            'datetime'  => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'action'    => 'delete',
            'field'     => 'entity',
            'valold'    => 0,
            'valnew'    => 0,
        ];

        parent::delete($id);
        (new TimesHistoryModel())->addData($preparedLogItems);
    }
}
