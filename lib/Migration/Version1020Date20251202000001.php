<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Migration;

use Doctrine\DBAL\Schema\SchemaException;
use OCA\Done\Models\Dictionaries\GlobalRoles_Model;
use OCA\Done\Models\GlobalRoleActionRights_Model;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1020Date20251202000001 extends SimpleMigrationStep
{
    protected IDBConnection $connection;

    public function __construct(IDBConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param IOutput                    $output
     * @param \Closure(): ISchemaWrapper $schemaClosure
     * @param array                      $options
     *
     * @return null|ISchemaWrapper
     *
     * @throws SchemaException
     */
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        $schema = $schemaClosure();

        $globalRoleActionRightsModel = new GlobalRoleActionRights_Model();

        if (!$schema->hasTable($globalRoleActionRightsModel->table)) {
            $table = $schema->createTable($globalRoleActionRightsModel->table);
            $table->setComment($globalRoleActionRightsModel->dbTableComment);

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a permission record',
                ]
            )->setLength(32);
            $table->addColumn(
                'global_role_id',
                'integer',
                [
                    'notnull' => true,
                    'comment' => 'Global role ID. References oc_done_global_roles.id',
                ]
            );
            $table->addColumn(
                'action',
                'string',
                [
                    'notnull' => true,
                    'default' => '',
                ]
            )->setLength(100);
            $table->addColumn(
                'can',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                ]
            );
            $table->addColumn(
                'created_at',
                'datetime_immutable',
                ['notnull' => false, 'default' => null, 'comment' => 'Record creation timestamp in UTC']
            );
            $table->addColumn(
                'updated_at',
                'datetime_immutable',
                ['notnull' => false, 'default' => null, 'comment' => 'Record last update timestamp in UTC']
            );

            $table->setPrimaryKey(['id']);
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $globalRoleActionRightsModel = new GlobalRoleActionRights_Model();

        if ($this->connection->tableExists(
            $globalRoleActionRightsModel->table
        ) && empty($globalRoleActionRightsModel->getList())) {
            $dataToSave = [
                [
                    'global_role_id' => GlobalRoles_Model::ADMIN,
                    'action'         => GlobalRoles_Model::CAN_ADD_ADMIN,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::ADMIN,
                    'action'         => GlobalRoles_Model::CAN_ADD_OFFICER,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::ADMIN,
                    'action'         => GlobalRoles_Model::CAN_CREATE_USERS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::ADMIN,
                    'action'         => GlobalRoles_Model::CAN_READ_USERS_LIST,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::ADMIN,
                    'action'         => GlobalRoles_Model::CAN_READ_USERS_PROFILE,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::ADMIN,
                    'action'         => GlobalRoles_Model::CAN_DISMISS_USERS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::ADMIN,
                    'action'         => GlobalRoles_Model::CAN_EDIT_USERS_GLOBAL_ROLES,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::ADMIN,
                    'action'         => GlobalRoles_Model::CAN_READ_DICTIONARIES,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::ADMIN,
                    'action'         => GlobalRoles_Model::CAN_ADD_STATISTICS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_ADD_HEAD,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_ADD_CURATOR,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_CREATE_USERS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_READ_USERS_LIST,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_READ_USERS_PROFILE,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_ADD_USERS_TO_DIRECTIONS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_DISMISS_USERS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_CREATE_PROJECTS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_READ_PROJECTS_LIST,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_ADD_USERS_TO_PROJECTS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_EDIT_PROJECTS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_DELETE_PROJECTS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_ADD_USERS_TO_TEAMS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_READ_TEAMS_LIST,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_CREATE_TEAMS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_EDIT_TEAMS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_DELETE_TEAMS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_ADD_TEAMS_TO_DIRECTIONS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_ADD_TEAMS_TO_PROJECTS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_READ_REPORTS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_READ_COMMON_REPORT,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_READ_PROJECTS_REPORT,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_READ_STAFF_REPORT,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_READ_SETTINGS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_READ_RIGHTS_MATRIX,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_EDIT_RIGHTS_MATRIX,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_READ_STATISTICS_ALL_USERS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_READ_DICTIONARIES,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::OFFICER,
                    'action'         => GlobalRoles_Model::CAN_ADD_STATISTICS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::HEAD,
                    'action'         => GlobalRoles_Model::CAN_READ_USERS_LIST,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::HEAD,
                    'action'         => GlobalRoles_Model::CAN_READ_USERS_PROFILE,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::HEAD,
                    'action'         => GlobalRoles_Model::CAN_READ_PROJECTS_LIST,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::HEAD,
                    'action'         => GlobalRoles_Model::CAN_READ_REPORTS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::HEAD,
                    'action'         => GlobalRoles_Model::CAN_READ_COMMON_REPORT,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::HEAD,
                    'action'         => GlobalRoles_Model::CAN_READ_PROJECTS_REPORT,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::HEAD,
                    'action'         => GlobalRoles_Model::CAN_READ_STAFF_REPORT,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::HEAD,
                    'action'         => GlobalRoles_Model::CAN_READ_STATISTICS_ALL_USERS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::HEAD,
                    'action'         => GlobalRoles_Model::CAN_ADD_STATISTICS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::CURATOR,
                    'action'         => GlobalRoles_Model::CAN_ADD_STATISTICS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::EMPLOYEE,
                    'action'         => GlobalRoles_Model::CAN_ADD_STATISTICS,
                    'can'            => true,
                ],
                [
                    'global_role_id' => GlobalRoles_Model::FINANCE,
                    'action'         => GlobalRoles_Model::CAN_READ_FINANCES,
                    'can'            => true,
                ],
            ];

            foreach ($dataToSave as $item) {
                $globalRoleActionRightsModel->addData($item);
            }
        }
    }
}
