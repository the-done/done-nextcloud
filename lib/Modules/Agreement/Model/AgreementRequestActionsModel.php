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
 * Class AgreementRequestActionsModel.
 *
 * Actions (approve/reject/return) performed on agreement requests.
 */
class AgreementRequestActionsModel extends BaseModel
{
    public string $table = 'done_agr_req_actions';
    public string $modelTitle = 'Agreement Request Actions';
    public string $modelName = 'agreementRequestActions';
    public string $dbTableComment = 'Agreement request actions: history of actions (approve/reject/return) on requests.';

    protected array $hashFields = [
        'request_id',
        'line_id',
        'user_id',
        'action',
        'comment',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'required'   => true,
            'db_comment' => 'Unique identifier for an action',
        ],
        'request_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Request',
            'required'   => true,
            'link'       => AgreementRequestsModel::class,
            'db_comment' => 'Agreement request ID. References oc_done_agr_requests.id',
        ],
        'line_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Line',
            'required'   => true,
            'link'       => AgreementSchemeLinesModel::class,
            'db_comment' => 'Line ID where action was performed. References oc_done_agr_scheme_lines.id',
        ],
        'user_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'User',
            'required'   => true,
            'link'       => UserModel::class,
            'db_comment' => 'User who performed the action. References oc_done_users_data.id',
        ],
        'action' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Action',
            'required'   => true,
            'db_comment' => 'Action type: approved, rejected, returned',
        ],
        'comment' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Comment',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Comment from approver (required for reject/return)',
        ],
        'created_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC',
        ],
    ];

    // Action types
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_RETURNED = 'returned';

    /**
     * Get action types with translations.
     */
    public function getActionTypes(): array
    {
        return [
            self::ACTION_APPROVED => $this->translateService->getTranslate('Approved'),
            self::ACTION_REJECTED => $this->translateService->getTranslate('Rejected'),
            self::ACTION_RETURNED => $this->translateService->getTranslate('Returned for revision'),
        ];
    }

    /**
     * Get action type options for frontend.
     */
    public function getActionTypeOptions(): array
    {
        return [
            ['id' => self::ACTION_APPROVED, 'name' => $this->translateService->getTranslate('Approved'), 'color' => '#4CAF50'],
            ['id' => self::ACTION_REJECTED, 'name' => $this->translateService->getTranslate('Rejected'), 'color' => '#F44336'],
            ['id' => self::ACTION_RETURNED, 'name' => $this->translateService->getTranslate('Returned for revision'), 'color' => '#2196F3'],
        ];
    }

    /**
     * Get all actions for a request.
     */
    public function getActionsForRequest(string $requestId): array
    {
        return $this->getListByFilter(
            ['request_id' => $requestId],
            ['*'],
            ['created_at', 'DESC']
        );
    }

    /**
     * Get actions for a specific line in a request.
     */
    public function getActionsForLine(string $requestId, string $lineId): array
    {
        return $this->getListByFilter([
            'request_id' => $requestId,
            'line_id'    => $lineId,
        ]);
    }

    /**
     * Check if user already acted on this line in the current round.
     * Actions older than the latest 'returned' on the request belong to a
     * previous round (the request has since been resubmitted) and don't count.
     */
    public function hasUserActed(string $requestId, string $lineId, string $userId): bool
    {
        $actions = $this->getListByFilter([
            'request_id' => $requestId,
            'line_id'    => $lineId,
            'user_id'    => $userId,
        ]);

        if (empty($actions)) {
            return false;
        }

        $latestReturn = $this->getLatestReturnTime($requestId);

        foreach ($actions as $action) {
            if ($this->isActionInCurrentRound($action, $latestReturn)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a map "{userId}-{lineId}" => true for current-round actions only,
     * so requests that have been resubmitted after a 'returned' aren't filtered
     * out for approvers who already acted in a previous round.
     */
    public function getActedRequestsForUser(array $requestsIds, string $userId): array
    {
        $result = [];

        if (empty($requestsIds)) {
            return $result;
        }

        $latestReturnByRequest = $this->getLatestReturnTimesByRequest($requestsIds);

        $actions = $this->getListByFilter([
            'request_id' => ['IN', $requestsIds, IQueryBuilder::PARAM_STR_ARRAY],
            'user_id'    => $userId,
        ]);

        foreach ($actions as $action) {
            $latestReturn = $latestReturnByRequest[$action['request_id']] ?? null;

            if (!$this->isActionInCurrentRound($action, $latestReturn)) {
                continue;
            }

            // Key by request + line: scheme lines are shared across requests, so
            // keying by line alone would mark every request on that line as acted
            // once the user acts on any single one.
            $result["{$action['request_id']}-{$action['line_id']}"] = true;
        }

        return $result;
    }

    /**
     * Count approvals for a line in the current round (after the latest return,
     * if any).
     */
    public function countApprovalsForLine(string $requestId, string $lineId): int
    {
        $actions = $this->getActionsForLine($requestId, $lineId);
        $latestReturn = $this->getLatestReturnTime($requestId);

        return \count(array_filter($actions, function ($action) use ($latestReturn) {
            if ($action['action'] !== self::ACTION_APPROVED) {
                return false;
            }

            return $this->isActionInCurrentRound($action, $latestReturn);
        }));
    }

    /**
     * Get the start time of the current approval round for a single request.
     *
     * A round starts each time the request transitions into 'pending' (initial
     * submit, resubmit after return, or resubmit after an approved edit). Actions
     * recorded before this time belong to a previous round and are ignored.
     */
    private function getLatestReturnTime(string $requestId): ?\DateTimeImmutable
    {
        $rows = (new AgreementRequestHistoryModel())->getListByFilter(
            [
                'request_id'   => $requestId,
                'event_type'   => AgreementRequestHistoryModel::EVENT_STATUS_CHANGED,
                'status_after' => AgreementRequestsModel::STATUS_PENDING,
            ],
            ['created_at'],
            ['created_at', 'DESC']
        );

        if (empty($rows[0]['created_at'])) {
            return null;
        }

        return new \DateTimeImmutable($rows[0]['created_at']);
    }

    /**
     * Get the current-round start time per request, indexed by request_id.
     * See getLatestReturnTime for the round definition.
     *
     * @param string[] $requestsIds
     *
     * @return array<string, \DateTimeImmutable>
     */
    private function getLatestReturnTimesByRequest(array $requestsIds): array
    {
        if (empty($requestsIds)) {
            return [];
        }

        $rows = (new AgreementRequestHistoryModel())->getListByFilter(
            [
                'request_id'   => ['IN', $requestsIds, IQueryBuilder::PARAM_STR_ARRAY],
                'event_type'   => AgreementRequestHistoryModel::EVENT_STATUS_CHANGED,
                'status_after' => AgreementRequestsModel::STATUS_PENDING,
            ],
            ['request_id', 'created_at']
        );

        $result = [];

        foreach ($rows as $row) {
            $rid = $row['request_id'];
            $time = new \DateTimeImmutable($row['created_at']);

            if (!isset($result[$rid]) || $time > $result[$rid]) {
                $result[$rid] = $time;
            }
        }

        return $result;
    }

    /**
     * Check whether an action belongs to the current approval round, i.e. it
     * happened at or after the current round started.
     */
    private function isActionInCurrentRound(array $action, ?\DateTimeImmutable $roundStart): bool
    {
        if ($roundStart === null) {
            return true;
        }

        $actionTime = new \DateTimeImmutable($action['created_at']);

        return $actionTime >= $roundStart;
    }

    /**
     * Record an action.
     */
    public function recordAction(
        string $requestId,
        string $lineId,
        string $userId,
        string $action,
        ?string $comment = null
    ): string {
        return $this->addData([
            'request_id' => $requestId,
            'line_id'    => $lineId,
            'user_id'    => $userId,
            'action'     => $action,
            'comment'    => $comment,
        ]);
    }
}
