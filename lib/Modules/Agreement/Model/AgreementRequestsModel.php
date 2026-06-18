<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Agreement\Model;

use OCA\Done\Models\BaseModel;
use OCA\Done\Models\UserModel;
use OCA\Done\Service\BaseService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class AgreementRequestsModel.
 *
 * Actual agreement requests created from entities (vacations, purchases, etc.).
 */
class AgreementRequestsModel extends BaseModel
{
    public string $table = 'done_agr_requests';
    public string $modelTitle = 'Agreement Requests';
    public string $modelName = 'agreementRequests';
    public string $dbTableComment = 'Agreement requests: actual requests created from entities that need agreement.';

    protected array $hashFields = [
        'entity_type',
        'entity_id',
        'scheme_id',
        'current_line_id',
        'status',
        'cached_title',
        'cached_data',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'required'   => true,
            'db_comment' => 'Unique identifier for an agreement request',
        ],
        'entity_type' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Entity type',
            'required'   => true,
            'db_comment' => 'Type of entity being agreed (e.g., vacation, purchase)',
        ],
        'entity_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Entity ID',
            'required'   => true,
            'db_comment' => 'ID of the entity being agreed',
        ],
        'scheme_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Scheme',
            'required'   => true,
            'link'       => AgreementSchemesModel::class,
            'db_comment' => 'Agreement scheme used for this request. References oc_done_agr_schemes.id',
        ],
        'current_line_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Current line',
            'required'   => false,
            'link'       => AgreementSchemeLinesModel::class,
            'db_comment' => 'Current agreement line being processed. References oc_done_agr_scheme_lines.id',
        ],
        'status' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Status',
            'required'   => true,
            'db_comment' => 'Request status: pending, approved, rejected, returned',
        ],
        'cached_title' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Cached title',
            'required'   => false,
            'db_comment' => 'Cached title/summary of the entity for quick display',
        ],
        'cached_data' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Cached data',
            'required'   => false,
            'db_comment' => 'JSON-encoded cached key data from the entity',
        ],
        'deadline_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Deadline at',
            'required'   => false,
            'db_comment' => 'Deadline for current line agreement in UTC',
        ],
        'created_by' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Created by',
            'required'   => true,
            'link'       => UserModel::class,
            'db_comment' => 'User who created the request. References oc_done_users_data.id',
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
        'completed_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Completed at',
            'required'   => false,
            'db_comment' => 'When the request was fully agreed/rejected in UTC',
        ],
        'project_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Project',
            'required'   => false,
            'db_comment' => 'Optional project ID associated with this request. References oc_done_projects.id',
        ],
        'deleted' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Deleted',
            'db_comment' => 'Soft delete flag (1=deleted, 0=active)',
        ],
    ];

    // Request statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELED = 'canceled';

    /**
     * Get request statuses with translations.
     */
    public function getStatuses(): array
    {
        return [
            self::STATUS_PENDING  => $this->translateService->getTranslate('Pending'),
            self::STATUS_APPROVED => $this->translateService->getTranslate('Approved'),
            self::STATUS_REJECTED => $this->translateService->getTranslate('Rejected'),
            self::STATUS_RETURNED => $this->translateService->getTranslate('Returned'),
            self::STATUS_CANCELED => $this->translateService->getTranslate('Canceled'),
        ];
    }

    /**
     * Get request statuses as options for frontend.
     */
    public function getStatusOptions(): array
    {
        return [
            ['id' => self::STATUS_PENDING, 'name' => $this->translateService->getTranslate('Pending'), 'color' => '#FFC107'],
            ['id' => self::STATUS_APPROVED, 'name' => $this->translateService->getTranslate('Approved'), 'color' => '#4CAF50'],
            ['id' => self::STATUS_REJECTED, 'name' => $this->translateService->getTranslate('Rejected'), 'color' => '#F44336'],
            ['id' => self::STATUS_RETURNED, 'name' => $this->translateService->getTranslate('Returned'), 'color' => '#2196F3'],
            ['id' => self::STATUS_CANCELED, 'name' => $this->translateService->getTranslate('Canceled'), 'color' => '#9E9E9E'],
        ];
    }

    /**
     * Get request for an entity.
     */
    public function getRequestForEntity(string $entityType, string $entityId): ?array
    {
        $request = $this->getItemByFilter([
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
        ]);

        return !empty($request) ? $request : null;
    }

    /**
     * Get pending requests for an entity type.
     */
    public function getPendingRequests(string $entityType): array
    {
        return $this->getListByFilter([
            'entity_type' => $entityType,
            'status'      => self::STATUS_PENDING,
        ]);
    }

    /**
     * Get requests waiting for a specific user's agreement.
     */
    public function getRequestsForApprover(string $userId, array $filters = []): array
    {
        $approversModel = new AgreementSchemeApproversModel();
        $actionsModel = new AgreementRequestActionsModel();

        // Build filter for pending requests
        $filter = ['status' => self::STATUS_PENDING];

        if (!empty($filters['entity_type'])) {
            $filter['entity_type'] = $filters['entity_type'];
        }

        if (!empty($filters['created_by'])) {
            $filter['created_by'] = ['IN', $filters['created_by'], IQueryBuilder::PARAM_STR_ARRAY];
        }

        if (!empty($filters['project_ids'])) {
            $filter['project_id'] = ['IN', $filters['project_ids'], IQueryBuilder::PARAM_STR_ARRAY];
        }

        if (!empty($filters['date_from'])) {
            $filter['created_at'] = ['>=', new \DateTimeImmutable($filters['date_from'])];
        }

        $pendingRequests = $this->getListByFilter($filter);

        // Apply date_to filter (second condition on created_at, can't use same key in filter array)
        if (!empty($filters['date_to'])) {
            $dateTo = new \DateTimeImmutable($filters['date_to'] . ' 23:59:59');
            $pendingRequests = array_filter($pendingRequests, static function ($request) use ($dateTo) {
                return new \DateTimeImmutable($request['created_at']) <= $dateTo;
            });
        }

        $result = [];

        $linesIds = BaseService::getField($pendingRequests, 'current_line_id', true);
        $requestsIds = BaseService::getField($pendingRequests, 'id', true);
        $approverUserIdsByLines = $approversModel->getApproverUserIdsByLines($linesIds);
        $actedRequestsForUser = $actionsModel->getActedRequestsForUser($requestsIds, $userId);

        foreach ($pendingRequests as $request) {
            if (empty($request['current_line_id'])) {
                continue;
            }

            // Check if user is an approver for this line
            $approverUserIds = $approverUserIdsByLines[$request['current_line_id']] ?? [];

            if (!\in_array($userId, $approverUserIds, true)) {
                continue;
            }

            $lineId = $request['current_line_id'];
            $actedKey = "{$request['id']}-{$lineId}";

            // Exclude if the user already acted on this line of THIS request.
            if (isset($actedRequestsForUser[$actedKey])) {
                continue;
            }

            $result[] = $request;
        }

        return $result;
    }

    /**
     * Get completed (non-pending) requests where the user has acted as an approver.
     */
    public function getCompletedRequestsForApprover(string $userId, array $filters = []): array
    {
        $actionsModel = new AgreementRequestActionsModel();

        // Get all request IDs where user performed an action
        $userActions = $actionsModel->getListByFilter(
            ['user_id' => $userId],
            ['request_id']
        );

        if (empty($userActions)) {
            return [];
        }

        $requestIds = array_unique(array_column($userActions, 'request_id'));

        // Build filter
        $filter = [
            'id'     => ['IN', $requestIds, IQueryBuilder::PARAM_STR_ARRAY],
            'status' => ['!=', self::STATUS_PENDING],
        ];

        if (!empty($filters['entity_type'])) {
            $filter['entity_type'] = $filters['entity_type'];
        }

        if (!empty($filters['created_by'])) {
            $filter['created_by'] = ['IN', $filters['created_by'], IQueryBuilder::PARAM_STR_ARRAY];
        }

        if (!empty($filters['project_ids'])) {
            $filter['project_id'] = ['IN', $filters['project_ids'], IQueryBuilder::PARAM_STR_ARRAY];
        }

        if (!empty($filters['date_from'])) {
            $filter['created_at'] = ['>=', new \DateTimeImmutable($filters['date_from'])];
        }

        $result = $this->getListByFilter($filter, ['*'], ['updated_at', 'DESC']);

        // Apply date_to filter
        if (!empty($filters['date_to'])) {
            $dateTo = new \DateTimeImmutable($filters['date_to'] . ' 23:59:59');
            $result = array_filter($result, static function ($request) use ($dateTo) {
                return new \DateTimeImmutable($request['created_at']) <= $dateTo;
            });
        }

        return array_values($result);
    }

    /**
     * Check if request is pending.
     */
    public function isPending(array $request): bool
    {
        return ($request['status'] ?? '') === self::STATUS_PENDING;
    }

    /**
     * Check if request can be processed (approved/rejected/returned).
     */
    public function canBeProcessed(array $request): bool
    {
        return $this->isPending($request) && !empty($request['current_line_id']);
    }

    /**
     * Get cached data as array.
     */
    public function getCachedData(array $request): array
    {
        if (empty($request['cached_data'])) {
            return [];
        }

        $data = json_decode($request['cached_data'], true);

        return \is_array($data) ? $data : [];
    }
}
