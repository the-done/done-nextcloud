<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Vacations\Model;

use OCA\Done\Models\BaseModel;
use OCA\Done\Models\UserModel;
use OCA\Done\Modules\Agreement\Model\AgreementRequestsModel;
use OCA\Done\Modules\Projects\Models\ProjectModel;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class VacationsModel.
 *
 * Main model for employee absence requests (vacations, sick leave, day-offs).
 */
class VacationsModel extends BaseModel
{
    public string $table = 'done_vacations';
    public string $modelTitle = 'Employee vacations';
    public string $modelName = 'vacationModel';
    public string $dbTableComment = 'Employee vacations: employee vacation records.';

    protected array $hashFields = [
        'date_start',
        'date_end',
        'user_id',
        'type_id',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'required'   => true,
            'db_comment' => 'Unique identifier for a vacation entry',
        ],
        'date_start' => [
            'type'       => IQueryBuilder::PARAM_DATE_IMMUTABLE,
            'title'      => 'Vacation start date',
            'required'   => true,
            'db_comment' => 'Absence start date',
        ],
        'date_end' => [
            'type'       => IQueryBuilder::PARAM_DATE_IMMUTABLE,
            'title'      => 'Vacation end date',
            'required'   => true,
            'db_comment' => 'Absence end date',
        ],
        'half_day' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Half day',
            'required'   => false,
            'db_comment' => 'When true, the last day of the absence is a half day (total length = calendar days - 0.5).',
        ],
        'user_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'User',
            'required'   => true,
            'link'       => UserModel::class,
            'db_comment' => 'Employee who will be absent (the subject of the request). References oc_done_users_data.id',
        ],
        'type_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Type',
            'required'   => true,
            'link'       => VacationsTypesModel::class,
            'db_comment' => 'Absence type ID. References oc_done_vacations_types.id',
        ],
        'project_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Project',
            'link'       => ProjectModel::class,
            'required'   => false,
            'db_comment' => 'Optional project ID for project-based employees. References oc_done_projects.id',
        ],
        'status_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Vacation status',
            'required'   => false,
            'db_comment' => 'Request status from Agreement module: pending, approved, rejected, returned',
        ],
        'created_by' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Created by',
            'required'   => true,
            'link'       => UserModel::class,
            'db_comment' => 'User who created this request (may differ from user_id if HR creates request for employee). References oc_done_users_data.id',
        ],
        'comment' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Comment',
            'required'         => false,
            'db_comment'       => 'Comment for the request (reason, rejection explanation, etc.)',
            'show'             => true,
            'validation_rules' => [
                'trim' => true,
            ],
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

    // Status constants - aligned with Agreement module
    public const STATUS_PENDING = AgreementRequestsModel::STATUS_PENDING;
    public const STATUS_APPROVED = AgreementRequestsModel::STATUS_APPROVED;
    public const STATUS_REJECTED = AgreementRequestsModel::STATUS_REJECTED;
    public const STATUS_RETURNED = AgreementRequestsModel::STATUS_RETURNED;
    public const STATUS_CANCELED = AgreementRequestsModel::STATUS_CANCELED;

    /**
     * Get vacation statuses from Agreement module.
     *
     * @return array
     */
    public function getVacationStatuses(): array
    {
        $agreementModel = new AgreementRequestsModel();

        return $agreementModel->getStatusOptions();
    }

    /**
     * Check if vacation can be processed (must be in pending or returned status)
     */
    public function canBeProcessed(array $vacation): bool
    {
        $status = $vacation['status_id'] ?? '';

        return \in_array($status, [self::STATUS_PENDING, self::STATUS_RETURNED], true);
    }

    /**
     * Check if vacation is pending approval
     */
    public function isPending(array $vacation): bool
    {
        return ($vacation['status_id'] ?? '') === self::STATUS_PENDING;
    }

    /**
     * Check if vacation is approved
     */
    public function isApproved(array $vacation): bool
    {
        return ($vacation['status_id'] ?? '') === self::STATUS_APPROVED;
    }

    /**
     * Check if vacation was returned for revision
     */
    public function isReturned(array $vacation): bool
    {
        return ($vacation['status_id'] ?? '') === self::STATUS_RETURNED;
    }

    /**
     * Calculate the number of days for a vacation
     */
    public function calculateDays(string $dateStart, string $dateEnd): int
    {
        $start = new \DateTimeImmutable($dateStart);
        $end = new \DateTimeImmutable($dateEnd);

        return $start->diff($end)->days + 1;
    }
}
