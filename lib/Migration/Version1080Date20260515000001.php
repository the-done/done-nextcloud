<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Migration;

use OCA\Done\Models\Dictionaries\GlobalRolesModel;
use OCA\Done\Models\GlobalRoleActionRightsModel;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1080Date20260515000001 extends SimpleMigrationStep
{
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('done_vac_coefficients')) {
            $table = $schema->createTable('done_vac_coefficients');
            $table->setComment('Vacation accrual coefficients per employee and vacation type, with effective dates.');

            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'length'        => 32,
                'comment'       => 'Unique identifier for a coefficient entry',
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'Employee ID. References oc_done_users_data.id',
            ]);
            $table->addColumn('type_id', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'Vacation type ID. References oc_done_vacations_types.id',
            ]);
            $table->addColumn('value', 'decimal', [
                'notnull'   => true,
                'precision' => 5,
                'scale'     => 2,
                'default'   => '1.00',
                'comment'   => 'Coefficient multiplier applied to monthly accrual (e.g., 1.20)',
            ]);
            $table->addColumn('effective_from', 'date_immutable', [
                'notnull' => true,
                'comment' => 'Date when this coefficient becomes active (inclusive)',
            ]);
            $table->addColumn('effective_to', 'date_immutable', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Date when this coefficient stops being active (inclusive); null means open-ended',
            ]);
            $table->addColumn('comment', 'text', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Optional comment (reason for the coefficient, etc.)',
            ]);
            $table->addColumn('adjusted_by', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'User who created or last modified the record. References oc_done_users_data.id',
            ]);
            $table->addColumn('created_at', 'datetime_immutable', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Record creation timestamp in UTC',
            ]);
            $table->addColumn('updated_at', 'datetime_immutable', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Record last update timestamp in UTC',
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'vac_coef_user_id_idx');
            $table->addIndex(['type_id'], 'vac_coef_type_id_idx');
            $table->addIndex(['effective_from'], 'vac_coef_eff_from_idx');
            $table->addIndex(['effective_to'], 'vac_coef_eff_to_idx');
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void
    {
        $globalRoleActionRightsModel = new GlobalRoleActionRightsModel();

        $globalRoleActionRightsModel->upsertByFilter(
            [
                'global_role_id' => GlobalRolesModel::OFFICER,
                'action'         => GlobalRolesModel::CAN_READ_VACATIONS_REPORT,
                'can'            => true,
            ],
            [
                'global_role_id' => GlobalRolesModel::OFFICER,
                'action'         => GlobalRolesModel::CAN_READ_VACATIONS_REPORT,
            ]
        );

        $globalRoleActionRightsModel->upsertByFilter(
            [
                'global_role_id' => GlobalRolesModel::HEAD,
                'action'         => GlobalRolesModel::CAN_READ_VACATIONS_REPORT,
                'can'            => false,
            ],
            [
                'global_role_id' => GlobalRolesModel::HEAD,
                'action'         => GlobalRolesModel::CAN_READ_VACATIONS_REPORT,
            ]
        );
    }
}
