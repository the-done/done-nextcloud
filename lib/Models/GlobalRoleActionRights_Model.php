<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Models;

use OCA\Done\Models\Dictionaries\GlobalRoles_Model;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class GlobalRoleActionRights_Model.
 */
class GlobalRoleActionRights_Model extends Base_Model
{
    public string $table = 'done_g_r_action_rights';
    public string $modelTitle = 'Global role action rights';
    public string $modelName = 'globalRoleActionRightsModel';
    public string $dbTableComment = 'Storing the set permissions for global role actions in an application.';

    protected array $hashFields = [
        'global_role_id',
        'action',
        'can',
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
            'db_comment' => 'Global role ID. References oc_done_global_roles.id',
        ],
        'action' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Role action',
            'required'   => true,
            'db_comment' => 'Role action',
        ],
        'can' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Can a role perform an action',
            'required'   => false,
            'db_comment' => 'Can a role perform an action (1 - yes, 0 - no)',
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

    public function getUsersRightsMap(): array
    {
        $rightsMap = [];
        $rolesRights = $this->getList();

        foreach ($rolesRights as $roleRight) {
            $role = $roleRight['global_role_id'] ?? null;
            $action = $roleRight['action'] ?? null;
            $can = $roleRight['can'];

            if (empty($role) || empty($action)) {
                continue;
            }

            if ($can) {
                $rightsMap[$role][] = $action;
            }
        }

        return $rightsMap;
    }

    /**
     * @override
     */
    public function addData(array $data): ?string
    {
        $existItem = $this->getItemByFilter(
            [
                'global_role_id' => $data['global_role_id'],
                'action'         => $data['action'],
            ]
        );

        return !empty($existItem) ? $this->update($data, $existItem['id']) : parent::addData($data);
    }
}
