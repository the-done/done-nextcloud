<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Modules\Projects\Controller;

use OCA\Done\Attribute\RequireRole;
use OCA\Done\Models\Dictionaries\GlobalRolesModel;
use OCA\Done\Models\Dictionaries\RolesModel;
use OCA\Done\Models\PermissionsEntitiesModel;
use OCA\Done\Models\UserModel;
use OCA\Done\Models\UsersRolesInProjectsModel;
use OCA\Done\Modules\BaseModuleController;
use OCA\Done\Modules\Projects\Models\ProjectModel;
use OCA\Done\Service\TableService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class ProjectsController extends BaseModuleController
{
    /** @var TableService */
    private TableService $tableService;

    public string $moduleName = 'projects';

    public function __construct($appName, IRequest $request)
    {
        parent::__construct($appName, $request);

        $this->tableService = TableService::getInstance();
        $this->setAllowedRoles([GlobalRolesModel::OFFICER, GlobalRolesModel::HEAD]);
    }

    /**
     * Get dynamic table for projects
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRolesModel::OFFICER, GlobalRolesModel::HEAD])]
    public function getProjectsTableData(): JSONResponse
    {
        $tableData = $this->tableService->getTableDataForEntity(
            new ProjectModel(),
            PermissionsEntitiesModel::PROJECT_ENTITY,
            $this->userService->getCurrentUserId()
        );

        return new JSONResponse($tableData, Http::STATUS_OK);
    }

    /**
     * Get projects/project data
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRolesModel::OFFICER])]
    public function getProjectsData(IRequest $request): JSONResponse
    {
        $slug = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');
        $projectModel = new ProjectModel();

        $projectId = $projectModel->getItemIdBySlug($slug);

        if (!empty($projectId)) {
            return new JSONResponse(
                $projectModel->prepareDataBeforeSend(
                    $projectModel->getProject($projectId),
                    PermissionsEntitiesModel::PROJECT_ENTITY,
                ),
                Http::STATUS_OK
            );
        }

        return new JSONResponse(
            $projectModel->prepareDataBeforeSend(
                $projectModel->getProjects(),
                PermissionsEntitiesModel::PROJECT_ENTITY,
                true
            ),
            Http::STATUS_OK
        );
    }

    /**
     * Get project public data
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRolesModel::OFFICER])]
    public function getProjectPublicData(IRequest $request): JSONResponse
    {
        $slug = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');
        $projectModel = new ProjectModel();

        $projectId = $projectModel->getItemIdBySlug($slug);

        if (empty($projectId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            $projectModel->getItemByFilter(['id' => $projectId], ['id', 'name']),
            Http::STATUS_OK
        );
    }

    /**
     * Get roles and users in project
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getUserRolesInProject(IRequest $request): JSONResponse
    {
        $slug = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');

        $projectModel = new ProjectModel();
        $projectId = $projectModel->getItemIdBySlug($slug);

        if (empty($projectId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to get roles'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            (new UsersRolesInProjectsModel())->getUserRoles($projectId),
            Http::STATUS_OK
        );
    }

    /**
     * Save project
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRolesModel::OFFICER])]
    public function saveProject(IRequest $request): JSONResponse
    {
        $data = $request->getParam('data');
        $slug = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');
        $isSave = empty($slug);
        $projectModel = new ProjectModel();

        [$data, $errors] = $projectModel->validateData($data, $isSave);

        if (!empty($errors)) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => $errors,
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        if (!$isSave) {
            $projectId = $projectModel->getItemIdBySlug($slug);
            $projectModel->update($data, $projectId);
            $message = $this->translateService->getTranslate('Project edited successfully');
        } else {
            $projectId = $projectModel->addData($data);
            $slug = $projectModel->getItemSlug([], $projectId);
            $message = $this->translateService->getTranslate('Project created successfully');
        }

        return new JSONResponse(
            [
                'message' => $message,
                'slug'    => $slug,
                'id'      => $projectId,
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Delete project
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRolesModel::OFFICER])]
    public function deleteProject(IRequest $request): JSONResponse
    {
        $slug = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');
        $projectModel = new ProjectModel();

        $projectId = $projectModel->getItemIdBySlug($slug);

        if (empty($projectId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('No data to delete'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $projectModel->delete($projectId);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Project deleted'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Save user role in project
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRolesModel::OFFICER])]
    public function saveUserRole(IRequest $request): JSONResponse
    {
        $input = $request->getParams();

        $projectSlug = $input['project']['slug'] ?? null;
        $projectSlugType = $input['project']['slug_type'] ?? null;
        $userSlug = $input['user']['slug'] ?? null;
        $userSlugType = $input['user']['slug_type'] ?? null;
        $roleSlug = $input['role']['slug'] ?? null;
        $roleSlugType = $input['role']['slug_type'] ?? null;

        $projectModel = new ProjectModel();
        $userModel = new UserModel();
        $rolesModel = new RolesModel();
        $usersRolesModel = (new UsersRolesInProjectsModel());

        $projectId = $projectModel->getItemIdBySlug($projectSlug);
        $userId = $userModel->getItemIdBySlug($userSlug);
        $roleId = $rolesModel->getItemIdBySlug($roleSlug);

        if (empty($projectId) || empty($userId) || empty($roleId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to save'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $data = [
            'project_id' => $projectId,
            'user_id'    => $userId,
            'role_id'    => $roleId,
        ];

        [$data, $errors] = $usersRolesModel->validateData($data, true);

        if (!empty($errors)) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => $errors,
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $usersRolesModel->addData($data);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Roles saved'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Delete user role in project
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRolesModel::OFFICER])]
    public function deleteUsersRoles(IRequest $request): JSONResponse
    {
        $slug = $request->getParam('slug');

        if (!$slug) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to delete'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        (new UsersRolesInProjectsModel())->delete($slug);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Role deleted'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Edit user record in project
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRolesModel::OFFICER])]
    public function editUserRoleInProject(IRequest $request): JSONResponse
    {
        $input = $request->getParams();

        $slug = $input['slug'] ?? null;
        $slugType = $input['slug_type'] ?? null;
        $projectSlug = $input['project']['slug'] ?? null;
        $projectSlugType = $input['project']['slug_type'] ?? null;
        $userSlug = $input['user']['slug'] ?? null;
        $userSlugType = $input['user']['slug_type'] ?? null;
        $roleSlug = $input['role']['slug'] ?? null;
        $roleSlugType = $input['role']['slug_type'] ?? null;

        $projectModel = new ProjectModel();
        $userModel = new UserModel();
        $rolesModel = new RolesModel();
        $usersRolesModel = (new UsersRolesInProjectsModel());

        $itemId = $usersRolesModel->getItemIdBySlug($slug);
        $projectId = $projectModel->getItemIdBySlug($projectSlug);
        $userId = $userModel->getItemIdBySlug($userSlug);
        $roleId = $rolesModel->getItemIdBySlug($roleSlug);

        if (empty($itemId) && (empty($projectId) && empty($userId) && empty($roleId))) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to edit'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $data = [
            'id' => $itemId,
        ];

        if (!empty($projectId)) {
            $data['project_id'] = $projectId;
        }

        if (!empty($userId)) {
            $data['user_id'] = $userId;
        }

        if (!empty($roleId)) {
            $data['role_id'] = $roleId;
        }

        [$data, $errors] = $usersRolesModel->validateData($data);

        if (!empty($errors)) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => $errors,
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $usersRolesModel->update($data, $itemId);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Updated successfully'),
            ],
            Http::STATUS_OK
        );
    }
}
