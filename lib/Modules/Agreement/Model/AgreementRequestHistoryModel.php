<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Agreement\Model;

use OCA\Done\Models\BaseModel;
use OCA\Done\Models\UserModel;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class AgreementRequestHistoryModel.
 *
 * History of all changes to agreement requests (status changes, line transitions, etc.).
 */
class AgreementRequestHistoryModel extends BaseModel
{
    public string $table = 'done_agr_req_history';
    public string $modelTitle = 'Agreement Request History';
    public string $modelName = 'agreementRequestHistory';
    public string $dbTableComment = 'Agreement request history: audit trail of all changes to requests.';

    protected array $hashFields = [
        'request_id',
        'event_type',
        'status_before',
        'status_after',
        'line_id_before',
        'line_id_after',
        'action',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'required'   => true,
            'db_comment' => 'Unique identifier for a history entry',
        ],
        'request_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Request',
            'required'   => true,
            'link'       => AgreementRequestsModel::class,
            'db_comment' => 'Agreement request ID. References oc_done_agr_requests.id',
        ],
        'event_type' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Event type',
            'required'   => true,
            'db_comment' => 'Type of event: created, status_changed, line_changed, action_performed',
        ],
        'status_before' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Status before',
            'required'   => false,
            'db_comment' => 'Status before the change (null for new requests)',
        ],
        'status_after' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Status after',
            'required'   => false,
            'db_comment' => 'Status after the change',
        ],
        'line_id_before' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Line before',
            'required'   => false,
            'link'       => AgreementSchemeLinesModel::class,
            'db_comment' => 'Line ID before the change. References oc_done_agr_scheme_lines.id',
        ],
        'line_id_after' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Line after',
            'required'   => false,
            'link'       => AgreementSchemeLinesModel::class,
            'db_comment' => 'Line ID after the change. References oc_done_agr_scheme_lines.id',
        ],
        'action' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Action',
            'required'   => false,
            'db_comment' => 'Action performed: approved, rejected, returned (if event_type is action_performed)',
        ],
        'changed_by' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Changed by',
            'required'   => true,
            'link'       => UserModel::class,
            'db_comment' => 'User who made the change. References oc_done_users_data.id',
        ],
        'comment' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Comment',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Comment for the change',
        ],
        'created_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC',
        ],
    ];

    // Event types
    public const EVENT_CREATED = 'created';
    public const EVENT_STATUS_CHANGED = 'status_changed';
    public const EVENT_LINE_CHANGED = 'line_changed';
    public const EVENT_ACTION_PERFORMED = 'action_performed';

    /**
     * Get event types with translations.
     */
    public function getEventTypes(): array
    {
        return [
            self::EVENT_CREATED          => $this->translateService->getTranslate('Created'),
            self::EVENT_STATUS_CHANGED   => $this->translateService->getTranslate('Status changed'),
            self::EVENT_LINE_CHANGED     => $this->translateService->getTranslate('Line changed'),
            self::EVENT_ACTION_PERFORMED => $this->translateService->getTranslate('Action performed'),
        ];
    }

    /**
     * Get history for a request.
     */
    public function getHistoryForRequest(string $requestId): array
    {
        return $this->getListByFilter(
            ['request_id' => $requestId],
            ['*'],
            ['created_at', 'DESC']
        );
    }

    /**
     * Record request creation.
     */
    public function recordCreation(string $requestId, string $initialStatus, string $lineId, string $createdBy): string
    {
        return $this->addData([
            'request_id'    => $requestId,
            'event_type'    => self::EVENT_CREATED,
            'status_after'  => $initialStatus,
            'line_id_after' => $lineId,
            'changed_by'    => $createdBy,
        ]);
    }

    /**
     * Record status change.
     */
    public function recordStatusChange(
        string $requestId,
        ?string $statusBefore,
        string $statusAfter,
        string $changedBy,
        ?string $comment = null
    ): string {
        return $this->addData([
            'request_id'    => $requestId,
            'event_type'    => self::EVENT_STATUS_CHANGED,
            'status_before' => $statusBefore,
            'status_after'  => $statusAfter,
            'changed_by'    => $changedBy,
            'comment'       => $comment,
        ]);
    }

    /**
     * Record line change (moving to next approval stage).
     */
    public function recordLineChange(
        string $requestId,
        ?string $lineIdBefore,
        ?string $lineIdAfter,
        string $changedBy
    ): string {
        return $this->addData([
            'request_id'     => $requestId,
            'event_type'     => self::EVENT_LINE_CHANGED,
            'line_id_before' => $lineIdBefore,
            'line_id_after'  => $lineIdAfter,
            'changed_by'     => $changedBy,
        ]);
    }

    /**
     * Record action performed (approve/reject/return).
     */
    public function recordAction(
        string $requestId,
        string $action,
        string $changedBy,
        ?string $comment = null
    ): string {
        return $this->addData([
            'request_id' => $requestId,
            'event_type' => self::EVENT_ACTION_PERFORMED,
            'action'     => $action,
            'changed_by' => $changedBy,
            'comment'    => $comment,
        ]);
    }
}
