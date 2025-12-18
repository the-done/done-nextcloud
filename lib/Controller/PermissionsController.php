<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Controller;

use OCA\Done\Models\Dictionaries\GlobalRoles_Model;
use OCA\Done\Models\DynamicFields_Model;
use OCA\Done\Models\GlobalRoleActionRights_Model;
use OCA\Done\Models\PermissionsEntities_Model;
use OCA\Done\Models\RolesPermissions_Model;
use OCA\Done\Service\BaseService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class PermissionsController extends AdminController
{
    /**
     * Get role permission sections
     */
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function getGlobalRolesPermissions(IRequest $request): JSONResponse
    {
        $source = $request->getParam('source');
        $result = [];

        $gRolesPermitList = (new RolesPermissions_Model())->getPermissions($source);
        $gRolesList = (new GlobalRoles_Model())->getListByFilter();
        $permissionEntities = PermissionsEntities_Model::getPermissionsEntities($source);
        $dynFieldsList = (new DynamicFields_Model())->getListByFilter(
            !empty($source) ? ['source' => $source] : []
        );
        $dynFieldsList = BaseService::makeHash($dynFieldsList, 'source', true);

        foreach ($permissionEntities as $permissionEntity => $entityParams) {
            $modelName = $entityParams['model'];
            $dynFieldsBySource = $dynFieldsList[$permissionEntity] ?? [];

            if (!class_exists($modelName)) {
                continue;
            }

            $fields = (new $modelName())->fields;
            $result[$permissionEntity]['entity_name'] ??= $entityParams['entity_name'];
            $result[$permissionEntity]['fields'] ??= [];

            foreach ($gRolesList as $gRole) {
                $gRoleId = $gRole['id'];
                $gRoleName = $gRole['name'] ?? '';

                foreach ($fields as $fieldName => $params) {
                    $needPermission = $params['permission'] ?? false;

                    if ($needPermission) {
                        $id = $gRolesPermitList[$gRoleId][$permissionEntity][$fieldName]['slug'] ?? null;
                        $canView = $gRolesPermitList[$gRoleId][$permissionEntity][$fieldName]['values']['can_view'] ?? false;
                        $canRead = $gRolesPermitList[$gRoleId][$permissionEntity][$fieldName]['values']['can_read'] ?? false;
                        $canWrite = $gRolesPermitList[$gRoleId][$permissionEntity][$fieldName]['values']['can_write'] ?? false;
                        $canDelete = $gRolesPermitList[$gRoleId][$permissionEntity][$fieldName]['values']['can_delete'] ?? false;
                        $canViewAddInfo = $gRolesPermitList[$gRoleId][$permissionEntity][$fieldName]['values']['can_view_add_info'] ?? false;

                        $result[$permissionEntity]['fields'][$fieldName]['field_title'] = $params['title'];
                        $result[$permissionEntity]['fields'][$fieldName]['roles'][$gRoleId]['slug'] = $id;
                        $result[$permissionEntity]['fields'][$fieldName]['roles'][$gRoleId]['role_title'] = $gRoleName;
                        $result[$permissionEntity]['fields'][$fieldName]['roles'][$gRoleId]['actions'] = [
                            'can_view'          => (bool)$canView,
                            'can_read'          => (bool)$canRead,
                            'can_write'         => (bool)$canWrite,
                            'can_delete'        => (bool)$canDelete,
                            'can_view_add_info' => (bool)$canViewAddInfo,
                        ];
                    }
                }

                foreach ($dynFieldsBySource as $dynField) {
                    $dynFieldName = $dynField['title'];
                    $dynFieldId = $dynField['id'];

                    $id = $gRolesPermitList[$gRoleId][$permissionEntity][$dynFieldId]['slug'] ?? null;
                    $canView = $gRolesPermitList[$gRoleId][$permissionEntity][$dynFieldId]['values']['can_view'] ?? false;
                    $canRead = $gRolesPermitList[$gRoleId][$permissionEntity][$dynFieldId]['values']['can_read'] ?? false;
                    $canWrite = $gRolesPermitList[$gRoleId][$permissionEntity][$dynFieldId]['values']['can_write'] ?? false;
                    $canDelete = $gRolesPermitList[$gRoleId][$permissionEntity][$dynFieldId]['values']['can_delete'] ?? false;
                    $canViewAddInfo = $gRolesPermitList[$gRoleId][$permissionEntity][$dynFieldId]['values']['can_view_add_info'] ?? false;

                    $result[$permissionEntity]['fields'][$dynFieldId]['field_title'] = $dynFieldName;
                    $result[$permissionEntity]['fields'][$dynFieldId]['roles'][$gRoleId]['slug'] = $id;
                    $result[$permissionEntity]['fields'][$dynFieldId]['roles'][$gRoleId]['role_title'] = $gRoleName;
                    $result[$permissionEntity]['fields'][$dynFieldId]['roles'][$gRoleId]['actions'] = [
                        'can_view'          => (bool)$canView,
                        'can_read'          => (bool)$canRead,
                        'can_write'         => (bool)$canWrite,
                        'can_delete'        => (bool)$canDelete,
                        'can_view_add_info' => (bool)$canViewAddInfo,
                    ];
                }
            }
        }

        // Remove sections that don't need permissions
        foreach ($result as $permissionEntity => $params) {
            if (empty($params['fields'])) {
                unset($result[$permissionEntity]);
            }
        }

        return new JSONResponse(
            [
                'permissions' => $result,
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Save role permissions
     */
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function saveGlobalRolesPermissions(IRequest $request): JSONResponse
    {
        $input = $request->getParams();
        $rolesPermissionsModel = new RolesPermissions_Model();

        $data = [
            'global_role_id' => $input['role'],
            'entity_id'      => $input['entity'],
            'field'          => $input['field'],
        ];

        foreach (RolesPermissions_Model::RIGHTS_TYPES as $rightType) {
            if (isset($input[$rightType])) {
                $data[$rightType] = (bool)$input[$rightType];
            }
        }

        [$data, $errors] = $rolesPermissionsModel->validateData($data, true);

        if (!empty($errors)) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => $errors,
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $slug = $rolesPermissionsModel->addData($data);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Role rights saved successfully'),
                'slug'    => $slug,
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Edit role permissions
     */
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function editGlobalRolesPermissions(IRequest $request): JSONResponse
    {
        $input = $request->getParams();
        $rolesPermissionsModel = new RolesPermissions_Model();

        $slug = $input['slug'];

        $entityId = $rolesPermissionsModel->getItemIdBySlug($slug);

        if (empty($entityId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to save rights'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $data = [];

        foreach (RolesPermissions_Model::RIGHTS_TYPES as $rightType) {
            if (isset($input[$rightType])) {
                $data[$rightType] = (bool)$input[$rightType];
            }
        }

        [$data, $errors] = $rolesPermissionsModel->validateData($data);

        if (!empty($errors)) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => $errors,
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $rolesPermissionsModel->update($data, $entityId);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Role rights updated successfully'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Save global role action rights
     */
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function saveGlobalRoleActionRights(IRequest $request): JSONResponse
    {
        $globalRoleId = $request->getParam('global_role_id');
        $action = $request->getParam('action');
        $slug = $request->getParam('slug');
        $can = $request->getParam('can', false);

        if (empty($globalRoleId) || empty($action)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to save rights'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $globalRolesModel = new GlobalRoles_Model();
        $globalRoleActionRightsModel = new GlobalRoleActionRights_Model();

        if (empty($globalRolesModel->getItem($globalRoleId))) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('The specified role does not exist.'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $data = [
            'global_role_id' => $globalRoleId,
            'action'         => $action,
            'can'            => $can,
        ];

        $slug = !empty($slug)
            ? $globalRoleActionRightsModel->update($data, $slug)
            : $globalRoleActionRightsModel->addData($data);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Saved successfully'),
                'slug'    => $slug,
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Get global role action rights
     */
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function getGlobalRoleActionRights(): JSONResponse
    {
        $globalRolesModel = new GlobalRoles_Model();
        $globalRoleActionRightsModel = new GlobalRoleActionRights_Model();
        $globalRolesActionRights = $globalRoleActionRightsModel->getListByFilter();
        $globalRoles = $globalRolesModel->getListByFilter();
        $actionsNames = $globalRolesModel->getRolesActions();
        $globalRolesActionRightsPrepared = $rightsMatrix = $globalRolesPrepared = [];

        foreach ($globalRolesActionRights as $item) {
            $globalRoleId = (int)$item['global_role_id'];
            $action = $item['action'];
            $can = (bool)$item['can'];

            if (empty($globalRoleId) || empty($action)) {
                continue;
            }

            $globalRolesActionRightsPrepared[$action][$globalRoleId] = $can;
        }

        foreach ($actionsNames as $actionName => $actionDescription) {
            foreach ($globalRoles as $globalRole) {
                $globalRoleId = (int)$globalRole['id'];
                $rightsMatrix[$actionName]['actionDescription'] ??= $actionDescription;
                $rightsMatrix[$actionName]['rights'][$globalRoleId] = $globalRolesActionRightsPrepared[$actionName][$globalRoleId] ?? false;
                $globalRolesPrepared[$globalRoleId] ??= $globalRole['name'];
            }
        }

        return new JSONResponse(
            [
                'rightsMatrix' => $rightsMatrix,
                'globalRoles'  => $globalRolesPrepared,
            ],
            Http::STATUS_OK
        );
    }
}
