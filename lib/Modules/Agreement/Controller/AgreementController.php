<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Modules\Agreement\Controller;

use OCA\Done\Attribute\RequireRole;
use OCA\Done\Models\Dictionaries\GlobalRolesModel;
use OCA\Done\Models\UserModel;
use OCA\Done\Modules\Agreement\Model\AgreementRequestActionsModel;
use OCA\Done\Modules\Agreement\Model\AgreementRequestHistoryModel;
use OCA\Done\Modules\Agreement\Model\AgreementRequestsModel;
use OCA\Done\Modules\Agreement\Model\AgreementSchemeApproversModel;
use OCA\Done\Modules\Agreement\Model\AgreementSchemeAssignmentsModel;
use OCA\Done\Modules\Agreement\Model\AgreementSchemeLinesModel;
use OCA\Done\Modules\Agreement\Model\AgreementSchemesModel;
use OCA\Done\Modules\Agreement\Service\AgreementService;
use OCA\Done\Modules\BaseModuleController;
use OCA\Done\Modules\Projects\Models\ProjectModel;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AgreementController extends BaseModuleController
{
    public string $moduleName = 'agreement';
    private AgreementService $agreementService;

    /**
     * @param mixed $appName
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct($appName, IRequest $request)
    {
        parent::__construct($appName, $request);
        $this->agreementService = AgreementService::getInstance();
        $this->setAllowedRoles(['ALL']);

        $isApprover = $this->userService->isAdmin()
            || $this->userService->isDoneAdmin()
            || (new AgreementSchemeApproversModel())->isApprover($this->userService->getCurrentUserId());

        $this->setUseGlobalAccessPermission(true);
        $this->setGlobalAccessPermission($isApprover);
    }

    // =========================================================================
    // Schemes CRUD
    // =========================================================================

    /**
     * Get all schemes.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getSchemes')]
    public function getSchemes(IRequest $request): JSONResponse
    {
        try {
            $entityType = $request->getParam('entity_type');
            $activeOnly = $request->getParam('active_only', true);

            $schemesModel = new AgreementSchemesModel();

            if ($entityType !== null) {
                $schemes = $schemesModel->getActiveSchemes($entityType);
            } else {
                $filter = $activeOnly ? ['is_active' => true] : [];
                $schemes = $schemesModel->getListByFilter($filter);
            }

            return $this->formatModuleResponse($schemes);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Check if any active scheme exists for a given entity type.
     * Used by entity modules to block record creation when no approval workflow
     * is configured and to surface a warning in the UI.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/hasActiveScheme')]
    public function hasActiveScheme(IRequest $request): JSONResponse
    {
        try {
            $entityType = $request->getParam('entity_type');

            if (empty($entityType)) {
                return $this->formatModuleResponse([
                    'error' => 'Entity type not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $hasScheme = $this->agreementService->hasActiveSchemeForEntityType($entityType);

            return $this->formatModuleResponse([
                'has_active_scheme' => $hasScheme,
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get scheme with details (lines and approvers).
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getScheme')]
    public function getScheme(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Scheme slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $schemesModel = new AgreementSchemesModel();
            $schemeId = $schemesModel->getItemIdBySlug($slug);
            $scheme = $this->agreementService->getSchemeWithDetails($schemeId);

            if (empty($scheme)) {
                return $this->formatModuleResponse([
                    'error' => 'Scheme not found',
                ], Http::STATUS_NOT_FOUND);
            }

            return $this->formatModuleResponse($scheme);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Create a new scheme.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/addScheme')]
    public function addScheme(IRequest $request): JSONResponse
    {
        try {
            $data = $request->getParam('data', []);
            $schemesModel = new AgreementSchemesModel();

            [$data, $errors] = $schemesModel->validateData($data, true, ['created_by']);

            if (!empty($errors)) {
                return $this->formatModuleResponse([
                    'error_type' => 'validation',
                    'errors'     => $errors,
                ], Http::STATUS_BAD_REQUEST);
            }

            $schemeId = $this->agreementService->createScheme($data);

            if (empty($schemeId)) {
                return $this->formatModuleResponse([
                    'error' => 'Error creating scheme',
                ], Http::STATUS_INTERNAL_SERVER_ERROR);
            }

            return $this->formatModuleResponse([
                'message' => 'Scheme successfully created',
                'slug'    => $schemeId,
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Edit a scheme.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/editScheme')]
    public function editScheme(IRequest $request): JSONResponse
    {
        try {
            $data = $request->getParam('data', []);
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Scheme slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $schemesModel = new AgreementSchemesModel();
            $schemeId = $schemesModel->getItemIdBySlug($slug);
            $existingScheme = $schemesModel->getItem($schemeId);

            if (empty($existingScheme)) {
                return $this->formatModuleResponse([
                    'error' => 'Scheme not found',
                ], Http::STATUS_NOT_FOUND);
            }

            [$data, $errors] = $schemesModel->validateData($data, false, ['created_by']);

            if (!empty($errors)) {
                return $this->formatModuleResponse([
                    'error_type' => 'validation',
                    'errors'     => $errors,
                ], Http::STATUS_BAD_REQUEST);
            }

            $schemesModel->update($data, $schemeId);

            return $this->formatModuleResponse([
                'message' => 'Scheme successfully updated',
                'slug'    => $schemeId,
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Delete a scheme (soft delete).
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/deleteScheme')]
    public function deleteScheme(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Scheme slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $schemesModel = new AgreementSchemesModel();
            $schemeId = $schemesModel->getItemIdBySlug($slug);
            $existingScheme = $schemesModel->getItem($schemeId);

            if (empty($existingScheme)) {
                return $this->formatModuleResponse([
                    'error' => 'Scheme not found',
                ], Http::STATUS_NOT_FOUND);
            }

            $this->agreementService->deleteSchemeWithRelated($schemeId);

            return $this->formatModuleResponse([
                'message' => 'Scheme successfully deleted',
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Set scheme as default for entity type.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/setDefaultScheme')]
    public function setDefaultScheme(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Scheme slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $schemesModel = new AgreementSchemesModel();
            $schemeId = $schemesModel->getItemIdBySlug($slug);

            $schemesModel->setAsDefault($schemeId);

            return $this->formatModuleResponse([
                'message' => 'Scheme set as default',
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    // =========================================================================
    // Scheme Lines CRUD
    // =========================================================================

    /**
     * Add a line to a scheme.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/addSchemeLine')]
    public function addSchemeLine(IRequest $request): JSONResponse
    {
        try {
            $schemeSlug = $request->getParam('scheme_slug');
            $data = $request->getParam('data', []);

            if (empty($schemeSlug)) {
                return $this->formatModuleResponse([
                    'error' => 'Scheme slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $schemesModel = new AgreementSchemesModel();
            $linesModel = new AgreementSchemeLinesModel();

            $schemeId = $schemesModel->getItemIdBySlug($schemeSlug);

            [$data, $errors] = $linesModel->validateData($data, true, ['scheme_id']);

            if (!empty($errors)) {
                return $this->formatModuleResponse([
                    'error_type' => 'validation',
                    'errors'     => $errors,
                ], Http::STATUS_BAD_REQUEST);
            }

            $lineId = $this->agreementService->addLineToScheme($schemeId, $data);

            if (empty($lineId)) {
                return $this->formatModuleResponse([
                    'error' => 'Error creating line',
                ], Http::STATUS_INTERNAL_SERVER_ERROR);
            }

            return $this->formatModuleResponse([
                'message' => 'Line successfully created',
                'slug'    => $lineId,
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Edit a scheme line.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/editSchemeLine')]
    public function editSchemeLine(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');
            $data = $request->getParam('data', []);

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Line slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $linesModel = new AgreementSchemeLinesModel();
            $lineId = $linesModel->getItemIdBySlug($slug);
            $existingLine = $linesModel->getItem($lineId);

            if (empty($existingLine)) {
                return $this->formatModuleResponse([
                    'error' => 'Line not found',
                ], Http::STATUS_NOT_FOUND);
            }

            [$data, $errors] = $linesModel->validateData($data, false, ['scheme_id']);

            if (!empty($errors)) {
                return $this->formatModuleResponse([
                    'error_type' => 'validation',
                    'errors'     => $errors,
                ], Http::STATUS_BAD_REQUEST);
            }

            $linesModel->update($data, $lineId);

            return $this->formatModuleResponse([
                'message' => 'Line successfully updated',
                'slug'    => $lineId,
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Delete a scheme line (soft delete).
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/deleteSchemeLine')]
    public function deleteSchemeLine(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Line slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $linesModel = new AgreementSchemeLinesModel();
            $lineId = $linesModel->getItemIdBySlug($slug);
            $existingLine = $linesModel->getItem($lineId);

            if (empty($existingLine)) {
                return $this->formatModuleResponse([
                    'error' => 'Line not found',
                ], Http::STATUS_NOT_FOUND);
            }

            $this->agreementService->deleteLineWithRelated($lineId);

            return $this->formatModuleResponse([
                'message' => 'Line successfully deleted',
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    // =========================================================================
    // Approvers Management
    // =========================================================================

    /**
     * Add approver to a line.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/addApprover')]
    public function addApprover(IRequest $request): JSONResponse
    {
        try {
            $lineSlug = $request->getParam('line_slug');
            $approverType = $request->getParam('approver_type');
            $userId = $request->getParam('user_id');
            $roleId = $request->getParam('role_id');

            if (empty($lineSlug) || empty($approverType)) {
                return $this->formatModuleResponse([
                    'error' => 'Missing required parameters: line_slug, approver_type',
                ], Http::STATUS_BAD_REQUEST);
            }

            $linesModel = new AgreementSchemeLinesModel();
            $lineId = $linesModel->getItemIdBySlug($lineSlug);

            $approverId = $this->agreementService->addApproverToLine(
                $lineId,
                $approverType,
                $userId,
                $roleId !== null ? (int)$roleId : null
            );

            if ($approverId === null) {
                return $this->formatModuleResponse([
                    'error' => 'Approver already exists or invalid parameters',
                ], Http::STATUS_BAD_REQUEST);
            }

            return $this->formatModuleResponse([
                'message' => 'Approver successfully added',
                'slug'    => $approverId,
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Remove approver from a line.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/removeApprover')]
    public function removeApprover(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Approver slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $approversModel = new AgreementSchemeApproversModel();
            $approverId = $approversModel->getItemIdBySlug($slug);
            $existingApprover = $approversModel->getItem($approverId);

            if (empty($existingApprover)) {
                return $this->formatModuleResponse([
                    'error' => 'Approver not found',
                ], Http::STATUS_NOT_FOUND);
            }

            $approversModel->delete($approverId);

            return $this->formatModuleResponse([
                'message' => 'Approver successfully removed',
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get users list for approver selection.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getUsersForApprover')]
    public function getUsersForApprover(IRequest $request): JSONResponse
    {
        try {
            $userModel = new UserModel();
            $users = $userModel->getListForLink([], false, true);

            return $this->formatModuleResponse($users);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get roles list for approver selection.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getRolesForApprover')]
    public function getRolesForApprover(IRequest $request): JSONResponse
    {
        try {
            $rolesModel = new GlobalRolesModel();
            $roles = $rolesModel->getListByFilter();

            return $this->formatModuleResponse($roles);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get projects list for the requests filter.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getProjectsForFilter')]
    public function getProjectsForFilter(IRequest $request): JSONResponse
    {
        try {
            $projectModel = new ProjectModel();
            $projects = $projectModel->getListByFilter([], ['id', 'name'], ['name', 'ASC']);

            return $this->formatModuleResponse($projects);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    // =========================================================================
    // Request Processing
    // =========================================================================

    /**
     * Get requests for current user to approve.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getMyRequests')]
    public function getMyRequests(IRequest $request): JSONResponse
    {
        try {
            $filters = $this->extractRequestFilters($request);
            $requests = $this->agreementService->getRequestsForCurrentUser($filters);

            return $this->formatModuleResponse($requests);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get completed requests where current user acted as approver.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getMyRequestsHistory')]
    public function getMyRequestsHistory(IRequest $request): JSONResponse
    {
        try {
            $filters = $this->extractRequestFilters($request);
            $requests = $this->agreementService->getCompletedRequestsForCurrentUser($filters);

            return $this->formatModuleResponse($requests);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Extract filter parameters from request.
     */
    private function extractRequestFilters(IRequest $request): array
    {
        $filters = [];

        $entityType = $request->getParam('entity_type');

        if (!empty($entityType)) {
            $filters['entity_type'] = $entityType;
        }

        $createdBy = $request->getParam('created_by');

        if (!empty($createdBy)) {
            $filters['created_by'] = \is_array($createdBy) ? $createdBy : [$createdBy];
        }

        $projectIds = $request->getParam('project_ids');

        if (!empty($projectIds)) {
            $filters['project_ids'] = \is_array($projectIds) ? $projectIds : [$projectIds];
        }

        $dateFrom = $request->getParam('date_from');

        if (!empty($dateFrom)) {
            $filters['date_from'] = $dateFrom;
        }

        $dateTo = $request->getParam('date_to');

        if (!empty($dateTo)) {
            $filters['date_to'] = $dateTo;
        }

        return $filters;
    }

    /**
     * Get request details.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getRequest')]
    public function getRequest(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Request slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $requestsModel = new AgreementRequestsModel();
            $requestId = $requestsModel->getItemIdBySlug($slug);
            $requestData = $this->agreementService->getRequestWithDetails($requestId);

            if (empty($requestData)) {
                return $this->formatModuleResponse([
                    'error' => 'Request not found',
                ], Http::STATUS_NOT_FOUND);
            }

            // Add user action rights
            $requestData['can_act'] = $this->agreementService->canUserActOnRequest($requestId);

            return $this->formatModuleResponse($requestData);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Approve a request.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/approveRequest')]
    public function approveRequest(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');
            $comment = $request->getParam('comment');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Request slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $requestsModel = new AgreementRequestsModel();
            $requestId = $requestsModel->getItemIdBySlug($slug);

            $result = $this->agreementService->approveRequest($requestId, $comment);

            if (!$result['success']) {
                return $this->formatModuleResponse([
                    'error' => $result['error'],
                ], Http::STATUS_BAD_REQUEST);
            }

            return $this->formatModuleResponse($result);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Reject a request.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/rejectRequest')]
    public function rejectRequest(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');
            $comment = $request->getParam('comment');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Request slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            if (empty($comment)) {
                return $this->formatModuleResponse([
                    'error' => 'Comment is required for rejection',
                ], Http::STATUS_BAD_REQUEST);
            }

            $requestsModel = new AgreementRequestsModel();
            $requestId = $requestsModel->getItemIdBySlug($slug);

            $result = $this->agreementService->rejectRequest($requestId, $comment);

            if (!$result['success']) {
                return $this->formatModuleResponse([
                    'error' => $result['error'],
                ], Http::STATUS_BAD_REQUEST);
            }

            return $this->formatModuleResponse($result);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Return a request for revision.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/returnRequest')]
    public function returnRequest(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');
            $comment = $request->getParam('comment');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Request slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            if (empty($comment)) {
                return $this->formatModuleResponse([
                    'error' => 'Comment is required for return',
                ], Http::STATUS_BAD_REQUEST);
            }

            $requestsModel = new AgreementRequestsModel();
            $requestId = $requestsModel->getItemIdBySlug($slug);

            $result = $this->agreementService->returnRequest($requestId, $comment);

            if (!$result['success']) {
                return $this->formatModuleResponse([
                    'error' => $result['error'],
                ], Http::STATUS_BAD_REQUEST);
            }

            return $this->formatModuleResponse($result);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Resubmit a returned request.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/resubmitRequest')]
    public function resubmitRequest(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Request slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $requestsModel = new AgreementRequestsModel();
            $requestId = $requestsModel->getItemIdBySlug($slug);

            $result = $this->agreementService->resubmitRequest($requestId);

            if (!$result['success']) {
                return $this->formatModuleResponse([
                    'error' => $result['error'],
                ], Http::STATUS_BAD_REQUEST);
            }

            return $this->formatModuleResponse($result);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get request history.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getRequestHistory')]
    public function getRequestHistory(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Request slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $requestsModel = new AgreementRequestsModel();
            $requestId = $requestsModel->getItemIdBySlug($slug);

            if (empty($requestId)) {
                return $this->formatModuleResponse([
                    'error' => 'Request not found',
                ], Http::STATUS_NOT_FOUND);
            }

            $history = $this->agreementService->getRequestHistory($requestId);

            return $this->formatModuleResponse($history);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    // =========================================================================
    // Options / Dictionaries
    // =========================================================================

    /**
     * Get all options for agreement module (request statuses, approver types, entity types, action types).
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getOptions')]
    public function getOptions(IRequest $request): JSONResponse
    {
        try {
            $requestsModel = new AgreementRequestsModel();
            $approversModel = new AgreementSchemeApproversModel();
            $schemesModel = new AgreementSchemesModel();
            $actionsModel = new AgreementRequestActionsModel();
            $assignmentsModel = new AgreementSchemeAssignmentsModel();
            $historyModel = new AgreementRequestHistoryModel();

            return $this->formatModuleResponse([
                'requestStatuses' => $requestsModel->getStatusOptions(),
                'approverTypes'   => $approversModel->getApproverTypeOptions(),
                'entityTypes'     => $schemesModel->getEntityTypeOptions(),
                'actionTypes'     => $actionsModel->getActionTypeOptions(),
                'subEntityTypes'  => $assignmentsModel->getSubEntityTypeOptions(),
                'eventTypes'      => $historyModel->getEventTypes(),
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get request status options.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getRequestStatuses')]
    public function getRequestStatuses(IRequest $request): JSONResponse
    {
        try {
            $requestsModel = new AgreementRequestsModel();

            return $this->formatModuleResponse($requestsModel->getStatusOptions());
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get approver type options.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getApproverTypes')]
    public function getApproverTypes(IRequest $request): JSONResponse
    {
        try {
            $approversModel = new AgreementSchemeApproversModel();

            return $this->formatModuleResponse($approversModel->getApproverTypeOptions());
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get entity type options.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getEntityTypes')]
    public function getEntityTypes(IRequest $request): JSONResponse
    {
        try {
            $schemesModel = new AgreementSchemesModel();

            return $this->formatModuleResponse($schemesModel->getEntityTypeOptions());
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get action type options.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getActionTypes')]
    public function getActionTypes(IRequest $request): JSONResponse
    {
        try {
            $actionsModel = new AgreementRequestActionsModel();

            return $this->formatModuleResponse($actionsModel->getActionTypeOptions());
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    // =========================================================================
    // Scheme Assignments
    // =========================================================================

    /**
     * Get all assignments for an entity type.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getAssignments')]
    public function getAssignments(IRequest $request): JSONResponse
    {
        try {
            $entityType = $request->getParam('entity_type');

            if (empty($entityType)) {
                return $this->formatModuleResponse([
                    'error' => 'Entity type not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $assignmentsModel = new AgreementSchemeAssignmentsModel();
            $assignments = $assignmentsModel->getEnrichedAssignments($entityType);

            return $this->formatModuleResponse($assignments);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get assignments for a specific scheme.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getSchemeAssignments')]
    public function getSchemeAssignments(IRequest $request): JSONResponse
    {
        try {
            $schemeSlug = $request->getParam('scheme_slug');

            if (empty($schemeSlug)) {
                return $this->formatModuleResponse([
                    'error' => 'Scheme slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $schemesModel = new AgreementSchemesModel();
            $schemeId = $schemesModel->getItemIdBySlug($schemeSlug);

            $assignmentsModel = new AgreementSchemeAssignmentsModel();
            $assignments = $assignmentsModel->getAssignmentsForScheme($schemeId);

            // Enrich with sub-entity names
            $userModel = new UserModel();
            $projectModel = new ProjectModel();

            foreach ($assignments as &$assignment) {
                switch ($assignment['sub_entity_type']) {
                    case AgreementSchemeAssignmentsModel::SUB_ENTITY_USER:
                        $user = $userModel->getListForLink(['id' => $assignment['sub_entity_id']])[0];
                        $assignment['sub_entity_name'] = $user['name'] ?? $user['user_id'] ?? $assignment['sub_entity_id'];
                        break;

                    case AgreementSchemeAssignmentsModel::SUB_ENTITY_PROJECT:
                        $project = $projectModel->getItem($assignment['sub_entity_id']);
                        $assignment['sub_entity_name'] = $project['name'] ?? $assignment['sub_entity_id'];
                        break;

                    default:
                        $assignment['sub_entity_name'] = $assignment['sub_entity_id'];
                }
            }

            return $this->formatModuleResponse($assignments);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Create or update a scheme assignment.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/addAssignment')]
    public function addAssignment(IRequest $request): JSONResponse
    {
        try {
            $schemeSlug = $request->getParam('scheme_slug');
            $entityType = $request->getParam('entity_type');
            $subEntityType = $request->getParam('sub_entity_type');
            $subEntityId = $request->getParam('sub_entity_id');

            if (empty($schemeSlug) || empty($entityType) || empty($subEntityType) || empty($subEntityId)) {
                return $this->formatModuleResponse([
                    'error' => 'Missing required parameters: scheme_slug, entity_type, sub_entity_type, sub_entity_id',
                ], Http::STATUS_BAD_REQUEST);
            }

            $schemesModel = new AgreementSchemesModel();
            $schemeId = $schemesModel->getItemIdBySlug($schemeSlug);

            $scheme = $schemesModel->getItem($schemeId);

            if (empty($scheme)) {
                return $this->formatModuleResponse([
                    'error' => 'Scheme not found',
                ], Http::STATUS_NOT_FOUND);
            }

            // Verify scheme entity type matches
            if ($scheme['entity_type'] !== $entityType) {
                return $this->formatModuleResponse([
                    'error' => 'Scheme entity type does not match assignment entity type',
                ], Http::STATUS_BAD_REQUEST);
            }

            $assignmentsModel = new AgreementSchemeAssignmentsModel();
            $currentUserId = $this->getCurrentUserID();

            $assignment = $assignmentsModel->createAssignment(
                $schemeId,
                $entityType,
                $subEntityType,
                $subEntityId,
                $currentUserId
            );

            if (empty($assignment)) {
                return $this->formatModuleResponse([
                    'error' => 'Error creating assignment',
                ], Http::STATUS_INTERNAL_SERVER_ERROR);
            }

            return $this->formatModuleResponse([
                'message' => 'Assignment successfully created',
                'slug'    => $assignment['id'],
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Delete a scheme assignment.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/deleteAssignment')]
    public function deleteAssignment(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Assignment slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $assignmentsModel = new AgreementSchemeAssignmentsModel();
            $assignmentId = $assignmentsModel->getItemIdBySlug($slug);
            $existingAssignment = $assignmentsModel->getItem($assignmentId);

            if (empty($existingAssignment)) {
                return $this->formatModuleResponse([
                    'error' => 'Assignment not found',
                ], Http::STATUS_NOT_FOUND);
            }

            $assignmentsModel->deleteAssignment($assignmentId);

            return $this->formatModuleResponse([
                'message' => 'Assignment successfully deleted',
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get sub-entity type options.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getSubEntityTypes')]
    public function getSubEntityTypes(IRequest $request): JSONResponse
    {
        try {
            $assignmentsModel = new AgreementSchemeAssignmentsModel();

            return $this->formatModuleResponse($assignmentsModel->getSubEntityTypeOptions());
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get available entities for assignment (users or projects based on sub_entity_type).
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/agreement/getEntitiesForAssignment')]
    public function getEntitiesForAssignment(IRequest $request): JSONResponse
    {
        try {
            $subEntityType = $request->getParam('sub_entity_type');

            if (empty($subEntityType)) {
                return $this->formatModuleResponse([
                    'error' => 'Sub-entity type not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            switch ($subEntityType) {
                case AgreementSchemeAssignmentsModel::SUB_ENTITY_USER:
                    $userModel = new UserModel();
                    $entities = $userModel->getListForLink([], false, true);
                    break;

                case AgreementSchemeAssignmentsModel::SUB_ENTITY_PROJECT:
                    $projectsModel = new ProjectModel();
                    $entities = $projectsModel->getListForLink();
                    break;

                default:
                    return $this->formatModuleResponse([
                        'error' => 'Unknown sub-entity type',
                    ], Http::STATUS_BAD_REQUEST);
            }

            return $this->formatModuleResponse($entities);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }
}
