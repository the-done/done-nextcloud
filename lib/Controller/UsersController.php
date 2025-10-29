<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


declare(strict_types=1);

namespace OCA\Done\Controller;

use OCA\Done\Models\Dictionaries\Direction_Model;
use OCA\Done\Models\Dictionaries\GlobalRoles_Model;
use OCA\Done\Models\Dictionaries\Positions_Model;
use OCA\Done\Models\PermissionsEntities_Model;
use OCA\Done\Models\UsersGlobalRoles_Model;
use OCA\Done\Modules\Projects\Models\Project_Model;
use OCA\Done\Models\UsersRolesInProjects_Model;
use OCA\Done\Models\User_Model;
use OCA\Done\Models\UsersToDirections_Model;
use OCA\Done\Service\BaseService;
use OCP\AppFramework\Http;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;

class UsersController extends AdminController
{
    /**
     * Get user/users data
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getUsersData(IRequest $request): JSONResponse
    {
        $slug                         = $request->getParam('slug');
        $slugType                     = $request->getParam('slug_type');
        $userModel                    = new User_Model();
        $userModel->needPrepareFields = false;

        $userId = $userModel->getItemIdBySlug($slug);

        if (!empty($userId)) {
            $data = $userModel->prepareUserItem($userModel->getItem($userId));

            return new JSONResponse(
                $userModel->prepareDataBeforeSend($data, PermissionsEntities_Model::USER_ENTITY),
                Http::STATUS_OK
            );
        }

        return new JSONResponse(
            $userModel->prepareData($userModel->getListByFilter([], ['*'], ['full_name', 'ASC'])),
            Http::STATUS_OK
        );
    }

    /**
     * Get dynamic table for users
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getUsersTableData(): JSONResponse
    {
        $tableData = $this->tableService->getTableDataForEntity(
            new User_Model(),
            PermissionsEntities_Model::USER_ENTITY,
            $this->userService->getCurrentUserId()
        );

        return new JSONResponse($tableData, Http::STATUS_OK);
    }

    /**
     * Get users for adding to project
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getUsersForProject(IRequest $request): JSONResponse
    {
        $slug     = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');

        $projectModel = new Project_Model();
        $projectId    = $projectModel->getItemIdBySlug($slug);

        if (empty($projectId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to get users'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $userModel      = new User_Model();
        $userRolesModel = new UsersRolesInProjects_Model();

        $userRolesInProject  = $userRolesModel->getListByFilter(['project_id' => ['=', $projectId]]);
        $existUsersInProject = BaseService::getField($userRolesInProject, 'user_id');

        $filter = !empty($existUsersInProject) ?
            ['Id' => ['NOT IN', $existUsersInProject, IQueryBuilder::PARAM_STR_ARRAY]] :
            [];

        $usersLinked = $userModel->getLinkedList(
            $filter,
            ['id', 'name', 'middle_name', 'lastname', 'position_id'],
        );

        return new JSONResponse(
            $usersLinked,
            Http::STATUS_OK
        );
    }

    /**
     * Get user public data
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getUserPublicData(IRequest $request): JSONResponse
    {
        $slug           = $request->getParam('slug');
        $slugType       = $request->getParam('slug_type');
        $userModel      = new User_Model();
        $positionsModel = new Positions_Model();

        if (empty($slug)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('No data to get user'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $userId = $userModel->getItemIdBySlug($slug);

        $user = $userModel->getItemByFilter(
            ['id' => ['=', $userId]],
            ['id', 'name', 'middle_name', 'lastname', 'position_id']
        );

        if (empty($user)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('No data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $positionId = $user['position_id'];

        unset($user['position_id']);
        $positionsList = $positionId ?
            $positionsModel->getIndexedListByFilter('id', ['id' => $positionId]) :
            [];

        $user['pname'] = !empty($positionsList) ? ($positionsList[$positionId]['name'] ?? '') : '';

        return new JSONResponse(
            $user,
            Http::STATUS_OK
        );
    }

    /**
     * Get missing Nextcloud users
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getFreeNextcloudUsers(): JSONResponse
    {
        $userModel = new User_Model();

        $innerUsers      = $userModel->getListByFilter([], ['id', 'user_id']);
        $existUsersUuids = BaseService::getField($innerUsers, 'user_id');

        $filter = !empty($existUsersUuids) ? $existUsersUuids : [];

        return new JSONResponse(
            $this->baseService->getOuterUsers([], $filter),
            Http::STATUS_OK
        );
    }

    /**
     * Save user
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveUser(IRequest $request): JSONResponse
    {
        $data     = $request->getParam('data');
        $slug     = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');
        $isSave   = empty($slug);

        if (empty($data)) {
            return new JSONResponse(
                [
                    'error_type' => 'empty_data',
                    'message'    => $this->translateService->getTranslate('No data to save'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $userModel = new User_Model();
        [$data, $errors] = $userModel->validateData($data, $isSave, [], $userModel->getItemIdBySlug($slug));

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
            $userId = $userModel->getItemIdBySlug($slug);

            $userModel->update($data, $userId);
        } else {
            $userId = $userModel->addData($data);
            $slug   = $userModel->getItemSlug([], $userId);
            (new UsersGlobalRoles_Model())->addData(['user_id' => $userId, 'role_id' => GlobalRoles_Model::EMPLOYEE]);
        }

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Saved successfully'),
                'slug'    => $slug,
                'id'      => $userId,
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Delete user
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deleteUser(IRequest $request): JSONResponse
    {
        $slug     = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');

        $userModel = new User_Model();
        $userId    = $userModel->getItemIdBySlug($slug);

        if (empty($userId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('No data to delete'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $userModel->delete($userId);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('User deleted'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Get user statistics by user ID
     *
     * @param IRequest $request
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getStatisticsByUser(IRequest $request): JSONResponse
    {
        $dateFrom = $request->getParam('date_from');
        $dateTo   = $request->getParam('date_to');
        $slug     = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');
        $projects = $request->getParam('projects');

        $userModel = new User_Model();
        $userId    = $userModel->getItemIdBySlug($slug);

        if (empty($dateFrom) || empty($dateTo)) {
            return new JSONResponse(['message' => $this->translateService->getTranslate('Select interval')],
                Http::STATUS_BAD_REQUEST);
        }

        if (empty($userId)) {
            return new JSONResponse(['message' => $this->translateService->getTranslate('Select user')],
                Http::STATUS_BAD_REQUEST);
        }

        return new JSONResponse(
            $this->userService->getUserStatistics($dateFrom, $dateTo, $userId, $projects ?? []),
            Http::STATUS_OK
        );
    }

    /**
     * Get users in format [id: 1, name: 'user_name']
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getSimpleUsers(): JSONResponse
    {
        return new JSONResponse(
            (new User_Model())->getSimpleUsers(),
            Http::STATUS_OK
        );
    }

    /**
     * Add employee to direction
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function addUserToDirection(IRequest $request): JSONResponse
    {
        $input = $request->getParams();

        $userSlug          = $input['user']['slug'] ?? null;
        $userSlugType      = $input['user']['slug_type'] ?? null;
        $directionSlug     = $input['direction']['slug'] ?? null;
        $directionSlugType = $input['direction']['slug_type'] ?? null;

        $userModel              = new User_Model();
        $directionModel         = new Direction_Model();
        $usersToDirectionsModel = new UsersToDirections_Model();

        $userId      = $userModel->getItemIdBySlug($userSlug);
        $directionId = $directionModel->getItemIdBySlug($directionSlug);

        if (empty($userId) || empty($directionId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to create record'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $data = [
            'user_id'      => $userId,
            'direction_id' => $directionId,
        ];

        [$data, $errors] = $usersToDirectionsModel->validateData($data, true);

        if (!empty($errors)) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => $errors,
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $usersToDirectionsModel->addData($data);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Added successfully'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Edit employee record in direction
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function editUserInDirection(IRequest $request): JSONResponse
    {
        $input = $request->getParams();

        $slug              = $input['slug'] ?? null;
        $slugType          = $input['slug_type'] ?? null;
        $userSlug          = $input['user']['slug'] ?? null;
        $userSlugType      = $input['user']['slug_type'] ?? null;
        $directionSlug     = $input['direction']['slug'] ?? null;
        $directionSlugType = $input['direction']['slug_type'] ?? null;

        $userModel              = new User_Model();
        $directionModel         = new Direction_Model();
        $usersToDirectionsModel = new UsersToDirections_Model();

        $itemId      = $usersToDirectionsModel->getItemIdBySlug($slug);
        $userId      = $userModel->getItemIdBySlug($userSlug);
        $directionId = $directionModel->getItemIdBySlug($directionSlug);

        if (empty($itemId) || empty($userId) || empty($directionId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to edit'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $data = [
            'id'           => $itemId,
            'user_id'      => $userId,
            'direction_id' => $directionId,
        ];

        [$data, $errors] = $usersToDirectionsModel->validateData($data, true);

        if (!empty($errors)) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => $errors,
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $usersToDirectionsModel->update($data, $itemId);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Updated successfully'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Delete employee from direction
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deleteUserFromDirection(IRequest $request): JSONResponse
    {
        $slug = $request->getParam('slug');

        if (empty($slug)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('No data to delete'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        (new UsersToDirections_Model())->delete($slug);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Employee removed from direction'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Get team and user associations
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getUserInDirection(IRequest $request): JSONResponse
    {
        $input = $request->getParams();

        $userSlug     = $input['slug'] ?? null;
        $userSlugType = $input['slug_type'] ?? null;

        $userModel              = new User_Model();
        $usersToDirectionsModel = new UsersToDirections_Model();

        $userId = $userModel->getItemIdBySlug($userSlug);

        if (empty($userId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to get records'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            $usersToDirectionsModel->getLinkedList(['user_id' => $userId]),
            Http::STATUS_OK
        );
    }
}
