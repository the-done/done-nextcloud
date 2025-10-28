<?php

declare(strict_types=1);

namespace OCA\Done\Modules\Projects\Controller;

use OCA\Done\Attribute\RequireRole;
use OCA\Done\Models\Dictionaries\GlobalRoles_Model;
use OCA\Done\Models\Dictionaries\Roles_Model;
use OCA\Done\Models\PermissionsEntities_Model;
use OCA\Done\Modules\Projects\Models\Project_Model;
use OCA\Done\Models\User_Model;
use OCA\Done\Models\UsersRolesInProjects_Model;
use OCA\Done\Modules\BaseModuleController;
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

        $this->tableService             = TableService::getInstance();
        $this->setAllowedRoles([GlobalRoles_Model::OFFICER, GlobalRoles_Model::HEAD]);
    }

    /**
     * Get dynamic table for projects
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRoles_Model::OFFICER, GlobalRoles_Model::HEAD])]
    public function getProjectsTableData(): JSONResponse
    {
        $tableData = $this->tableService->getTableDataForEntity(
            new Project_Model(),
            PermissionsEntities_Model::PROJECT_ENTITY,
            $this->userService->getCurrentUserId()
        );

        return new JSONResponse($tableData, Http::STATUS_OK);
    }

    /**
     * Get projects/project data
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRoles_Model::OFFICER])]
    public function getProjectsData(IRequest $request): JSONResponse
    {
        $slug         = $request->getParam('slug');
        $slugType     = $request->getParam('slug_type');
        $projectModel = new Project_Model();

        $projectId = $projectModel->getItemIdBySlug($slug);

        if (!empty($projectId)) {
            return new JSONResponse(
                $projectModel->prepareDataBeforeSend(
                    $projectModel->getProject($projectId),
                    PermissionsEntities_Model::PROJECT_ENTITY,
                ),
                Http::STATUS_OK
            );
        }

        return new JSONResponse(
            $projectModel->prepareDataBeforeSend(
                $projectModel->getProjects(),
                PermissionsEntities_Model::PROJECT_ENTITY,
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
    #[RequireRole([GlobalRoles_Model::OFFICER])]
    public function getProjectPublicData(IRequest $request): JSONResponse
    {
        $slug         = $request->getParam('slug');
        $slugType     = $request->getParam('slug_type');
        $projectModel = new Project_Model();

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
        $slug     = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');

        $projectModel = new Project_Model();
        $projectId    = $projectModel->getItemIdBySlug($slug);

        if (empty($projectId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to get roles'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            (new UsersRolesInProjects_Model())->getUserRoles($projectId),
            Http::STATUS_OK
        );
    }

    /**
     * Save project
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRoles_Model::OFFICER])]
    public function saveProject(IRequest $request): JSONResponse
    {
        $data         = $request->getParam('data');
        $slug         = $request->getParam('slug');
        $slugType     = $request->getParam('slug_type');
        $isSave       = empty($slug);
        $projectModel = new Project_Model();

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
            $slug      = $projectModel->getItemSlug([], $projectId);
            $message   = $this->translateService->getTranslate('Project created successfully');
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
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRoles_Model::OFFICER])]
    public function deleteProject(IRequest $request): JSONResponse
    {
        $slug         = $request->getParam('slug');
        $slugType     = $request->getParam('slug_type');
        $projectModel = new Project_Model();

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
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRoles_Model::OFFICER])]
    public function saveUserRole(IRequest $request): JSONResponse
    {
        $input = $request->getParams();

        $projectSlug     = $input['project']['slug'] ?? null;
        $projectSlugType = $input['project']['slug_type'] ?? null;
        $userSlug        = $input['user']['slug'] ?? null;
        $userSlugType    = $input['user']['slug_type'] ?? null;
        $roleSlug        = $input['role']['slug'] ?? null;
        $roleSlugType    = $input['role']['slug_type'] ?? null;

        $projectModel    = new Project_Model();
        $userModel       = new User_Model();
        $rolesModel      = new Roles_Model();
        $usersRolesModel = (new UsersRolesInProjects_Model());

        $projectId = $projectModel->getItemIdBySlug($projectSlug);
        $userId    = $userModel->getItemIdBySlug($userSlug);
        $roleId    = $rolesModel->getItemIdBySlug($roleSlug);

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
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRoles_Model::OFFICER])]
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

        (new UsersRolesInProjects_Model())->delete($slug);

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
    #[RequireRole([GlobalRoles_Model::OFFICER])]
    public function editUserRoleInProject(IRequest $request): JSONResponse
    {
        $input = $request->getParams();

        $slug            = $input['slug'] ?? null;
        $slugType        = $input['slug_type'] ?? null;
        $projectSlug     = $input['project']['slug'] ?? null;
        $projectSlugType = $input['project']['slug_type'] ?? null;
        $userSlug        = $input['user']['slug'] ?? null;
        $userSlugType    = $input['user']['slug_type'] ?? null;
        $roleSlug        = $input['role']['slug'] ?? null;
        $roleSlugType    = $input['role']['slug_type'] ?? null;

        $projectModel    = new Project_Model();
        $userModel       = new User_Model();
        $rolesModel      = new Roles_Model();
        $usersRolesModel = (new UsersRolesInProjects_Model());

        $itemId    = $usersRolesModel->getItemIdBySlug($slug);
        $projectId = $projectModel->getItemIdBySlug($projectSlug);
        $userId    = $userModel->getItemIdBySlug($userSlug);
        $roleId    = $rolesModel->getItemIdBySlug($roleSlug);

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