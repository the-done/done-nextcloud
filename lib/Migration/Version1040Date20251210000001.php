<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Migration;

use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migration for creating finance contract tables
 * Creates tables: done_fin_contracts, done_fin_c_params, done_fin_c_p_values,
 * done_fin_c_p_groups, done_fin_c_p_g_to_p
 */
class Version1040Date20251210000001 extends SimpleMigrationStep
{
    /**
     * @param IOutput                    $output
     * @param \Closure(): ISchemaWrapper $schemaClosure
     * @param array                      $options
     *
     * @throws SchemaException
     */
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('done_fin_contracts')) {
            $table = $schema->createTable('done_fin_contracts');
            $table->setComment('Company contracts concluded with employees. Uses soft-delete.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a contract',
                ]
            )->setLength(32);
            $table->addColumn(
                'employee_id',
                'string',
                [
                    'notnull' => false,
                    'default' => '',
                    'comment' => 'ID of the employee associated with the contract. References oc_done_users_data.id',
                ]
            )->setLength(32);
            $table->addColumn(
                'start_date',
                'date_immutable',
                [
                    'notnull' => false,
                    'default' => null,
                    'comment' => 'Contract start date',
                ]
            );
            $table->addColumn(
                'end_date',
                'date_immutable',
                [
                    'notnull' => false,
                    'default' => null,
                    'comment' => 'Contract expiration date',
                ]
            );
            $table->addColumn(
                'is_hourly',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                    'comment' => 'A flag indicating that contract payments are based on hourly wages.',
                ]
            );
            $table->addColumn(
                'number_of_hours',
                'integer',
                [
                    'notnull' => false,
                    'default' => 0,
                    'comment' => 'Number of working hours for the period',
                ]
            );
            $table->addColumn(
                'hourly_rate',
                'integer',
                [
                    'notnull' => false,
                    'default' => 0,
                    'comment' => 'Hourly rate',
                ]
            );
            $table->addColumn(
                'period_rate',
                'integer',
                [
                    'notnull' => false,
                    'default' => 0,
                    'comment' => 'Period rate',
                ]
            );
            $table->addColumn(
                'project_rate',
                'integer',
                [
                    'notnull' => false,
                    'default' => 0,
                    'comment' => 'Project rate',
                ]
            );
            $table->addColumn(
                'created_at',
                'datetime_immutable',
                [
                    'notnull' => false,
                    'default' => null,
                    'comment' => 'Record creation timestamp in UTC',
                ]
            );
            $table->addColumn(
                'updated_at',
                'datetime_immutable',
                [
                    'notnull' => false,
                    'default' => null,
                    'comment' => 'Record last update timestamp in UTC',
                ]
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

        if (!$schema->hasTable('done_fin_c_params')) {
            $table = $schema->createTable('done_fin_c_params');
            $table->setComment('Company contracts parameters. Uses soft-delete.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a contract parameter',
                ]
            )->setLength(32);
            $table->addColumn(
                'name',
                'string',
                [
                    'notnull' => true,
                    'comment' => 'Contract parameter title',
                ]
            )->setLength(255);
            $table->addColumn(
                'type_id',
                'integer',
                [
                    'notnull' => true,
                    'comment' => 'Contract parameter type ID (1 - number, 2 - percent, 3 - formula)',
                ]
            );
            $table->addColumn(
                'created_at',
                'datetime_immutable',
                [
                    'notnull' => false,
                    'default' => null,
                    'comment' => 'Record creation timestamp in UTC',
                ]
            );
            $table->addColumn(
                'updated_at',
                'datetime_immutable',
                [
                    'notnull' => false,
                    'default' => null,
                    'comment' => 'Record last update timestamp in UTC',
                ]
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

        if (!$schema->hasTable('done_fin_c_p_values')) {
            $table = $schema->createTable('done_fin_c_p_values');
            $table->setComment('Company contracts parameters values. Uses soft-delete.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a contract parameter',
                ]
            )->setLength(32);
            $table->addColumn(
                'contract_id',
                'string',
                [
                    'notnull' => true,
                    'comment' => 'Contract ID. References done_fin_contracts.id',
                ]
            )->setLength(32);
            $table->addColumn(
                'contract_parameter_id',
                'string',
                [
                    'notnull' => true,
                    'comment' => 'Contract parameter ID. References done_fin_contracts_params.id',
                ]
            )->setLength(32);
            $table->addColumn(
                'value',
                'text',
                [
                    'notnull' => false,
                    'default' => '',
                    'comment' => 'Contract parameter value (can be a number, percent, or formula)',
                ]
            )->setLength(255);
            $table->addColumn(
                'calculated_value',
                'string',
                [
                    'notnull' => false,
                    'default' => '',
                    'comment' => 'Calculated result value for formulas (cached for performance)',
                ]
            )->setLength(255);
            $table->addColumn(
                'last_calculated_at',
                'datetime_immutable',
                [
                    'notnull' => false,
                    'default' => null,
                    'comment' => 'Timestamp when the formula was last calculated in UTC',
                ]
            );
            $table->addColumn(
                'created_at',
                'datetime_immutable',
                [
                    'notnull' => false,
                    'default' => null,
                    'comment' => 'Record creation timestamp in UTC',
                ]
            );
            $table->addColumn(
                'updated_at',
                'datetime_immutable',
                [
                    'notnull' => false,
                    'default' => null,
                    'comment' => 'Record last update timestamp in UTC',
                ]
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

        if (!$schema->hasTable('done_fin_c_p_groups')) {
            $table = $schema->createTable('done_fin_c_p_groups');
            $table->setComment('Company contract parameter groups. Uses soft-delete.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a contract parameter group',
                ]
            )->setLength(32);
            $table->addColumn(
                'name',
                'string',
                [
                    'notnull' => true,
                    'comment' => 'Contract parameter group title',
                ]
            )->setLength(255);
            $table->addColumn(
                'created_at',
                'datetime_immutable',
                [
                    'notnull' => false,
                    'default' => null,
                    'comment' => 'Record creation timestamp in UTC',
                ]
            );
            $table->addColumn(
                'updated_at',
                'datetime_immutable',
                [
                    'notnull' => false,
                    'default' => null,
                    'comment' => 'Record last update timestamp in UTC',
                ]
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

        if (!$schema->hasTable('done_fin_c_p_g_to_p')) {
            $table = $schema->createTable('done_fin_c_p_g_to_p');
            $table->setComment('Links contract parameter groups to parameters.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a global role assignment to a user',
                ]
            )->setLength(32);
            $table->addColumn(
                'group_id',
                'string',
                [
                    'notnull' => true,
                    'comment' => 'Group ID. References done_fin_c_p_groups.id',
                ]
            )->setLength(32);
            $table->addColumn(
                'parameter_id',
                'string',
                [
                    'notnull' => true,
                    'comment' => 'Contract parameter ID. References done_fin_c_params.id',
                ]
            )->setLength(32);
            $table->addColumn(
                'created_at',
                'datetime_immutable',
                [
                    'notnull' => false,
                    'default' => null,
                    'comment' => 'Record creation timestamp in UTC',
                ]
            );
            $table->addColumn(
                'updated_at',
                'datetime_immutable',
                [
                    'notnull' => false,
                    'default' => null,
                    'comment' => 'Record last update timestamp in UTC',
                ]
            );

            $table->setPrimaryKey(['id']);
            $table->addIndex(['group_id'], 'idx_c_p_g_to_p_group');
            $table->addIndex(['parameter_id'], 'idx_c_p_g_to_p_parameter');
            $table->addUniqueIndex(['group_id', 'parameter_id'], 'uniq_group_parameter');
        }

        return $schema;
    }
}
