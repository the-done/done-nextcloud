<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Migration;

use Doctrine\DBAL\Schema\SchemaException;
use OCA\Done\Models\Dictionaries\GlobalRolesModel;
use OCA\Done\Models\GlobalRoleActionRightsModel;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1050Date20260112000001 extends SimpleMigrationStep
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
     * @throws SchemaException
     */
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        $schema = $schemaClosure();

        if (!$schema->hasTable('done_fin_tag')) {
            $table = $schema->createTable('done_fin_tag');
            $table->setComment('[FUTURE] Lookup table for financial tags for payment categorization. This module is under development and not in use. Uses soft-delete.');
            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'comment'       => 'Unique identifier for a payment tag',
            ])->setLength(32);
            $table->addColumn('name', 'string', [
                'notnull' => true,
                'default' => '',
                'length'  => 255,
                'comment' => 'Name of the financial tag',
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record creation timestamp in UTC',
            ]);
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record last update timestamp in UTC',
            ]);
            $table->addColumn('deleted', 'boolean', [
                'notnull' => false,
                'default' => false,
                'comment' => 'Soft-delete flag (1 - deleted, 0 - active). Deleted records should be excluded from queries.',
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['deleted'], 'fin_tag_deleted_idx');
        }

        if (!$schema->hasTable('done_fin_payments')) {
            $table = $schema->createTable('done_fin_payments');
            $table->setComment('[FUTURE] Financial payments: income, expenses, company financial transactions. This module is under development and not in use. Uses soft-delete.');
            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'comment'       => 'Unique identifier for a financial transaction (payment)',
            ])->setLength(32);
            $table->addColumn('date', 'date_immutable', [
                'notnull' => true,
                'comment' => 'Date the payment was made',
            ]);
            $table->addColumn('amount', 'integer', [
                'notnull' => true,
                'comment' => 'Payment amount in RUB (stored in kopecks)',
            ]);
            $table->addColumn('type_id', 'integer', [
                'notnull' => true,
                'comment' => 'Payment type. Possible values: 1 (debit/income), 2 (credit/expense)',
            ]);
            $table->addColumn('description', 'text', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Payment description/purpose',
            ]);
            $table->addColumn('payee', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 255,
                'comment' => 'Recipient of the payment',
            ]);
            $table->addColumn('payer', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 255,
                'comment' => 'Payer',
            ]);
            $table->addColumn('INN', 'integer', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Taxpayer Identification Number',
            ]);
            $table->addColumn('employee_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 255,
                'comment' => 'ID of the employee associated with the payment. References oc_done_users_data.id',
            ]);
            $table->addColumn('customer_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 255,
                'comment' => 'ID of the customer associated with the payment. References oc_done_customers.id',
            ]);
            $table->addColumn('comment', 'text', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Internal comment on the payment',
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record creation timestamp in UTC',
            ]);
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record last update timestamp in UTC',
            ]);
            $table->addColumn('deleted', 'boolean', [
                'notnull' => false,
                'default' => false,
                'comment' => 'Soft-delete flag (1 - deleted, 0 - active). Deleted records should be excluded from queries.',
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['type_id'], 'fin_payments_type_id_idx');
            $table->addIndex(['employee_id'], 'fin_payments_employee_id_idx');
            $table->addIndex(['customer_id'], 'fin_payments_customer_id_idx');
            $table->addIndex(['deleted'], 'fin_payments_deleted_idx');
            $table->addIndex(['date'], 'fin_payments_date_idx');
        }

        if (!$schema->hasTable('done_fin_payment_tags')) {
            $table = $schema->createTable('done_fin_payment_tags');
            $table->setComment('[FUTURE] Links payments to tags for categorization. This module is under development and not in use.');
            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'comment'       => 'Unique identifier for the payment-to-tag link',
            ])->setLength(32);
            $table->addColumn('payment_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 255,
                'comment' => 'Payment ID. References oc_done_fin_payments.id',
            ]);
            $table->addColumn('tag_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 255,
                'comment' => 'Tag ID. References oc_done_fin_tag.id',
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record creation timestamp in UTC',
            ]);
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record last update timestamp in UTC',
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['payment_id'], 'fin_payment_tags_p_id_idx');
            $table->addIndex(['tag_id'], 'fin_payment_tags_t_id_idx');
        }

        if (!$schema->hasTable('done_employeestoteams')) {
            $table = $schema->createTable('done_employeestoteams');
            $table->setComment('Links employees to teams: defines team composition and employee roles within them.');
            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'comment'       => 'Unique identifier for the employee-to-team link',
            ])->setLength(32);
            $table->addColumn('user_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'User ID. References oc_done_users_data.id',
            ]);
            $table->addColumn('team_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'Team ID. References oc_done_teams.id',
            ]);
            $table->addColumn('role_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'Role in team ID. References oc_done_roles_in_team.id',
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record creation timestamp in UTC',
            ]);
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record last update timestamp in UTC',
            ]);
            $table->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('done_roles_in_team')) {
            $table = $schema->createTable('done_roles_in_team');
            $table->setComment('Lookup table: stores the names of employee roles in teams. Uses soft-delete.');
            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'comment'       => 'Unique identifier for an employee role in a team',
            ])->setLength(32);
            $table->addColumn('name', 'string', [
                'notnull' => true,
                'default' => '',
                'length'  => 255,
                'comment' => 'Name of the employee role in a team',
            ]);
            $table->addColumn('sort', 'integer', [
                'notnull' => false,
                'default' => 0,
                'comment' => 'Sort order number for the record',
            ]);
            $table->addColumn('deleted', 'boolean', [
                'notnull' => false,
                'default' => false,
                'comment' => 'Soft-delete flag (1 - deleted, 0 - active). Deleted records should be excluded from queries.',
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record creation timestamp in UTC',
            ]);
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record last update timestamp in UTC',
            ]);
            $table->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('done_team_appearances')) {
            $table = $schema->createTable('done_team_appearances');
            $table->setComment('Team appearances: avatars, symbols, colors, background images. Uses soft-delete.');
            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a team appearance record',
                ]
            )->setLength(32);
            $table->addColumn('team_id', 'string', [
                'notnull' => true,
                'comment' => 'Team ID. References oc_done_teams.id',
                'length'  => 32,
            ]);
            $table->addColumn('avatar', 'string', [
                'notnull' => false,
                'default' => '',
                'comment' => 'URL of the team avatar',
                'length'  => 255,
            ]);
            $table->addColumn('symbol', 'string', [
                'notnull' => false,
                'default' => '',
                'comment' => 'URL of team symbol (emoji)',
                'length'  => 255,
            ]);
            $table->addColumn('bg_image', 'string', [
                'notnull' => false,
                'default' => '',
                'comment' => 'URL of the team card background image',
                'length'  => 255,
            ]);
            $table->addColumn('color', 'string', [
                'notnull' => false,
                'default' => '',
                'comment' => 'Team card color in HEX format (e.g., #RRGGBB)',
                'length'  => 255,
            ]);
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
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                    'comment' => 'Soft-delete flag (1 - deleted, 0 - active). Deleted records should be excluded from queries.',
                ]
            );

            $table->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('done_teams')) {
            $table = $schema->createTable('done_teams');
            $table->setComment('Employee teams: organizational units. Uses soft-delete.');
            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'comment'       => 'Unique identifier for a team',
            ])->setLength(32);
            $table->addColumn('name', 'string', [
                'notnull' => true,
                'default' => '',
                'length'  => 255,
                'comment' => 'Team name',
            ]);
            $table->addColumn('comment', 'text', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Description or comment about the team',
            ]);
            $table->addColumn('deleted', 'boolean', [
                'notnull' => false,
                'default' => false,
                'comment' => 'Soft-delete flag (1 - deleted, 0 - active). Deleted records should be excluded from queries.',
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record creation timestamp in UTC',
            ]);
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record last update timestamp in UTC',
            ]);
            $table->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('done_teamstodirections')) {
            $table = $schema->createTable('done_teamstodirections');
            $table->setComment('Links teams to directions: defines which company directions the teams belong to.');
            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'comment'       => 'Unique identifier for the team-to-direction link',
            ])->setLength(32);
            $table->addColumn('team_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'Team ID. References oc_done_teams.id',
            ]);
            $table->addColumn('direction_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'Direction ID. References oc_done_directions.id',
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record creation timestamp in UTC',
            ]);
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record last update timestamp in UTC',
            ]);
            $table->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('done_teamstoprojects')) {
            $table = $schema->createTable('done_teamstoprojects');
            $table->setComment('Links teams to projects: defines which teams are assigned to which projects.');
            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'comment'       => 'Unique identifier for the team-to-project link',
            ])->setLength(32);
            $table->addColumn('team_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'Team ID. References oc_done_teams.id',
            ]);
            $table->addColumn('project_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'Project ID. References oc_done_projects.id',
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record creation timestamp in UTC',
            ]);
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => false,
                'comment' => 'Record last update timestamp in UTC',
            ]);
            $table->setPrimaryKey(['id']);
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void
    {
        $globalRoleActionRightsModel = new GlobalRoleActionRightsModel();

        if ($this->connection->tableExists($globalRoleActionRightsModel->table)) {
            if (
                empty($globalRoleActionRightsModel->getListByFilter(
                    ['action' => GlobalRolesModel::CAN_VIEW_USERS_LIST_RELATED]
                )
                )
            ) {
                $dataToSave = [
                    [
                        'global_role_id' => GlobalRolesModel::HEAD,
                        'action'         => GlobalRolesModel::CAN_VIEW_USERS_LIST_RELATED,
                        'can'            => true,
                    ],
                    [
                        'global_role_id' => GlobalRolesModel::CURATOR,
                        'action'         => GlobalRolesModel::CAN_VIEW_USERS_LIST_RELATED,
                        'can'            => true,
                    ],
                ];

                foreach ($dataToSave as $item) {
                    $globalRoleActionRightsModel->addData($item);
                }
            }

            if (
                empty($globalRoleActionRightsModel->getListByFilter(
                    ['action' => GlobalRolesModel::CAN_VIEW_PROJECTS_LIST_RELATED]
                )
                )
            ) {
                $globalRoleActionRightsModel->addData(
                    [
                        'global_role_id' => GlobalRolesModel::HEAD,
                        'action'         => GlobalRolesModel::CAN_VIEW_PROJECTS_LIST_RELATED,
                        'can'            => true,
                    ]
                );
            }

            $globalRoleActionRightsModel->upsertByFilter(
                [
                    'global_role_id' => GlobalRolesModel::HEAD,
                    'action'         => GlobalRolesModel::CAN_READ_USERS_LIST,
                    'can'            => false,
                ],
                [
                    'global_role_id' => GlobalRolesModel::HEAD,
                    'action'         => GlobalRolesModel::CAN_READ_USERS_LIST,
                ]
            );

            $globalRoleActionRightsModel->upsertByFilter(
                [
                    'global_role_id' => GlobalRolesModel::HEAD,
                    'action'         => GlobalRolesModel::CAN_READ_PROJECTS_LIST,
                    'can'            => false,
                ],
                [
                    'global_role_id' => GlobalRolesModel::HEAD,
                    'action'         => GlobalRolesModel::CAN_READ_PROJECTS_LIST,
                ]
            );

            $globalRoleActionRightsModel->upsertByFilter(
                [
                    'global_role_id' => GlobalRolesModel::HEAD,
                    'action'         => GlobalRolesModel::CAN_READ_COMMON_REPORT,
                    'can'            => false,
                ],
                [
                    'global_role_id' => GlobalRolesModel::HEAD,
                    'action'         => GlobalRolesModel::CAN_READ_COMMON_REPORT,
                ]
            );

            $globalRoleActionRightsModel->upsertByFilter(
                [
                    'global_role_id' => GlobalRolesModel::HEAD,
                    'action'         => GlobalRolesModel::CAN_READ_STAFF_REPORT,
                    'can'            => false,
                ],
                [
                    'global_role_id' => GlobalRolesModel::HEAD,
                    'action'         => GlobalRolesModel::CAN_READ_STAFF_REPORT,
                ]
            );
        }
    }
}
