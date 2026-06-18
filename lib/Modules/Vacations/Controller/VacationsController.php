<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Modules\Vacations\Controller;

use OCA\Done\Attribute\RequireRole;
use OCA\Done\Models\Dictionaries\GlobalRolesModel;
use OCA\Done\Models\PermissionsEntitiesModel;
use OCA\Done\Models\UserModel;
use OCA\Done\Modules\Agreement\Model\AgreementSchemesModel;
use OCA\Done\Modules\Agreement\Service\AgreementService;
use OCA\Done\Modules\BaseModuleController;
use OCA\Done\Modules\Projects\Models\ProjectModel;
use OCA\Done\Modules\Vacations\Model\VacationsModel;
use OCA\Done\Modules\Vacations\Model\VacationsReportModel;
use OCA\Done\Modules\Vacations\Model\VacationsTypeSettingsModel;
use OCA\Done\Modules\Vacations\Model\VacationsTypesModel;
use OCA\Done\Modules\Vacations\Service\VacationsService;
use OCA\Done\Service\TableService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IRequest;

class VacationsController extends BaseModuleController
{
    public string $moduleName = 'vacations';
    private VacationsService $vacationsService;
    private TableService $tableService;

    /**
     * @param mixed $appName
     */
    public function __construct($appName, IRequest $request)
    {
        parent::__construct($appName, $request);
        $this->vacationsService = VacationsService::getInstance();
        $this->tableService = TableService::getInstance();
        $this->setAllowedRoles(['ALL']);
    }

    // =========================================================================
    // Vacation Requests CRUD
    // =========================================================================

    /**
     * Get vacations list for table view
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getVacationsTableData')]
    public function getVacationsTableData(IRequest $request): JSONResponse
    {
        try {
            $dateFrom = $request->getParam('date_from', date('Y-01-01'));
            $dateTo = $request->getParam('date_to', date('Y-12-31'));
            $employeeIds = $request->getParam('employee_ids');
            $projectIds = $request->getParam('project_ids');
            $vacationTypeIds = $request->getParam('vacation_type_ids');
            $vacationStatus = $request->getParam('vacation_status');

            $vacations = $this->vacationsService->getVacationsTableData(
                $dateFrom,
                $dateTo,
                !empty($employeeIds) ? (array)$employeeIds : null,
                !empty($projectIds) ? (array)$projectIds : null,
                !empty($vacationTypeIds) ? (array)$vacationTypeIds : null,
                !empty($vacationStatus) ? (string)$vacationStatus : null,
            );

            return $this->formatModuleResponse($vacations);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get vacations for Gantt chart (approvers only)
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getVacationsForGantt')]
    public function getVacationsForGantt(IRequest $request): JSONResponse
    {
        try {
            $dateFrom = $request->getParam('date_from', date('Y-01-01'));
            $dateTo = $request->getParam('date_to', date('Y-12-31'));
            $employeeIds = $request->getParam('employee_ids');
            $projectIds = $request->getParam('project_ids');
            $vacationTypeIds = $request->getParam('vacation_type_ids');
            $vacationStatus = $request->getParam('vacation_status');

            $data = $this->vacationsService->getVacationsForGantt(
                $dateFrom,
                $dateTo,
                !empty($employeeIds) ? (array)$employeeIds : null,
                !empty($projectIds) ? (array)$projectIds : null,
                !empty($vacationTypeIds) ? (array)$vacationTypeIds : null,
                !empty($vacationStatus) ? (string)$vacationStatus : null,
            );

            return $this->formatModuleResponse($data);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Add new vacation request
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/addVacation')]
    public function addVacation(IRequest $request): JSONResponse
    {
        try {
            $data = $request->getParam('data', []);
            $vacationsModel = new VacationsModel();

            // Validate but ignore created_by (will be set by service)
            [$data, $errors] = $vacationsModel->validateData($data, true, ['created_by']);

            if (!empty($errors)) {
                return $this->formatModuleResponse([
                    'error_type' => 'validation',
                    'errors'     => $errors,
                ], Http::STATUS_BAD_REQUEST);
            }

            if (!AgreementService::getInstance()->hasActiveSchemeForEntityType(AgreementSchemesModel::ENTITY_TYPE_VACATION)) {
                return $this->formatModuleResponse([
                    'error' => 'No agreement scheme configured for vacations. Please add one in the Agreement module before creating vacation requests.',
                ], Http::STATUS_BAD_REQUEST);
            }

            $vacationId = $this->vacationsService->createVacation($data);

            if (empty($vacationId)) {
                return $this->formatModuleResponse([
                    'error' => 'Error creating vacation',
                ], Http::STATUS_INTERNAL_SERVER_ERROR);
            }

            return $this->formatModuleResponse([
                'message' => 'Vacation successfully created',
                'slug'    => $vacationId,
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Edit vacation request
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/editVacation')]
    public function editVacation(IRequest $request): JSONResponse
    {
        try {
            $data = $request->getParam('data', []);
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Vacation slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $vacationsModel = new VacationsModel();
            $vacationId = $vacationsModel->getItemIdBySlug($slug);
            $existingVacation = $vacationsModel->getItemByFilter(['id' => $vacationId]);

            if (empty($existingVacation)) {
                return $this->formatModuleResponse([
                    'error' => 'Vacation not found',
                ], Http::STATUS_NOT_FOUND);
            }

            $currentUserId = $this->getCurrentUserId();
            $isOwner = $currentUserId !== null
                && (
                    $existingVacation['created_by'] === $currentUserId
                    || $existingVacation['user_id'] === $currentUserId
                );

            if (!$isOwner) {
                return $this->formatModuleResponse([
                    'error' => 'You are not allowed to edit this vacation',
                ], Http::STATUS_FORBIDDEN);
            }

            $previousStatus = $existingVacation['status_id'] ?? '';

            // Approved requests can be edited too — saving restarts the approval
            // flow (handled in refreshAgreementRequestForVacation), same as returned.
            if (!\in_array($previousStatus, [
                VacationsModel::STATUS_PENDING,
                VacationsModel::STATUS_RETURNED,
                VacationsModel::STATUS_APPROVED,
            ], true)) {
                return $this->formatModuleResponse([
                    'error' => 'Vacation cannot be edited in its current status',
                ], Http::STATUS_BAD_REQUEST);
            }

            [$data, $errors] = $vacationsModel->validateData($data, false, ['created_by']);

            if (!empty($errors)) {
                return $this->formatModuleResponse([
                    'error_type' => 'validation',
                    'errors'     => $errors,
                ], Http::STATUS_BAD_REQUEST);
            }

            $vacationsModel->update($data, $vacationId);

            // Refresh agreement request cache from new data, and if it was returned for
            // revision, automatically resubmit it to start the approval flow over.
            $updatedVacation = $vacationsModel->getItem($vacationId) ?? array_merge($existingVacation, $data);
            $this->vacationsService->refreshAgreementRequestForVacation($vacationId, $updatedVacation);

            return $this->formatModuleResponse([
                'message' => 'Vacation successfully updated',
                'slug'    => $vacationId,
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Cancel vacation request (owner action). The row is kept for history;
     * status_id changes to 'canceled' and the linked agreement request is canceled.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/cancelVacation')]
    public function cancelVacation(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Vacation slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $vacationsModel = new VacationsModel();
            $vacationId = $vacationsModel->getItemIdBySlug($slug);
            $existingVacation = $vacationsModel->getItemByFilter(['id' => $vacationId]);

            if (empty($existingVacation)) {
                return $this->formatModuleResponse([
                    'error' => 'Vacation not found',
                ], Http::STATUS_NOT_FOUND);
            }

            $result = $this->vacationsService->cancelVacation($vacationId);

            if (empty($result['success'])) {
                return $this->formatModuleResponse([
                    'error' => $result['error'] ?? 'Failed to cancel vacation',
                ], Http::STATUS_BAD_REQUEST);
            }

            return $this->formatModuleResponse([
                'message' => 'Vacation successfully canceled',
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    // =========================================================================
    // History
    // =========================================================================

    /**
     * Get a single vacation enriched for the request card view
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getVacation')]
    public function getVacation(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Vacation slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $vacationsModel = new VacationsModel();
            $vacationId = $vacationsModel->getItemIdBySlug($slug);

            $data = $vacationId !== null ? $this->vacationsService->getVacationCard($vacationId) : null;

            if (empty($data)) {
                return $this->formatModuleResponse([
                    'error' => 'Vacation not found',
                ], Http::STATUS_NOT_FOUND);
            }

            return $this->formatModuleResponse($data);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get vacation request history
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getVacationHistory')]
    public function getVacationHistory(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Vacation slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $vacationsModel = new VacationsModel();
            $vacationId = $vacationsModel->getItemIdBySlug($slug);

            if (empty($vacationId)) {
                return $this->formatModuleResponse([
                    'error' => 'Vacation not found',
                ], Http::STATUS_NOT_FOUND);
            }

            $history = $this->vacationsService->getVacationHistory($vacationId);

            return $this->formatModuleResponse($history);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    // =========================================================================
    // Balance Management
    // =========================================================================

    /**
     * Get upcoming approved paid leave vacations for the current user.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getUpcomingPaidVacations')]
    public function getUpcomingPaidVacations(IRequest $request): JSONResponse
    {
        try {
            $vacations = $this->vacationsService->getUpcomingPaidVacations();

            return $this->formatModuleResponse($vacations);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get remaining days for current user (employee view)
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getRemainingDays')]
    public function getRemainingDays(IRequest $request): JSONResponse
    {
        try {
            $year = $request->getParam('year');
            $userId = $request->getParam('user_id'); // Optional, for HR to view other employees

            $year = $year !== null ? (int)$year : null;

            $remaining = $this->vacationsService->getRemainingForEmployee($year, $userId);

            return $this->formatModuleResponse($remaining);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Set individual balance for an employee (HR function)
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/setEmployeeBalance')]
    public function setEmployeeBalance(IRequest $request): JSONResponse
    {
        try {
            $userId = $request->getParam('user_id');
            $typeId = $request->getParam('type_id');
            $year = $request->getParam('year');
            $daysTotal = $request->getParam('days_total');
            $comment = $request->getParam('comment');

            if (empty($userId) || empty($typeId) || $year === null || $daysTotal === null) {
                return $this->formatModuleResponse([
                    'error' => 'Missing required parameters: user_id, type_id, year, days_total',
                ], Http::STATUS_BAD_REQUEST);
            }

            $balanceId = $this->vacationsService->setEmployeeBalance(
                $userId,
                $typeId,
                (int)$year,
                (int)$daysTotal,
                $comment
            );

            return $this->formatModuleResponse([
                'message' => 'Balance successfully updated',
                'slug'    => $balanceId,
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get all balances for employees (HR view)
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getEmployeeBalances')]
    public function getEmployeeBalances(IRequest $request): JSONResponse
    {
        try {
            $userIds = $request->getParam('user_ids');
            $year = $request->getParam('year');

            if (empty($userIds)) {
                return $this->formatModuleResponse([
                    'error' => 'Missing required parameter: user_ids',
                ], Http::STATUS_BAD_REQUEST);
            }

            $year = $year !== null ? (int)$year : null;

            $balances = $this->vacationsService->getRemainingForEmployees($year, (array)$userIds);

            return $this->formatModuleResponse($balances);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    // =========================================================================
    // Vacation Coefficients
    // =========================================================================

    /**
     * List vacation coefficient records (HR view)
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getCoefficients')]
    public function getCoefficients(IRequest $request): JSONResponse
    {
        try {
            $userId = $request->getParam('user_id');
            $typeId = $request->getParam('type_id');

            $list = $this->vacationsService->listCoefficients(
                !empty($userId) ? (string)$userId : null,
                !empty($typeId) ? (string)$typeId : null,
            );

            return $this->formatModuleResponse($list);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Create a coefficient record
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/createCoefficient')]
    public function createCoefficient(IRequest $request): JSONResponse
    {
        try {
            $userId = $request->getParam('user_id');
            $typeId = $request->getParam('type_id');
            $value = $request->getParam('value');
            $effectiveFrom = $request->getParam('effective_from');
            $effectiveTo = $request->getParam('effective_to');
            $comment = $request->getParam('comment');

            if (empty($userId) || empty($typeId) || $value === null || empty($effectiveFrom)) {
                return $this->formatModuleResponse([
                    'error' => 'Missing required parameters: user_id, type_id, value, effective_from',
                ], Http::STATUS_BAD_REQUEST);
            }

            $newId = $this->vacationsService->createCoefficient(
                (string)$userId,
                (string)$typeId,
                (float)$value,
                (string)$effectiveFrom,
                !empty($effectiveTo) ? (string)$effectiveTo : null,
                $comment !== null ? (string)$comment : null,
            );

            return $this->formatModuleResponse([
                'message' => 'Coefficient successfully created',
                'slug'    => $newId,
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Update a coefficient record
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/updateCoefficient')]
    public function updateCoefficient(IRequest $request): JSONResponse
    {
        try {
            $id = $request->getParam('id');
            $data = $request->getParam('data', []);

            if (empty($id)) {
                return $this->formatModuleResponse([
                    'error' => 'Missing required parameter: id',
                ], Http::STATUS_BAD_REQUEST);
            }

            $this->vacationsService->updateCoefficient((string)$id, (array)$data);

            return $this->formatModuleResponse([
                'message' => 'Coefficient successfully updated',
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Delete a coefficient record
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/deleteCoefficient')]
    public function deleteCoefficient(IRequest $request): JSONResponse
    {
        try {
            $id = $request->getParam('id');

            if (empty($id)) {
                return $this->formatModuleResponse([
                    'error' => 'Missing required parameter: id',
                ], Http::STATUS_BAD_REQUEST);
            }

            $this->vacationsService->deleteCoefficient((string)$id);

            return $this->formatModuleResponse([
                'message' => 'Coefficient successfully deleted',
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    // =========================================================================
    // Vacation Types CRUD
    // =========================================================================

    /**
     * Get all vacation types
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getVacationTypes')]
    public function getVacationTypes(IRequest $request): JSONResponse
    {
        try {
            $types = $this->vacationsService->getVacationTypes();

            return $this->formatModuleResponse($types);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get vacation statuses
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getVacationStatuses')]
    public function getVacationStatuses(IRequest $request): JSONResponse
    {
        try {
            $statuses = $this->vacationsService->getVacationStatuses();

            return $this->formatModuleResponse($statuses);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Add new vacation type
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/addVacationType')]
    public function addVacationType(IRequest $request): JSONResponse
    {
        try {
            $data = $request->getParam('data', []);
            $vacationsTypesModel = new VacationsTypesModel();

            [$data, $errors] = $vacationsTypesModel->validateData($data, true);

            if (!empty($errors)) {
                return $this->formatModuleResponse([
                    'error_type' => 'validation',
                    'errors'     => $errors,
                ], Http::STATUS_BAD_REQUEST);
            }

            $vacationTypeId = $vacationsTypesModel->addData($data);

            if (empty($vacationTypeId)) {
                return $this->formatModuleResponse([
                    'error' => 'Error creating vacation type',
                ], Http::STATUS_INTERNAL_SERVER_ERROR);
            }

            return $this->formatModuleResponse([
                'message' => 'Vacation type successfully created',
                'slug'    => $vacationTypeId,
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Edit vacation type
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/editVacationType')]
    public function editVacationType(IRequest $request): JSONResponse
    {
        try {
            $data = $request->getParam('data', []);
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Vacation type slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $vacationsTypesModel = new VacationsTypesModel();
            $vacationTypeId = $vacationsTypesModel->getItemIdBySlug($slug);
            $existingVacationType = $vacationsTypesModel->getItemByFilter(['id' => $vacationTypeId]);

            if (empty($existingVacationType)) {
                return $this->formatModuleResponse([
                    'error' => 'Vacation type not found',
                ], Http::STATUS_NOT_FOUND);
            }

            [$data, $errors] = $vacationsTypesModel->validateData($data);

            if (!empty($errors)) {
                return $this->formatModuleResponse([
                    'error_type' => 'validation',
                    'errors'     => $errors,
                ], Http::STATUS_BAD_REQUEST);
            }

            $vacationsTypesModel->update($data, $vacationTypeId);

            return $this->formatModuleResponse([
                'message' => 'Vacation type successfully updated',
                'slug'    => $vacationTypeId,
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Delete vacation type (soft delete)
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/deleteVacationType')]
    public function deleteVacationType(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Vacation type slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $vacationsTypesModel = new VacationsTypesModel();
            $vacationTypeId = $vacationsTypesModel->getItemIdBySlug($slug);
            $existingVacationType = $vacationsTypesModel->getItemByFilter(['id' => $vacationTypeId]);

            if (empty($existingVacationType)) {
                return $this->formatModuleResponse([
                    'error' => 'Vacation type not found',
                ], Http::STATUS_NOT_FOUND);
            }

            $vacationsTypesModel->delete($vacationTypeId);

            return $this->formatModuleResponse([
                'message' => 'Vacation type successfully deleted',
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    // =========================================================================
    // Vacation Type Settings CRUD
    // =========================================================================

    /**
     * Get vacation type settings (default limits)
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getVacationTypeSettings')]
    public function getVacationTypeSettings(IRequest $request): JSONResponse
    {
        try {
            $typeId = $request->getParam('type_id');
            $settingsModel = new VacationsTypeSettingsModel();

            $filter = [];

            if (!empty($typeId)) {
                $filter['type_id'] = $typeId;
            }

            $settings = $settingsModel->getListByFilter($filter);

            return $this->formatModuleResponse($settings);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Add or update vacation type settings
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/setVacationTypeSetting')]
    public function setVacationTypeSetting(IRequest $request): JSONResponse
    {
        try {
            $typeId = $request->getParam('type_id');
            $value = $request->getParam('value');
            $displayWarning = $request->getParam('display_warning', false);

            if (empty($typeId) || $value === null) {
                return $this->formatModuleResponse([
                    'error' => 'Missing required parameters: type_id, value',
                ], Http::STATUS_BAD_REQUEST);
            }

            $settingsModel = new VacationsTypeSettingsModel();

            $data = [
                'type_id'         => $typeId,
                'value'           => (int)$value,
                'display_warning' => (bool)$displayWarning,
            ];

            $settingId = $settingsModel->upsertByFilter($data, ['type_id' => $typeId]);

            return $this->formatModuleResponse([
                'message' => 'Vacation type setting successfully saved',
                'slug'    => $settingId,
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Delete vacation type setting
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/deleteVacationTypeSetting')]
    public function deleteVacationTypeSetting(IRequest $request): JSONResponse
    {
        try {
            $slug = $request->getParam('slug');

            if (empty($slug)) {
                return $this->formatModuleResponse([
                    'error' => 'Setting slug not specified',
                ], Http::STATUS_BAD_REQUEST);
            }

            $settingsModel = new VacationsTypeSettingsModel();
            $settingId = $settingsModel->getItemIdBySlug($slug);
            $existingSetting = $settingsModel->getItemByFilter(['id' => $settingId]);

            if (empty($existingSetting)) {
                return $this->formatModuleResponse([
                    'error' => 'Setting not found',
                ], Http::STATUS_NOT_FOUND);
            }

            $settingsModel->delete($settingId);

            return $this->formatModuleResponse([
                'message' => 'Vacation type setting successfully deleted',
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get projects data for vacations module
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    public function getProjectsOptionsForVacations(): JSONResponse
    {
        $filter = [];

        $projectModel = new ProjectModel();

        $currentUserId = $this->userService->getCurrentUserId();
        $isHeadInfo = $this->userService->isHeadAndGetProjects();

        if ($isHeadInfo['isHead']) {
            $filter['id'] = ['IN', $isHeadInfo['projectsIds'], IQueryBuilder::PARAM_STR_ARRAY];
        }

        return new JSONResponse(
            $projectModel->getListByFilter($filter, ['id', 'name'], ['name', 'ASC'], [], true),
            Http::STATUS_OK
        );
    }

    /**
     * Get all projects (id + name) for the vacations report.
     *
     * Unlike getProjectsOptionsForVacations, this is not head-scoped: the report
     * shows every employee's projects, so badges need the full project list to
     * resolve names to ids for linking. Gated by the report permission.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getProjectsOptionsForVacationsReport')]
    public function getProjectsOptionsForVacationsReport(): JSONResponse
    {
        if (!$this->userService->canDoAction(GlobalRolesModel::CAN_READ_VACATIONS_REPORT)) {
            return new JSONResponse(
                ['message' => $this->translateService->getTranslate('Access denied')],
                Http::STATUS_FORBIDDEN
            );
        }

        $projectModel = new ProjectModel();

        return new JSONResponse(
            $projectModel->getListByFilter([], ['id', 'name'], ['name', 'ASC'], [], true),
            Http::STATUS_OK
        );
    }

    /**
     * Get employees data for vacations module
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    public function getEmployeesOptionsForVacations(): JSONResponse
    {
        $filter = [];

        $userModel = new UserModel();

        $currentUserId = $this->userService->getCurrentUserId();
        $isHeadInfo = $this->userService->isHeadAndGetEmployees();
        $isOfficer = $this->userService->isOfficer();
        $isAdmin = $this->userService->isAdmin();

        if ($isHeadInfo['isHead'] && !$isOfficer && !$isAdmin) {
            $filter['id'] = ['IN', $isHeadInfo['employeesIds'], IQueryBuilder::PARAM_STR_ARRAY];
        }

        $data = $userModel->getListForLink($filter, false, true);

        array_unshift($data, ['id' => 'all', 'name' => $this->translateService->getTranslate('All employees')]);

        return new JSONResponse(
            $data,
            Http::STATUS_OK
        );
    }

    // =========================================================================
    // CEO vacations report
    // =========================================================================

    /**
     * Get the configurable table for the CEO vacations report.
     * Flows through TableService so user-level column/filter/sort settings
     * persist exactly like for Projects and Staff.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getVacationsReportTableData')]
    public function getVacationsReportTableData(IRequest $request): JSONResponse
    {
        try {
            if (!$this->userService->canDoAction(GlobalRolesModel::CAN_READ_VACATIONS_REPORT)) {
                return new JSONResponse(
                    ['message' => $this->translateService->getTranslate('Access denied')],
                    Http::STATUS_FORBIDDEN
                );
            }

            $year = (int)$request->getParam('year', (int)date('Y'));
            $currentUserId = $this->userService->getCurrentUserId();
            $vacationsReportModel = new VacationsReportModel();

            $tableData = $this->tableService->getTableDataForEntity(
                $vacationsReportModel,
                PermissionsEntitiesModel::VACATION_REPORT_ENTITY,
                $currentUserId,
                false,
                ['year' => $year]
            );

            return new JSONResponse($tableData, Http::STATUS_OK);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * List every vacation of a single employee, newest-first, for the
     * "Details" side panel of the report.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getEmployeeVacationsList')]
    public function getEmployeeVacationsList(IRequest $request): JSONResponse
    {
        try {
            if (!$this->userService->canDoAction(GlobalRolesModel::CAN_READ_VACATIONS_REPORT)) {
                return new JSONResponse(
                    ['message' => $this->translateService->getTranslate('Access denied')],
                    Http::STATUS_FORBIDDEN
                );
            }

            $userId = (string)$request->getParam('user_id', '');

            if ($userId === '') {
                return new JSONResponse(
                    ['message' => $this->translateService->getTranslate('Employee identifier is required')],
                    Http::STATUS_BAD_REQUEST
                );
            }

            return $this->formatModuleResponse(
                $this->vacationsService->getEmployeeVacationsList($userId)
            );
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get the configurable separator used between "used" and "limit" in the
     * report's usage columns (defaults to "/").
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getReportSeparator')]
    public function getReportSeparator(): JSONResponse
    {
        try {
            return $this->formatModuleResponse([
                'separator' => $this->vacationsService->getReportSeparator(),
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Set the report usage separator. Empty value resets it to the default.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/setReportSeparator')]
    public function setReportSeparator(IRequest $request): JSONResponse
    {
        try {
            $separator = (string)$request->getParam('separator', '');
            $this->vacationsService->setReportSeparator($separator);

            return $this->formatModuleResponse([
                'message'   => 'Separator saved',
                'separator' => $this->vacationsService->getReportSeparator(),
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get the minimum length (in days) of a mandatory leave (defaults to 14).
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/getMandatoryLeaveMinDays')]
    public function getMandatoryLeaveMinDays(): JSONResponse
    {
        try {
            return $this->formatModuleResponse([
                'min_days' => $this->vacationsService->getMandatoryLeaveMinDays(),
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Set the minimum length (in days) of a mandatory leave. Non-positive
     * values reset it to the default.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole(['ALL'])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/vacations/setMandatoryLeaveMinDays')]
    public function setMandatoryLeaveMinDays(IRequest $request): JSONResponse
    {
        try {
            $minDays = (int)$request->getParam('min_days', 0);
            $this->vacationsService->setMandatoryLeaveMinDays($minDays);

            return $this->formatModuleResponse([
                'message'  => 'Mandatory leave minimum saved',
                'min_days' => $this->vacationsService->getMandatoryLeaveMinDays(),
            ]);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }
}
