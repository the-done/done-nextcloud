<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Models\Dictionaries;

use OCA\Done\Models\Base_Model;
use OCA\Done\Modules\BaseModuleService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class GlobalRoles_Model.
 */
class GlobalRoles_Model extends Base_Model
{
    public string $table = 'done_global_roles';
    public string $modelTitle = 'Global roles';
    public string $modelName = 'globalRolesDictionary';
    public string $dbTableComment = 'Lookup table: stores the names of global user roles. Uses soft-delete.';

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'ID',
            'db_comment' => 'Internal unique key for a global role',
        ],
        'name' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Role name',
            'required'         => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Name of the user\'s global role (e.g., Administrator, Director)',
        ],
        'sort' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Sorting',
            'db_comment' => 'Sort order number for the record',
        ],
        'deleted' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Deleted',
            'db_comment' => 'Soft-delete flag (1 - deleted, 0 - active). Deleted records should be excluded from queries.',
        ],
    ];

    public bool $unsetIndexField = false;

    // Global roles
    public const ADMIN = 1;
    public const OFFICER = 2;
    public const HEAD = 3;
    public const CURATOR = 4;
    public const EMPLOYEE = 5;
    public const FINANCE = 6;

    // Permissions
    public const CAN_ADD_ADMIN = 'canAddAdmin';
    public const CAN_ADD_OFFICER = 'canAddOfficer';
    public const CAN_ADD_CURATOR = 'canAddCurator';
    public const CAN_ADD_HEAD = 'canAddHead';
    public const CAN_CREATE_USERS = 'canCreateUsers';
    public const CAN_READ_DICTIONARIES = 'canReadDictionaries';
    public const CAN_READ_USERS_LIST = 'canReadUsersList';
    public const CAN_READ_PROJECTS_LIST = 'canReadProjectsList';
    public const CAN_CREATE_PROJECTS = 'canCreateProjects';
    public const CAN_ADD_USERS_TO_PROJECTS = 'canAddUsersToProjects';
    public const CAN_DISMISS_USERS = 'canDismissUsers';
    public const CAN_READ_STATISTICS_ALL_USERS = 'canReadStatisticsAllUsers';
    public const CAN_READ_USERS_PROFILE = 'canReadUsersProfile';
    public const CAN_ADD_STATISTICS = 'canAddStatistics';

    public const CAN_READ_REPORTS = 'canReadReports';
    public const CAN_READ_COMMON_REPORT = 'canReadCommonReport';
    public const CAN_READ_PROJECTS_REPORT = 'canReadProjectsReport';
    public const CAN_READ_STAFF_REPORT = 'canReadStaffReport';
    public const CAN_READ_TEAMS_LIST = 'canReadTeamsList';
    public const CAN_READ_SETTINGS = 'canReadSettings';
    public const CAN_READ_RIGHTS_MATRIX = 'canReadRightsMatrix';
    public const CAN_EDIT_RIGHTS_MATRIX = 'canEditRightsMatrix';
    public const CAN_EDIT_PROJECTS = 'canEditProjects';
    public const CAN_DELETE_PROJECTS = 'canDeleteProjects';

    public const CAN_READ_FINANCES = 'canReadFinances';

    public const CAN_CREATE_TEAMS = 'canCreateTeams';
    public const CAN_EDIT_TEAMS = 'canEditTeams';
    public const CAN_DELETE_TEAMS = 'canDeleteTeams';
    public const CAN_ADD_USERS_TO_TEAMS = 'canAddUsersToTeams';
    public const CAN_ADD_TEAMS_TO_DIRECTIONS = 'canAddTeamsToDirections';
    public const CAN_ADD_TEAMS_TO_PROJECTS = 'canAddTeamsToProjects';
    public const CAN_EDIT_USERS_GLOBAL_ROLES = 'canEditUsersGlobalRoles';
    public const CAN_ADD_USERS_TO_DIRECTIONS = 'canAddUsersToDirections';

    public const CAN_HIDE_EMPTY_FIELDS_IN_PREVIEW = 'canHideEmptyFieldsInPreview';

    /**
     * Returns permission names.
     *
     * @return string[]
     */
    public function getRolesActions(): array
    {
        return [
            self::CAN_CREATE_USERS            => 'Create employees',
            self::CAN_READ_USERS_LIST         => 'View all employees list',
            self::CAN_READ_USERS_PROFILE      => 'View all employees profiles',
            self::CAN_ADD_USERS_TO_DIRECTIONS => 'Add employees to directions',
            self::CAN_DISMISS_USERS           => 'Dismiss employees',
            self::CAN_EDIT_USERS_GLOBAL_ROLES => 'Edit global roles of employees',

            self::CAN_CREATE_PROJECTS       => 'Create projects',
            self::CAN_READ_PROJECTS_LIST    => 'View all projects list',
            self::CAN_ADD_USERS_TO_PROJECTS => 'Add employees to projects',
            self::CAN_EDIT_PROJECTS         => 'Edit project card',
            self::CAN_DELETE_PROJECTS       => 'Delete projects',

            self::CAN_ADD_USERS_TO_TEAMS      => 'Add employees to teams',
            self::CAN_READ_TEAMS_LIST         => 'View all teams list',
            self::CAN_CREATE_TEAMS            => 'Create teams',
            self::CAN_EDIT_TEAMS              => 'Edit team card',
            self::CAN_DELETE_TEAMS            => 'Delete teams',
            self::CAN_ADD_TEAMS_TO_DIRECTIONS => 'Add teams to directions',
            self::CAN_ADD_TEAMS_TO_PROJECTS   => 'Add teams to projects',

            self::CAN_READ_REPORTS              => 'View reports',
            self::CAN_READ_COMMON_REPORT        => 'View common report',
            self::CAN_READ_PROJECTS_REPORT      => 'View projects report',
            self::CAN_READ_STAFF_REPORT         => 'View staff report',
            self::CAN_READ_STATISTICS_ALL_USERS => 'View statistics for all employees',
            self::CAN_ADD_STATISTICS            => 'Create reports',

            self::CAN_READ_FINANCES => 'View finances',

            self::CAN_READ_SETTINGS      => 'View settings',
            self::CAN_READ_RIGHTS_MATRIX => 'View rights matrix',
            self::CAN_EDIT_RIGHTS_MATRIX => 'Edit rights matrix',
            self::CAN_READ_DICTIONARIES  => 'Dictionaries actions',

            self::CAN_ADD_ADMIN   => 'Assign Administrator role',
            self::CAN_ADD_OFFICER => 'Assign Director role',
            self::CAN_ADD_HEAD    => 'Assign Head role',
            self::CAN_ADD_CURATOR => 'Assign Curator role',
        ];
    }

    public static function getUsersDefaultRights(): array
    {
        return [
            self::CAN_CREATE_USERS              => false,
            self::CAN_READ_USERS_LIST           => false,
            self::CAN_READ_USERS_PROFILE        => false,
            self::CAN_ADD_USERS_TO_DIRECTIONS   => false,
            self::CAN_DISMISS_USERS             => false,
            self::CAN_EDIT_USERS_GLOBAL_ROLES   => false,
            self::CAN_CREATE_PROJECTS           => false,
            self::CAN_READ_PROJECTS_LIST        => false,
            self::CAN_ADD_USERS_TO_PROJECTS     => false,
            self::CAN_EDIT_PROJECTS             => false,
            self::CAN_DELETE_PROJECTS           => false,
            self::CAN_ADD_USERS_TO_TEAMS        => false,
            self::CAN_READ_TEAMS_LIST           => false,
            self::CAN_CREATE_TEAMS              => false,
            self::CAN_EDIT_TEAMS                => false,
            self::CAN_DELETE_TEAMS              => false,
            self::CAN_ADD_TEAMS_TO_DIRECTIONS   => false,
            self::CAN_ADD_TEAMS_TO_PROJECTS     => false,
            self::CAN_READ_REPORTS              => false,
            self::CAN_READ_COMMON_REPORT        => false,
            self::CAN_READ_PROJECTS_REPORT      => false,
            self::CAN_READ_STAFF_REPORT         => false,
            self::CAN_READ_SETTINGS             => false,
            self::CAN_READ_RIGHTS_MATRIX        => false,
            self::CAN_EDIT_RIGHTS_MATRIX        => false,
            self::CAN_ADD_ADMIN                 => false,
            self::CAN_ADD_OFFICER               => false,
            self::CAN_ADD_HEAD                  => false,
            self::CAN_ADD_CURATOR               => false,
            self::CAN_READ_STATISTICS_ALL_USERS => false,
            self::CAN_READ_DICTIONARIES         => false,
            self::CAN_ADD_STATISTICS            => false,
            self::CAN_READ_FINANCES             => false,
        ];
    }

    public function getItem(
        int | string $id,
        array $fields = ['*'],
    ): array {
        $roleId = (int)$id;

        $fields = $this->prepareSelectFields($fields);

        $qb = $this->db->getQueryBuilder();
        $qb->select($fields)
            ->from($this->table)
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($roleId)));

        $item = $qb->executeQuery()->fetch();

        if (!empty($item)) {
            $item['name'] = $this->translateService->getTranslate($item['name']);
            $item = $this->addSlugToItem($item);
        }

        return !empty($item) ? $item : [];
    }

    /**
     * @override
     */
    public function getListByFilter(
        array $filter = [],
        array $fields = ['*'],
        array $orderBy = [],
        array $additionalOrderBy = [],
        bool $needDeleted = false,
    ): array {
        if (!BaseModuleService::moduleExists('finances')) {
            $filter['id'] = ['!=', self::FINANCE];
        }

        $data = parent::getListByFilter($filter, $fields, $orderBy, $additionalOrderBy, $needDeleted);

        return array_map(function ($item) {
            $item['name'] = $this->translateService->getTranslate($item['name']);

            return $item;
        }, $data);
    }
}
