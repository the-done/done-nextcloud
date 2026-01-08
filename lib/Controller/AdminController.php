<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Controller;

use OCA\Done\Models\Dictionaries\GlobalRolesModel;
use OCA\Done\Models\PermissionsEntitiesModel;
use OCA\Done\Models\UserModel;
use OCA\Done\Models\UsersGlobalRolesModel;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class AdminController extends BaseController
{
    /**
     * Get Nextcloud users
     *
     * @return JSONResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getNextcloudUsersData(): JSONResponse
    {
        return new JSONResponse(
            $this->baseService->getOuterUsers(),
            Http::STATUS_OK
        );
    }

    /**
     * Add global role to user
     *
     * @return JSONResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function saveGlobalRoleToUser(IRequest $request): JSONResponse
    {
        $input = $request->getParams();

        $userSlug = $input['user']['slug'] ?? null;
        $roleSlug = $input['role']['slug'] ?? null;

        if (!$this->userService->canDoAction(GlobalRolesModel::CAN_EDIT_USERS_GLOBAL_ROLES)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate(
                        'Insufficient permissions to perform the operation'
                    ),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $userModel = new UserModel();
        $globalRolesModel = new GlobalRolesModel();

        $userId = $userModel->getItemIdBySlug($userSlug, true);
        $roleId = $globalRolesModel->getItemIdBySlug($roleSlug);

        if (empty($userId) || empty($roleId) || empty($globalRolesModel->getItem((string)$roleId))) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to save'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $usersGlobalRolesModel = new UsersGlobalRolesModel();

        $data = [
            'user_id' => $userId,
            'role_id' => $roleId,
        ];

        if (!empty($usersGlobalRolesModel->getItemByFilter(['user_id' => $userId, 'role_id' => $roleId]))) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while saving the user role'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        if (!(new UsersGlobalRolesModel())->addData($data)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while saving the user role'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Saved successfully'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Remove global role from user
     *
     * @return JSONResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function deleteGlobalRoleFromUser(IRequest $request): JSONResponse
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

        (new UsersGlobalRolesModel())->delete($slug);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Role deleted'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Get entity data
     *
     * @return JSONResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getDataToViewEntity(IRequest $request): JSONResponse
    {
        $source = $request->getParam('source');
        $slug = $request->getParam('slug');

        if (empty($source) || empty($slug) || !PermissionsEntitiesModel::entityExists($source)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            ['data' => $this->entitiesService->getDataToViewEntity($source, $slug)],
            Http::STATUS_OK
        );
    }
}
