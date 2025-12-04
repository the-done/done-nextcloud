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
 * Class UsersGlobalRoles_Model.
 */
class UsersGlobalRoles_Model extends Base_Model
{
    public string $table = 'done_users_roles_g';
    public string $modelTitle = 'Links users to global roles';
    public string $modelName = 'usersGlobalRolesModel';
    public string $dbTableComment = 'Links users to global roles: defines a user\'s overall access level in the system.';

    protected array $hashFields = [
        'user_id',
        'role_id',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Unique identifier for a global role assignment to a user',
        ],
        'user_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'User',
            'link'       => User_Model::class,
            'required'   => true,
            'db_comment' => 'User ID. References oc_done_users_data.id',
        ],
        'role_id' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Global role',
            'link'       => GlobalRoles_Model::class,
            'required'   => true,
            'db_comment' => 'Global role ID. References oc_done_global_roles.id',
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

    /**
     * Get user global roles
     *
     * @param string $userId
     *
     * @return int[]
     */
    public function getUserRoles(string $userId): array
    {
        $data = $this->getListByFilter(['user_id' => $userId]);

        if (!empty($data)) {
            return BaseService::getField($data, 'role_id', true);
        }

        return [];
    }

    /**
     * Get permissions for global roles
     *
     * @param int[] $globalRoles
     *
     * @return array<string, bool>
     */
    public function getRights(array $globalRoles): array
    {
        $rightsMap = (new GlobalRoleActionRights_Model())->getUsersRightsMap();
        $defaultRights = GlobalRoles_Model::getUsersDefaultRights();
        $rights = $result = [];

        foreach ($globalRoles as $role) {
            $rights = array_merge($rights, $rightsMap[$role]);
        }

        $rights = array_unique($rights);

        foreach ($defaultRights as $right => $can) {
            $result[$right] = \in_array($right, $rights);
        }

        return $result;
    }
}
