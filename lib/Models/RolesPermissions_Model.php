<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Models;

use OCA\Done\Models\Dictionaries\GlobalRoles_Model;
use OCA\Done\Service\BaseService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class RolesPermissions_Model.
 */
class RolesPermissions_Model extends Base_Model
{
    public string $table = 'done_g_roles_permit';
    public string $modelTitle = 'Global roles permissions';
    public string $modelName = 'rolesPermissionsModel';
    public string $dbTableComment = 'Access permissions for global roles to fields in various application sections.';

    protected array $hashFields = [
        'global_role_id',
        'entity_id',
        'field',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Unique identifier for a permission record',
        ],
        'global_role_id' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Global role ID',
            'link'       => GlobalRoles_Model::class,
            'required'   => true,
            'db_comment' => 'Global role identifier. References oc_done_global_roles.id',
        ],
        'entity_id' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Entity',
            'required'   => true,
            'db_comment' => 'Identifier of the application section. Possible values: 1 (Users), 2 (Projects), 3 (Teams), 4 (Payments)',
        ],
        'field' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Field name',
            'required'   => true,
            'db_comment' => 'Technical name of the column from the table indicated by `entity_id` (e.g., `name` from `oc_done_projects`)',
        ],
        'can_view' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Can view the field',
            'required'   => false,
            'db_comment' => 'Permission to view the field (1 - yes, 0 - no). If `can_view=1` and `can_read=0`, the user sees the field but not its value',
        ],
        'can_read' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Can read field value',
            'required'   => false,
            'db_comment' => 'Permission to read the field\'s value (1 - yes, 0 - no). Requires `can_view=1`',
        ],
        'can_write' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Can write field',
            'required'   => false,
            'db_comment' => 'Permission to change the field\'s value (1 - yes, 0 - no)',
        ],
        'can_delete' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Can delete field value',
            'required'   => false,
            'db_comment' => 'Permission to delete the field\'s value (1 - yes, 0 - no)',
        ],
        'can_view_add_info' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Can view additional field info',
            'required'   => false,
            'db_comment' => '[DEPRECATED] Permission to view additional information about the field (1 - yes, 0 - no)',
        ],
        'created_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC',
        ],
        'updated_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Updated at',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC',
        ],
    ];

    public const RIGHTS_TYPES = [
        'can_view',
        'can_read',
        'can_write',
        'can_delete',
        'can_view_add_info',
    ];

    /**
     * Get existing installed role permissions
     *
     * @param null|int $source
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPermissions(?int $source = null): array
    {
        $result = $filter = [];

        if (isset($source)) {
            $filter['entity_id'] = $source;
        }

        $permissions = $this->getListByFilter($filter);

        foreach ($permissions as $permission) {
            $roleId = $permission['global_role_id'];
            $entityId = $permission['entity_id'];
            $field = $permission['field'];
            $id = $permission['id'];

            $result[$roleId][$entityId][$field]['slug'] = $id;
            $result[$roleId][$entityId][$field]['values'] = [
                'can_view'          => $permission['can_view'] ?? false,
                'can_read'          => $permission['can_read'] ?? false,
                'can_write'         => $permission['can_write'] ?? false,
                'can_delete'        => $permission['can_delete'] ?? false,
                'can_view_add_info' => $permission['can_view_add_info'] ?? false,
            ];
        }

        return $result;
    }

    /**
     * @override
     */
    public function validateData(array $data, bool $save = false, array $ignoreFields = []): array
    {
        if (isset($data['can_read']) && $data['can_read']) {
            $data['can_view'] = true;
        }

        if (isset($data['can_write']) && $data['can_write']) {
            $data['can_view'] = $data['can_read'] = true;
        }

        if (isset($data['can_delete']) && $data['can_delete']) {
            $data['can_write'] = $data['can_view'] = $data['can_read'] = true;
        }

        return parent::validateData($data, $save, $ignoreFields);
    }

    /**
     * Get entity field permissions for role
     *
     * @param array<string, mixed> $filter
     *
     * @return array
     */
    public function getFieldsFullPermissions(array $filter): array
    {
        $permissionEntities = PermissionsEntities_Model::getPermissionsEntities();
        $permissions = $this->getListByFilter($filter);
        $permissions = BaseService::makeHash($permissions, 'entity_id', true);

        return $this->preparePermissions(
            $permissionEntities,
            $this->getExistPermissions($permissionEntities, $permissions)
        );
    }

    /**
     * Get entity field read permissions for role
     *
     * @param int                  $entityId
     * @param array<string, mixed> $filter
     *
     * @return array
     */
    public function getFieldsReadPermissions(int $entityId, array $filter): array
    {
        $result = [];
        $permissionEntities = PermissionsEntities_Model::getPermissionsEntities();
        $entity = $permissionEntities[$entityId];
        $modelName = $entity['model'];

        if (!class_exists($modelName)) {
            return [];
        }

        $model = new $modelName();
        $fields = $model->fields;
        $permissions = (new self())->getListByFilter($filter);

        foreach ($permissions as $permission) {
            $fieldName = $permission['field'];

            if (isset($result[$fieldName])) {
                $result[$fieldName] = $result[$fieldName] ?: (bool)$permission['can_read'];

                continue;
            }

            $result[$fieldName] = (bool)$permission['can_read'];
        }

        foreach ($fields as $fieldName => $params) {
            $needPermission = (bool)$params['permission'];

            if ($needPermission && !isset($result[$fieldName])) {
                $result[$fieldName] = false;
            } elseif (!$needPermission && !isset($result[$fieldName]) && !\in_array($fieldName, $this->excludedKeys)) {
                $result[$fieldName] = true;
            }
        }

        return $result;
    }

    /**
     * Get granted field permissions for roles
     *
     * @param array<int, mixed> $permissionEntities
     * @param array<int, mixed> $permissions
     *
     * @return array
     */
    public function getExistPermissions(array $permissionEntities, array $permissions): array
    {
        $result = [];

        foreach ($permissionEntities as $entityId => $permissionEntity) {
            $entitySlug = $permissionEntity['slug'];
            $modelName = $permissionEntity['model'];

            if (!class_exists($modelName)) {
                continue;
            }

            $permissionsForEntity = $permissions[$entityId] ?? [];

            foreach ($permissionsForEntity as $permission) {
                $fieldName = $permission['field'];

                if (isset($result[$entitySlug][$fieldName])) {
                    $canView = $result[$entitySlug][$fieldName]['can_view'] ?: $permission['can_view'];
                    $canRead = $result[$entitySlug][$fieldName]['can_read'] ?: $permission['can_read'];
                    $canWrite = $result[$entitySlug][$fieldName]['can_write'] ?: $permission['can_write'];
                    $canDelete = $result[$entitySlug][$fieldName]['can_delete'] ?: $permission['can_delete'];
                    $canViewAddInfo = $result[$entitySlug][$fieldName]['can_view_add_info'] ?: $permission['can_view_add_info'];

                    $result[$entitySlug][$fieldName]['can_view'] = (bool)$canView;
                    $result[$entitySlug][$fieldName]['can_read'] = (bool)$canRead;
                    $result[$entitySlug][$fieldName]['can_write'] = (bool)$canWrite;
                    $result[$entitySlug][$fieldName]['can_delete'] = (bool)$canDelete;
                    $result[$entitySlug][$fieldName]['can_view_add_info'] = (bool)$canViewAddInfo;

                    continue;
                }

                $result[$entitySlug][$fieldName] = [
                    'can_view'          => (bool)$permission['can_view'],
                    'can_read'          => (bool)$permission['can_read'],
                    'can_write'         => (bool)$permission['can_write'],
                    'can_delete'        => (bool)$permission['can_delete'],
                    'can_view_add_info' => (bool)$permission['can_view_add_info'],
                ];
            }
        }

        return $result;
    }

    /**
     * Enrich existing role permissions with remaining (not granted) permissions
     *
     * @param array<int, mixed>    $permissionEntities
     * @param array<string, mixed> $result
     *
     * @return array
     */
    public function preparePermissions(array $permissionEntities, array $result): array
    {
        foreach ($permissionEntities as $permissionEntity) {
            $entitySlug = $permissionEntity['slug'];
            $modelName = $permissionEntity['model'];

            if (!class_exists($modelName)) {
                continue;
            }

            $model = new $modelName();
            $fields = $model->fields;

            foreach ($fields as $fieldName => $params) {
                $needPermission = $params['permission'] ?? false;

                if ($needPermission && !isset($result[$entitySlug][$fieldName])) {
                    $result[$entitySlug][$fieldName] = [
                        'can_view'          => false,
                        'can_read'          => false,
                        'can_write'         => false,
                        'can_delete'        => false,
                        'can_view_add_info' => false,
                    ];
                }
            }
        }

        return $result;
    }
}
