<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Agreement\Service;

use OCA\Done\Models\Dictionaries\GlobalRolesModel;
use OCA\Done\Models\UserModel;
use OCA\Done\Modules\Agreement\Model\AgreementRequestActionsModel;
use OCA\Done\Modules\Agreement\Model\AgreementRequestHistoryModel;
use OCA\Done\Modules\Agreement\Model\AgreementRequestsModel;
use OCA\Done\Modules\Agreement\Model\AgreementSchemeApproversModel;
use OCA\Done\Modules\Agreement\Model\AgreementSchemeAssignmentsModel;
use OCA\Done\Modules\Agreement\Model\AgreementSchemeLinesModel;
use OCA\Done\Modules\Agreement\Model\AgreementSchemesModel;
use OCA\Done\Modules\Vacations\Service\VacationsService;
use OCA\Done\Notification\Notifier;
use OCA\Done\Service\BaseService;
use OCA\Done\Service\NotificationService;
use OCA\Done\Service\TranslateService;
use OCA\Done\Service\UserService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Server;

class AgreementService
{
    private UserService $userService;
    private TranslateService $translateService;
    private static AgreementService $instance;

    public function __construct()
    {
        $this->userService = UserService::getInstance();
        $this->translateService = TranslateService::getInstance();
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = Server::get(self::class);
        }

        return self::$instance;
    }

    // =========================================================================
    // Scheme Management
    // =========================================================================

    /**
     * Check whether the entity type has a usable agreement scheme: at least one
     * active scheme that has at least one line with at least one approver. A
     * scheme without lines or with empty lines cannot actually move a request
     * through approval, so we treat it as "no scheme" for the purposes of
     * gating new requests.
     */
    public function hasActiveSchemeForEntityType(string $entityType): bool
    {
        $schemesModel = new AgreementSchemesModel();
        $linesModel = new AgreementSchemeLinesModel();
        $approversModel = new AgreementSchemeApproversModel();

        $schemes = $schemesModel->getActiveSchemes($entityType);

        if (empty($schemes)) {
            return false;
        }

        $schemeIds = BaseService::getField($schemes, 'id', true);

        $lines = $linesModel->getListByFilter(
            ['scheme_id' => ['IN', $schemeIds, IQueryBuilder::PARAM_STR_ARRAY]],
            ['id']
        );

        if (empty($lines)) {
            return false;
        }

        $lineIds = BaseService::getField($lines, 'id', true);

        $approver = $approversModel->getItemByFilter(
            ['line_id' => ['IN', $lineIds, IQueryBuilder::PARAM_STR_ARRAY]],
            ['id']
        );

        return !empty($approver);
    }

    /**
     * Get scheme with all its lines and approvers.
     */
    public function getSchemeWithDetails(string $schemeId): ?array
    {
        $schemesModel = new AgreementSchemesModel();
        $linesModel = new AgreementSchemeLinesModel();
        $approversModel = new AgreementSchemeApproversModel();
        $userModel = new UserModel();
        $rolesModel = new GlobalRolesModel();

        $scheme = $schemesModel->getItem($schemeId);

        if (empty($scheme)) {
            return null;
        }

        $lines = $linesModel->getLinesForScheme($schemeId);

        foreach ($lines as &$line) {
            $approvers = $approversModel->getApproversForLine($line['id']);

            // Enrich with user/role names
            foreach ($approvers as &$approver) {
                if ($approver['approver_type'] === AgreementSchemeApproversModel::TYPE_USER) {
                    $user = $userModel->getItem($approver['user_id']);
                    $approver['name'] = $user['name'] ?? 'Unknown';
                } elseif ($approver['approver_type'] === AgreementSchemeApproversModel::TYPE_ROLE) {
                    $role = $rolesModel->getItem($approver['role_id']);
                    $approver['name'] = $role['name'] ?? 'Unknown role';
                }
            }

            $line['approvers'] = $approvers;
        }

        $scheme['lines'] = $lines;

        return $scheme;
    }

    /**
     * Create a new agreement scheme.
     */
    public function createScheme(array $data): string
    {
        $schemesModel = new AgreementSchemesModel();

        $data['created_by'] = $data['created_by'] ?? $this->userService->getCurrentUserId();

        return $schemesModel->addData($data);
    }

    /**
     * Soft-delete a scheme together with all related entities: its assignments,
     * its lines, and the approvers of those lines. Existing agreement requests
     * that reference this scheme are intentionally left untouched so that
     * historical data and in-progress flows remain readable.
     */
    public function deleteSchemeWithRelated(string $schemeId): void
    {
        $schemesModel = new AgreementSchemesModel();
        $linesModel = new AgreementSchemeLinesModel();
        $assignmentsModel = new AgreementSchemeAssignmentsModel();

        // Delete all assignments pointing to this scheme
        $assignments = $assignmentsModel->getListByFilter(['scheme_id' => $schemeId]);

        foreach ($assignments as $assignment) {
            $assignmentsModel->delete($assignment['id']);
        }

        // Delete all lines (and their approvers) belonging to this scheme
        $lines = $linesModel->getLinesForScheme($schemeId);

        foreach ($lines as $line) {
            $this->deleteLineWithRelated($line['id']);
        }

        // Finally, soft-delete the scheme itself
        $schemesModel->delete($schemeId);
    }

    /**
     * Soft-delete a scheme line together with all of its approvers.
     */
    public function deleteLineWithRelated(string $lineId): void
    {
        $linesModel = new AgreementSchemeLinesModel();
        $approversModel = new AgreementSchemeApproversModel();

        $approvers = $approversModel->getApproversForLine($lineId);

        foreach ($approvers as $approver) {
            $approversModel->delete($approver['id']);
        }

        $linesModel->delete($lineId);
    }

    /**
     * Add a line to a scheme.
     */
    public function addLineToScheme(string $schemeId, array $lineData): string
    {
        $linesModel = new AgreementSchemeLinesModel();

        $lineData['scheme_id'] = $schemeId;

        // Auto-set sort_order if not provided
        if (!isset($lineData['sort_order'])) {
            $existingLines = $linesModel->getLinesForScheme($schemeId);
            $maxOrder = 0;

            foreach ($existingLines as $line) {
                if ((int)$line['sort_order'] > $maxOrder) {
                    $maxOrder = (int)$line['sort_order'];
                }
            }

            $lineData['sort_order'] = $maxOrder + 1;
        }

        return $linesModel->addData($lineData);
    }

    /**
     * Add approver to a line.
     */
    public function addApproverToLine(string $lineId, string $type, ?string $userId = null, ?int $roleId = null): ?string
    {
        $approversModel = new AgreementSchemeApproversModel();

        if ($type === AgreementSchemeApproversModel::TYPE_USER && $userId !== null) {
            return $approversModel->addUserApprover($lineId, $userId);
        }

        if ($type === AgreementSchemeApproversModel::TYPE_ROLE && $roleId !== null) {
            return $approversModel->addRoleApprover($lineId, $roleId);
        }

        return null;
    }

    // =========================================================================
    // Request Creation
    // =========================================================================

    /**
     * Create an agreement request for an entity.
     *
     * @param string      $entityType  Type of entity (e.g., 'vacation')
     * @param string      $entityId    ID of the entity
     * @param null|string $cachedTitle Cached title for display
     * @param null|array  $cachedData  Cached data from entity
     * @param null|string $userId      User ID for scheme resolution (author)
     * @param null|string $projectId   Project ID for scheme resolution
     *
     * @return null|array Created request or null if no scheme found
     */
    public function createRequest(
        string $entityType,
        string $entityId,
        ?string $cachedTitle = null,
        ?array $cachedData = null,
        ?string $userId = null,
        ?string $projectId = null
    ): ?array {
        $requestsModel = new AgreementRequestsModel();
        $assignmentsModel = new AgreementSchemeAssignmentsModel();
        $linesModel = new AgreementSchemeLinesModel();

        // Check if request already exists
        $existingRequest = $requestsModel->getRequestForEntity($entityType, $entityId);

        if (!empty($existingRequest)) {
            return $existingRequest;
        }

        // Find appropriate scheme
        $scheme = $assignmentsModel->getSchemeForContext($entityType, $userId, $projectId);

        if (empty($scheme)) {
            return null;
        }

        // Get first line
        $firstLine = $linesModel->getFirstLine($scheme['id']);

        if (empty($firstLine)) {
            return null;
        }

        $currentUserId = $this->userService->getCurrentUserId();

        // Calculate deadline if enabled
        $deadlineAt = null;

        if (!empty($firstLine['deadline_enabled']) && !empty($firstLine['deadline_days'])) {
            $deadlineAt = (new \DateTimeImmutable())
                ->modify('+' . (int)$firstLine['deadline_days'] . ' days');
        }

        $requestId = $requestsModel->addData([
            'entity_type'     => $entityType,
            'entity_id'       => $entityId,
            'scheme_id'       => $scheme['id'],
            'current_line_id' => $firstLine['id'],
            'status'          => AgreementRequestsModel::STATUS_PENDING,
            'cached_title'    => $cachedTitle,
            'cached_data'     => $cachedData !== null ? json_encode($cachedData) : null,
            'deadline_at'     => $deadlineAt,
            'created_by'      => $currentUserId,
            'project_id'      => $projectId,
        ]);

        // Record creation in history
        $historyModel = new AgreementRequestHistoryModel();
        $historyModel->recordCreation(
            $requestId,
            AgreementRequestsModel::STATUS_PENDING,
            $firstLine['id'],
            $currentUserId
        );

        $createdRequest = $requestsModel->getItem($requestId);

        if (!empty($createdRequest)) {
            $this->notifyLineApprovers($firstLine['id'], $createdRequest);
        }

        return $createdRequest;
    }

    // =========================================================================
    // Request Processing
    // =========================================================================

    /**
     * Check if user can act on a request.
     */
    public function canUserActOnRequest(string $requestId, ?string $userId = null): array
    {
        $requestsModel = new AgreementRequestsModel();
        $approversModel = new AgreementSchemeApproversModel();
        $actionsModel = new AgreementRequestActionsModel();

        $userId = $userId ?? $this->userService->getCurrentUserId();
        $request = $requestsModel->getItem($requestId);

        if (empty($request) || !$requestsModel->canBeProcessed($request)) {
            return [
                'can_act'    => false,
                'reason'     => 'Request cannot be processed',
                'is_pending' => false,
            ];
        }

        $lineId = $request['current_line_id'];

        // Check if user is an approver for current line
        $approverUserIds = $approversModel->getApproverUserIds($lineId);

        if (!\in_array($userId, $approverUserIds, true)) {
            return [
                'can_act'    => false,
                'reason'     => 'User is not an approver for current line',
                'is_pending' => true,
            ];
        }

        // Check if user already acted
        if ($actionsModel->hasUserActed($requestId, $lineId, $userId)) {
            return [
                'can_act'    => false,
                'reason'     => 'User already acted on this line',
                'is_pending' => true,
            ];
        }

        return [
            'can_act'    => true,
            'reason'     => null,
            'is_pending' => true,
        ];
    }

    /**
     * Approve a request.
     */
    public function approveRequest(string $requestId, ?string $comment = null, ?string $userId = null): array
    {
        $userId = $userId ?? $this->userService->getCurrentUserId();

        $canAct = $this->canUserActOnRequest($requestId, $userId);

        if (!$canAct['can_act']) {
            return [
                'success' => false,
                'error'   => $canAct['reason'],
            ];
        }

        $requestsModel = new AgreementRequestsModel();
        $linesModel = new AgreementSchemeLinesModel();
        $actionsModel = new AgreementRequestActionsModel();
        $historyModel = new AgreementRequestHistoryModel();

        $request = $requestsModel->getItem($requestId);
        $lineId = $request['current_line_id'];
        $line = $linesModel->getItem($lineId);

        // Record the action
        $actionsModel->recordAction(
            $requestId,
            $lineId,
            $userId,
            AgreementRequestActionsModel::ACTION_APPROVED,
            $comment
        );

        // Record action in history
        $historyModel->recordAction(
            $requestId,
            AgreementRequestActionsModel::ACTION_APPROVED,
            $userId,
            $comment
        );

        // Check if line is complete
        $lineComplete = $this->isLineComplete($requestId, $lineId, $line);

        if ($lineComplete) {
            // Try to move to next line
            $nextLine = $linesModel->getNextLine($request['scheme_id'], (int)$line['sort_order']);

            if ($nextLine !== null) {
                // Move to next line
                $deadlineAt = null;

                if (!empty($nextLine['deadline_enabled']) && !empty($nextLine['deadline_days'])) {
                    $deadlineAt = (new \DateTimeImmutable())
                        ->modify('+' . (int)$nextLine['deadline_days'] . ' days');
                }

                $requestsModel->update([
                    'current_line_id' => $nextLine['id'],
                    'deadline_at'     => $deadlineAt,
                ], $requestId);

                // Record line change in history
                $historyModel->recordLineChange($requestId, $lineId, $nextLine['id'], $userId);

                $this->notifyLineApprovers($nextLine['id'], $request);

                return [
                    'success'      => true,
                    'status'       => 'moved_to_next_line',
                    'next_line_id' => $nextLine['id'],
                ];
            }

            // No more lines - request is fully approved
            $requestsModel->update([
                'status'          => AgreementRequestsModel::STATUS_APPROVED,
                'current_line_id' => null,
                'completed_at'    => new \DateTimeImmutable(),
            ], $requestId);

            // Record status change in history
            $historyModel->recordStatusChange(
                $requestId,
                AgreementRequestsModel::STATUS_PENDING,
                AgreementRequestsModel::STATUS_APPROVED,
                $userId
            );

            // Notify entity service about status change
            $this->notifyEntityStatusChange($request, AgreementRequestsModel::STATUS_APPROVED, $userId);

            return [
                'success' => true,
                'status'  => 'approved',
            ];
        }

        return [
            'success' => true,
            'status'  => 'waiting_for_others',
        ];
    }

    /**
     * Reject a request.
     */
    public function rejectRequest(string $requestId, string $comment, ?string $userId = null): array
    {
        if (empty($comment)) {
            return [
                'success' => false,
                'error'   => 'Comment is required for rejection',
            ];
        }

        $userId = $userId ?? $this->userService->getCurrentUserId();

        $canAct = $this->canUserActOnRequest($requestId, $userId);

        if (!$canAct['can_act']) {
            return [
                'success' => false,
                'error'   => $canAct['reason'],
            ];
        }

        $requestsModel = new AgreementRequestsModel();
        $actionsModel = new AgreementRequestActionsModel();
        $historyModel = new AgreementRequestHistoryModel();

        $request = $requestsModel->getItem($requestId);
        $lineId = $request['current_line_id'];

        // Record the action
        $actionsModel->recordAction(
            $requestId,
            $lineId,
            $userId,
            AgreementRequestActionsModel::ACTION_REJECTED,
            $comment
        );

        // Record action in history
        $historyModel->recordAction(
            $requestId,
            AgreementRequestActionsModel::ACTION_REJECTED,
            $userId,
            $comment
        );

        // Reject immediately
        $requestsModel->update([
            'status'       => AgreementRequestsModel::STATUS_REJECTED,
            'completed_at' => new \DateTimeImmutable(),
        ], $requestId);

        // Record status change in history
        $historyModel->recordStatusChange(
            $requestId,
            AgreementRequestsModel::STATUS_PENDING,
            AgreementRequestsModel::STATUS_REJECTED,
            $userId,
            $comment
        );

        // Notify entity service about status change
        $this->notifyEntityStatusChange($request, AgreementRequestsModel::STATUS_REJECTED, $userId);

        return [
            'success' => true,
            'status'  => 'rejected',
        ];
    }

    /**
     * Cancel a request (typically initiated by the entity owner, e.g. vacation author).
     * Unlike reject/return, no comment is required.
     */
    public function cancelRequest(string $requestId, ?string $userId = null): array
    {
        $userId = $userId ?? $this->userService->getCurrentUserId();

        $requestsModel = new AgreementRequestsModel();
        $historyModel = new AgreementRequestHistoryModel();

        $request = $requestsModel->getItem($requestId);

        if (empty($request)) {
            return [
                'success' => false,
                'error'   => 'Request not found',
            ];
        }

        $previousStatus = $request['status'] ?? '';

        if ($previousStatus === AgreementRequestsModel::STATUS_CANCELED) {
            return [
                'success' => false,
                'error'   => 'Request is already canceled',
            ];
        }

        $requestsModel->update([
            'status'          => AgreementRequestsModel::STATUS_CANCELED,
            'current_line_id' => null,
            'completed_at'    => new \DateTimeImmutable(),
        ], $requestId);

        $historyModel->recordStatusChange(
            $requestId,
            $previousStatus,
            AgreementRequestsModel::STATUS_CANCELED,
            $userId,
        );

        // Clear pending bell notifications for approvers, the request is no longer actionable.
        NotificationService::getInstance()->clearForObject('agreement_request', $requestId);

        return [
            'success' => true,
            'status'  => 'canceled',
        ];
    }

    /**
     * Return a request for revision.
     */
    public function returnRequest(string $requestId, string $comment, ?string $userId = null): array
    {
        if (empty($comment)) {
            return [
                'success' => false,
                'error'   => 'Comment is required for return',
            ];
        }

        $userId = $userId ?? $this->userService->getCurrentUserId();

        $canAct = $this->canUserActOnRequest($requestId, $userId);

        if (!$canAct['can_act']) {
            return [
                'success' => false,
                'error'   => $canAct['reason'],
            ];
        }

        $requestsModel = new AgreementRequestsModel();
        $actionsModel = new AgreementRequestActionsModel();
        $historyModel = new AgreementRequestHistoryModel();

        $request = $requestsModel->getItem($requestId);
        $lineId = $request['current_line_id'];

        // Record the action
        $actionsModel->recordAction(
            $requestId,
            $lineId,
            $userId,
            AgreementRequestActionsModel::ACTION_RETURNED,
            $comment
        );

        // Record action in history
        $historyModel->recordAction(
            $requestId,
            AgreementRequestActionsModel::ACTION_RETURNED,
            $userId,
            $comment
        );

        // Set status to returned
        $requestsModel->update([
            'status' => AgreementRequestsModel::STATUS_RETURNED,
        ], $requestId);

        // Record status change in history
        $historyModel->recordStatusChange(
            $requestId,
            AgreementRequestsModel::STATUS_PENDING,
            AgreementRequestsModel::STATUS_RETURNED,
            $userId,
            $comment
        );

        // Notify entity service about status change
        $this->notifyEntityStatusChange($request, AgreementRequestsModel::STATUS_RETURNED, $userId);

        return [
            'success' => true,
            'status'  => 'returned',
        ];
    }

    /**
     * Resubmit a returned request.
     */
    public function resubmitRequest(string $requestId): array
    {
        $requestsModel = new AgreementRequestsModel();
        $linesModel = new AgreementSchemeLinesModel();
        $historyModel = new AgreementRequestHistoryModel();

        $request = $requestsModel->getItem($requestId);

        if (empty($request)) {
            return [
                'success' => false,
                'error'   => 'Request not found',
            ];
        }

        // A returned request (sent back for revision) or an already-approved one
        // (edited after approval) restarts the approval flow from the first line.
        if (!\in_array($request['status'], [
            AgreementRequestsModel::STATUS_RETURNED,
            AgreementRequestsModel::STATUS_APPROVED,
        ], true)) {
            return [
                'success' => false,
                'error'   => 'Only returned or approved requests can be resubmitted',
            ];
        }

        $previousStatus = $request['status'];

        // Reset to first line
        $firstLine = $linesModel->getFirstLine($request['scheme_id']);

        if (empty($firstLine)) {
            return [
                'success' => false,
                'error'   => 'Scheme has no lines',
            ];
        }

        $deadlineAt = null;

        if (!empty($firstLine['deadline_enabled']) && !empty($firstLine['deadline_days'])) {
            $deadlineAt = (new \DateTimeImmutable())
                ->modify('+' . (int)$firstLine['deadline_days'] . ' days');
        }

        $currentUserId = $this->userService->getCurrentUserId();
        $oldLineId = $request['current_line_id'];

        $requestsModel->update([
            'status'          => AgreementRequestsModel::STATUS_PENDING,
            'current_line_id' => $firstLine['id'],
            'deadline_at'     => $deadlineAt,
        ], $requestId);

        // Record status change in history. This "→ pending" entry also marks the
        // start of the new approval round (see AgreementRequestActionsModel), so
        // approvers who acted in the previous round see the request again.
        $historyModel->recordStatusChange(
            $requestId,
            $previousStatus,
            AgreementRequestsModel::STATUS_PENDING,
            $currentUserId
        );

        // Record line change in history
        $historyModel->recordLineChange($requestId, $oldLineId, $firstLine['id'], $currentUserId);

        // Sync the source entity (e.g., vacation) back to pending
        $this->notifyEntityStatusChange($request, AgreementRequestsModel::STATUS_PENDING, $currentUserId);

        $this->notifyLineApprovers($firstLine['id'], $request);

        return [
            'success' => true,
            'status'  => 'resubmitted',
        ];
    }

    // =========================================================================
    // Request Data
    // =========================================================================

    /**
     * Get request with full details.
     */
    public function getRequestWithDetails(string $requestId): ?array
    {
        $requestsModel = new AgreementRequestsModel();
        $actionsModel = new AgreementRequestActionsModel();
        $historyModel = new AgreementRequestHistoryModel();
        $linesModel = new AgreementSchemeLinesModel();

        $request = $requestsModel->getItem($requestId);

        if (empty($request)) {
            return null;
        }

        // Get scheme details
        $request['scheme'] = $this->getSchemeWithDetails($request['scheme_id']);

        // Get all actions
        $request['actions'] = $actionsModel->getActionsForRequest($requestId);

        // Get history
        $request['history'] = $historyModel->getHistoryForRequest($requestId);

        // Get current line details
        if (!empty($request['current_line_id'])) {
            $request['current_line'] = $linesModel->getItem($request['current_line_id']);
        }

        // Decode cached data
        $request['cached_data'] = $requestsModel->getCachedData($request);

        return $request;
    }

    /**
     * Get history for a request.
     */
    public function getRequestHistory(string $requestId): array
    {
        $historyModel = new AgreementRequestHistoryModel();

        return $historyModel->getHistoryForRequest($requestId);
    }

    /**
     * Get requests for current user to approve.
     */
    public function getRequestsForCurrentUser(array $filters = []): array
    {
        $requestsModel = new AgreementRequestsModel();
        $userId = $this->userService->getCurrentUserId();

        return $requestsModel->getRequestsForApprover($userId, $filters);
    }

    /**
     * Get completed requests where current user acted as approver.
     */
    public function getCompletedRequestsForCurrentUser(array $filters = []): array
    {
        $requestsModel = new AgreementRequestsModel();
        $userId = $this->userService->getCurrentUserId();

        return $requestsModel->getCompletedRequestsForApprover($userId, $filters);
    }

    /**
     * Get request for an entity.
     */
    public function getRequestForEntity(string $entityType, string $entityId): ?array
    {
        $requestsModel = new AgreementRequestsModel();

        return $requestsModel->getRequestForEntity($entityType, $entityId);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Notify entity service about agreement status change.
     *
     * @param array  $request     Agreement request data
     * @param string $newStatus   New agreement status
     * @param string $actorUserId User who made the change
     */
    private function notifyEntityStatusChange(array $request, string $newStatus, string $actorUserId): void
    {
        $entityType = $request['entity_type'] ?? '';
        $entityId = $request['entity_id'] ?? '';

        if (empty($entityType) || empty($entityId)) {
            return;
        }

        switch ($entityType) {
            case AgreementSchemesModel::ENTITY_TYPE_VACATION:
                $vacationsService = VacationsService::getInstance();
                $vacationsService->updateVacationFromAgreement($entityId, $newStatus, $actorUserId);

                break;
                // Add more entity types here as needed
        }
    }

    /**
     * Notify approvers of a given line that a request is waiting for their review.
     *
     * @param string $lineId  Line whose approvers must be notified
     * @param array  $request Request record (must contain id, created_by, cached_title)
     */
    private function notifyLineApprovers(string $lineId, array $request): void
    {
        if (empty($lineId) || empty($request['id'])) {
            return;
        }

        $approversModel = new AgreementSchemeApproversModel();
        $approverIds = $approversModel->getApproverUserIds($lineId);

        if (empty($approverIds)) {
            return;
        }

        $userModel = new UserModel();

        // Resolve Done internal user IDs (oc_done_users_data.id, a hash) to Nextcloud user UIDs
        // (oc_done_users_data.user_id). Notifications are addressed to NC accounts.
        $approverRows = $userModel->getListByFilter(
            ['id' => ['IN', $approverIds, IQueryBuilder::PARAM_STR_ARRAY]],
            ['id', 'user_id'],
        );

        $ncUids = [];

        foreach ($approverRows as $row) {
            if (!empty($row['user_id'])) {
                $ncUids[] = (string)$row['user_id'];
            }
        }

        if (empty($ncUids)) {
            return;
        }

        $authorName = '';
        $authorId = $request['created_by'] ?? null;

        if (!empty($authorId)) {
            $authors = $userModel->getListForLink(['id' => $authorId], true, true);
            $authorName = $authors[$authorId]['name'] ?? $authorId;
        }

        $params = [
            'title'  => (string)($request['cached_title'] ?? ''),
            'author' => $authorName,
        ];

        // For vacation requests, attach the entity id so the Notifier can deep-link to the card.
        if (
            ($request['entity_type'] ?? '') === AgreementSchemesModel::ENTITY_TYPE_VACATION
            && !empty($request['entity_id'])
        ) {
            $params['vacation_id'] = (string)$request['entity_id'];
        }

        NotificationService::getInstance()->sendMany(
            $ncUids,
            Notifier::SUBJECT_AGREEMENT_PENDING,
            $params,
            'agreement_request',
            (string)$request['id'],
        );
    }

    /**
     * Check if a line is complete (all required approvals received).
     */
    private function isLineComplete(string $requestId, string $lineId, array $line): bool
    {
        $actionsModel = new AgreementRequestActionsModel();
        $approversModel = new AgreementSchemeApproversModel();

        $approvalCount = $actionsModel->countApprovalsForLine($requestId, $lineId);

        if (empty($line['require_all'])) {
            // One approval is enough
            return $approvalCount > 0;
        }

        // All approvers must approve
        $allApproverIds = $approversModel->getApproverUserIds($lineId);

        return $approvalCount >= \count($allApproverIds);
    }
}
