<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Migration;

use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1060Date20260122000001 extends SimpleMigrationStep
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
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Vacation types lookup table
        if (!$schema->hasTable('done_vacations_types')) {
            $table = $schema->createTable('done_vacations_types');
            $table->setComment('Types of employee absences (vacation, sick leave, day-off, etc.). Uses soft-delete.');

            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'length'        => 32,
                'comment'       => 'Unique identifier for a vacation type',
            ]);
            $table->addColumn('name', 'string', [
                'notnull' => true,
                'length'  => 255,
                'comment' => 'Vacation type name (e.g., Paid Leave, Sick Leave, Day-off)',
            ]);
            $table->addColumn('color', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 7,
                'comment' => 'Color for UI display in HEX format (e.g., #FF5733)',
            ]);
            $table->addColumn('sort', 'integer', [
                'notnull' => false,
                'default' => 0,
                'comment' => 'Sort order for display',
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
            $table->addColumn('deleted', 'boolean', [
                'notnull' => false,
                'default' => false,
                'comment' => 'Soft-delete flag (1 - deleted, 0 - active)',
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['deleted'], 'vac_types_deleted_idx');
        }

        // Vacation type settings (limits per type)
        if (!$schema->hasTable('done_vac_type_settings')) {
            $table = $schema->createTable('done_vac_type_settings');
            $table->setComment('Settings for vacation types: default days limit, warnings, etc.');

            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'length'        => 32,
                'comment'       => 'Unique identifier for a type setting entry',
            ]);
            $table->addColumn('type_id', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'Vacation type ID. References oc_done_vacations_types.id',
            ]);
            $table->addColumn('value', 'integer', [
                'notnull' => true,
                'default' => 0,
                'comment' => 'Default days limit for this vacation type per year',
            ]);
            $table->addColumn('display_warning', 'boolean', [
                'notnull' => false,
                'default' => false,
                'comment' => 'Display warning when approaching/exceeding limit',
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
            $table->addIndex(['type_id'], 'vac_type_settings_type_idx');
        }

        // Main vacations/absences table
        if (!$schema->hasTable('done_vacations')) {
            $table = $schema->createTable('done_vacations');
            $table->setComment('Employee absence requests: vacation records, sick leaves, day-offs.');

            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'length'        => 32,
                'comment'       => 'Unique identifier for a vacation entry',
            ]);
            $table->addColumn('date_start', 'date_immutable', [
                'notnull' => true,
                'comment' => 'Absence start date',
            ]);
            $table->addColumn('date_end', 'date_immutable', [
                'notnull' => true,
                'comment' => 'Absence end date',
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'Employee who will be absent (the subject of the request). References oc_done_users_data.id',
            ]);
            $table->addColumn('type_id', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'Absence type ID. References oc_done_vacations_types.id',
            ]);
            $table->addColumn('project_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'Optional project ID for project-based employees. References oc_done_projects.id',
            ]);
            $table->addColumn('status_id', 'string', [
                'notnull' => true,
                'length'  => 20,
                'default' => 'pending',
                'comment' => 'Request status from Agreement module: pending, approved, rejected, returned',
            ]);
            $table->addColumn('created_by', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'User who created this request (may differ from user_id if HR creates request for employee). References oc_done_users_data.id',
            ]);
            $table->addColumn('comment', 'text', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Comment for the request (reason, rejection explanation, etc.)',
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
            $table->addIndex(['user_id'], 'vac_user_id_idx');
            $table->addIndex(['type_id'], 'vac_type_id_idx');
            $table->addIndex(['project_id'], 'vac_project_id_idx');
            $table->addIndex(['status_id'], 'vac_status_id_idx');
            $table->addIndex(['date_start'], 'vac_date_start_idx');
            $table->addIndex(['date_end'], 'vac_date_end_idx');
            $table->addIndex(['created_by'], 'vac_created_by_idx');
        }

        // Employee vacation balances (for manual HR adjustments)
        if (!$schema->hasTable('done_vac_balances')) {
            $table = $schema->createTable('done_vac_balances');
            $table->setComment('Employee vacation day balances: manual adjustments by HR for each vacation type per year.');

            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'length'        => 32,
                'comment'       => 'Unique identifier for a balance entry',
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
            $table->addColumn('year', 'integer', [
                'notnull' => true,
                'comment' => 'Year for which this balance applies',
            ]);
            $table->addColumn('days_total', 'integer', [
                'notnull' => true,
                'default' => 0,
                'comment' => 'Total days available (overrides default from type settings)',
            ]);
            $table->addColumn('comment', 'text', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Comment (e.g., reason for adjustment, bonus days)',
            ]);
            $table->addColumn('adjusted_by', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'User who made the adjustment. References oc_done_users_data.id',
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
            $table->addIndex(['user_id'], 'vac_bal_user_id_idx');
            $table->addIndex(['type_id'], 'vac_bal_type_id_idx');
            $table->addIndex(['year'], 'vac_bal_year_idx');
            $table->addUniqueIndex(['user_id', 'type_id', 'year'], 'vac_bal_user_type_year_uniq');
        }

        // =========================================================================
        // Agreement Module Tables
        // =========================================================================

        // Agreement schemes (main approval workflow templates)
        if (!$schema->hasTable('done_agr_schemes')) {
            $table = $schema->createTable('done_agr_schemes');
            $table->setComment('Agreement schemes: templates for multi-stage approval workflows.');

            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'length'        => 32,
                'comment'       => 'Unique identifier for an agreement scheme',
            ]);
            $table->addColumn('name', 'string', [
                'notnull' => true,
                'length'  => 255,
                'comment' => 'Scheme name (e.g., "Vacation approval", "Purchase approval")',
            ]);
            $table->addColumn('description', 'text', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Optional description of the scheme',
            ]);
            $table->addColumn('entity_type', 'string', [
                'notnull' => true,
                'length'  => 50,
                'comment' => 'Entity type this scheme applies to (e.g., "vacation", "purchase")',
            ]);
            $table->addColumn('is_default', 'boolean', [
                'notnull' => false,
                'default' => false,
                'comment' => 'Whether this is the default scheme for the entity type (1=default, 0=not default)',
            ]);
            $table->addColumn('is_active', 'boolean', [
                'notnull' => false,
                'default' => true,
                'comment' => 'Whether the scheme is active (1=active, 0=inactive)',
            ]);
            $table->addColumn('created_by', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'User who created the scheme. References oc_done_users_data.id',
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
            $table->addColumn('deleted', 'boolean', [
                'notnull' => false,
                'default' => false,
                'comment' => 'Soft delete flag (1=deleted, 0=active)',
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['entity_type'], 'agr_schemes_entity_type_idx');
            $table->addIndex(['is_default'], 'agr_schemes_is_default_idx');
            $table->addIndex(['is_active'], 'agr_schemes_is_active_idx');
            $table->addIndex(['deleted'], 'agr_schemes_deleted_idx');
        }

        // Agreement scheme lines (stages within schemes)
        if (!$schema->hasTable('done_agr_scheme_lines')) {
            $table = $schema->createTable('done_agr_scheme_lines');
            $table->setComment('Agreement scheme lines: stages within agreement schemes.');

            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'length'        => 32,
                'comment'       => 'Unique identifier for an agreement scheme line',
            ]);
            $table->addColumn('scheme_id', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'Parent scheme ID. References oc_done_agr_schemes.id',
            ]);
            $table->addColumn('name', 'string', [
                'notnull' => true,
                'length'  => 255,
                'comment' => 'Line name (e.g., "Manager approval", "HR approval")',
            ]);
            $table->addColumn('sort_order', 'integer', [
                'notnull' => false,
                'default' => 0,
                'comment' => 'Order of the line in the agreement process (1, 2, 3...)',
            ]);
            $table->addColumn('require_all', 'boolean', [
                'notnull' => false,
                'default' => false,
                'comment' => 'If true, all approvers must agree. If false, one approver is enough (1=all, 0=any)',
            ]);
            $table->addColumn('deadline_enabled', 'boolean', [
                'notnull' => false,
                'default' => false,
                'comment' => 'Whether deadline is enabled for this line (1=yes, 0=no)',
            ]);
            $table->addColumn('deadline_days', 'integer', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Number of days for agreement before auto-rejection',
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
            $table->addColumn('deleted', 'boolean', [
                'notnull' => false,
                'default' => false,
                'comment' => 'Soft delete flag (1=deleted, 0=active)',
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['scheme_id'], 'agr_lines_scheme_id_idx');
            $table->addIndex(['sort_order'], 'agr_lines_sort_order_idx');
            $table->addIndex(['deleted'], 'agr_lines_deleted_idx');
        }

        // Agreement scheme approvers (users or roles assigned to lines)
        if (!$schema->hasTable('done_agr_sch_approvers')) {
            $table = $schema->createTable('done_agr_sch_approvers');
            $table->setComment('Agreement scheme approvers: users or roles assigned to scheme lines.');

            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'length'        => 32,
                'comment'       => 'Unique identifier for an approver assignment',
            ]);
            $table->addColumn('line_id', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'Parent line ID. References oc_done_agr_scheme_lines.id',
            ]);
            $table->addColumn('approver_type', 'string', [
                'notnull' => true,
                'length'  => 10,
                'comment' => 'Type of approver: "user" or "role"',
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'User ID if approver_type is "user". References oc_done_users_data.id',
            ]);
            $table->addColumn('role_id', 'integer', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Role ID if approver_type is "role". References oc_done_global_roles.id',
            ]);
            $table->addColumn('created_at', 'datetime_immutable', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Record creation timestamp in UTC',
            ]);
            $table->addColumn('deleted', 'boolean', [
                'notnull' => false,
                'default' => false,
                'comment' => 'Soft delete flag (1=deleted, 0=active)',
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['line_id'], 'agr_approvers_line_id_idx');
            $table->addIndex(['approver_type'], 'agr_approvers_type_idx');
            $table->addIndex(['user_id'], 'agr_approvers_user_id_idx');
            $table->addIndex(['role_id'], 'agr_approvers_role_id_idx');
            $table->addIndex(['deleted'], 'agr_approvers_deleted_idx');
        }

        // Agreement scheme assignments (links schemes to specific entities)
        if (!$schema->hasTable('done_agr_sch_assgmts')) {
            $table = $schema->createTable('done_agr_sch_assgmts');
            $table->setComment('Agreement scheme assignments: links schemes to specific entities for custom agreement flows.');

            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'length'        => 32,
                'comment'       => 'Unique identifier for a scheme assignment',
            ]);
            $table->addColumn('scheme_id', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'Agreement scheme ID. References oc_done_agr_schemes.id',
            ]);
            $table->addColumn('entity_type', 'string', [
                'notnull' => true,
                'length'  => 50,
                'comment' => 'Type of main entity (e.g., "vacation")',
            ]);
            $table->addColumn('sub_entity_type', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 20,
                'comment' => 'Type of sub-entity for targeting (e.g., "user", "project")',
            ]);
            $table->addColumn('sub_entity_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'ID of the sub-entity (user_id, project_id, etc.)',
            ]);
            $table->addColumn('priority', 'integer', [
                'notnull' => false,
                'default' => 0,
                'comment' => 'Priority for assignment resolution (higher = more specific)',
            ]);
            $table->addColumn('created_by', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'User who created the assignment. References oc_done_users_data.id',
            ]);
            $table->addColumn('created_at', 'datetime_immutable', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Record creation timestamp in UTC',
            ]);
            $table->addColumn('deleted', 'boolean', [
                'notnull' => false,
                'default' => false,
                'comment' => 'Soft delete flag (1=deleted, 0=active)',
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['scheme_id'], 'agr_assign_scheme_id_idx');
            $table->addIndex(['entity_type'], 'agr_assign_entity_type_idx');
            $table->addIndex(['sub_entity_type'], 'agr_assign_sub_type_idx');
            $table->addIndex(['sub_entity_id'], 'agr_assign_sub_id_idx');
            $table->addIndex(['priority'], 'agr_assign_priority_idx');
            $table->addIndex(['deleted'], 'agr_assign_deleted_idx');
        }

        // Agreement requests (actual requests created from entities)
        if (!$schema->hasTable('done_agr_requests')) {
            $table = $schema->createTable('done_agr_requests');
            $table->setComment('Agreement requests: actual requests created from entities that need agreement.');

            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'length'        => 32,
                'comment'       => 'Unique identifier for an agreement request',
            ]);
            $table->addColumn('entity_type', 'string', [
                'notnull' => true,
                'length'  => 50,
                'comment' => 'Type of entity being agreed (e.g., "vacation", "purchase")',
            ]);
            $table->addColumn('entity_id', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'ID of the entity being agreed',
            ]);
            $table->addColumn('scheme_id', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'Agreement scheme used for this request. References oc_done_agr_schemes.id',
            ]);
            $table->addColumn('current_line_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'Current agreement line being processed. References oc_done_agr_scheme_lines.id',
            ]);
            $table->addColumn('status', 'string', [
                'notnull' => true,
                'length'  => 20,
                'comment' => 'Request status: "pending", "approved", "rejected", "returned"',
            ]);
            $table->addColumn('cached_title', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 255,
                'comment' => 'Cached title/summary of the entity for quick display',
            ]);
            $table->addColumn('cached_data', 'text', [
                'notnull' => false,
                'default' => null,
                'comment' => 'JSON-encoded cached key data from the entity',
            ]);
            $table->addColumn('deadline_at', 'datetime_immutable', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Deadline for current line agreement in UTC',
            ]);
            $table->addColumn('created_by', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'User who created the request. References oc_done_users_data.id',
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
            $table->addColumn('completed_at', 'datetime_immutable', [
                'notnull' => false,
                'default' => null,
                'comment' => 'When the request was fully agreed/rejected in UTC',
            ]);
            $table->addColumn('deleted', 'boolean', [
                'notnull' => false,
                'default' => false,
                'comment' => 'Soft delete flag (1=deleted, 0=active)',
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['entity_type'], 'agr_req_entity_type_idx');
            $table->addIndex(['entity_id'], 'agr_req_entity_id_idx');
            $table->addIndex(['scheme_id'], 'agr_req_scheme_id_idx');
            $table->addIndex(['current_line_id'], 'agr_req_current_line_idx');
            $table->addIndex(['status'], 'agr_req_status_idx');
            $table->addIndex(['deadline_at'], 'agr_req_deadline_idx');
            $table->addIndex(['created_by'], 'agr_req_created_by_idx');
            $table->addIndex(['deleted'], 'agr_req_deleted_idx');
            $table->addUniqueIndex(['entity_type', 'entity_id'], 'agr_req_entity_uniq');
        }

        // Agreement request actions (history of actions on requests)
        if (!$schema->hasTable('done_agr_req_actions')) {
            $table = $schema->createTable('done_agr_req_actions');
            $table->setComment('Agreement request actions: history of actions (approve/reject/return) on requests.');

            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'length'        => 32,
                'comment'       => 'Unique identifier for an action',
            ]);
            $table->addColumn('request_id', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'Agreement request ID. References oc_done_agr_requests.id',
            ]);
            $table->addColumn('line_id', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'Line ID where action was performed. References oc_done_agr_scheme_lines.id',
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'User who performed the action. References oc_done_users_data.id',
            ]);
            $table->addColumn('action', 'string', [
                'notnull' => true,
                'length'  => 20,
                'comment' => 'Action type: "approved", "rejected", "returned"',
            ]);
            $table->addColumn('comment', 'text', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Comment from approver (required for reject/return)',
            ]);
            $table->addColumn('created_at', 'datetime_immutable', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Record creation timestamp in UTC',
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['request_id'], 'agr_actions_request_id_idx');
            $table->addIndex(['line_id'], 'agr_actions_line_id_idx');
            $table->addIndex(['user_id'], 'agr_actions_user_id_idx');
            $table->addIndex(['action'], 'agr_actions_action_idx');
            $table->addIndex(['created_at'], 'agr_actions_created_at_idx');
        }

        // Agreement request history (audit trail of all changes)
        if (!$schema->hasTable('done_agr_req_history')) {
            $table = $schema->createTable('done_agr_req_history');
            $table->setComment('Agreement request history: audit trail of all changes to requests.');

            $table->addColumn('id', 'string', [
                'autoincrement' => false,
                'notnull'       => true,
                'length'        => 32,
                'comment'       => 'Unique identifier for a history entry',
            ]);
            $table->addColumn('request_id', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'Agreement request ID. References oc_done_agr_requests.id',
            ]);
            $table->addColumn('event_type', 'string', [
                'notnull' => true,
                'length'  => 30,
                'comment' => 'Type of event: created, status_changed, line_changed, action_performed',
            ]);
            $table->addColumn('status_before', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 20,
                'comment' => 'Status before the change (null for new requests)',
            ]);
            $table->addColumn('status_after', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 20,
                'comment' => 'Status after the change',
            ]);
            $table->addColumn('line_id_before', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'Line ID before the change. References oc_done_agr_scheme_lines.id',
            ]);
            $table->addColumn('line_id_after', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'Line ID after the change. References oc_done_agr_scheme_lines.id',
            ]);
            $table->addColumn('action', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 20,
                'comment' => 'Action performed: approved, rejected, returned (if event_type is action_performed)',
            ]);
            $table->addColumn('changed_by', 'string', [
                'notnull' => true,
                'length'  => 32,
                'comment' => 'User who made the change. References oc_done_users_data.id',
            ]);
            $table->addColumn('comment', 'text', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Comment for the change',
            ]);
            $table->addColumn('created_at', 'datetime_immutable', [
                'notnull' => false,
                'default' => null,
                'comment' => 'Record creation timestamp in UTC',
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['request_id'], 'agr_hist_request_id_idx');
            $table->addIndex(['event_type'], 'agr_hist_event_type_idx');
            $table->addIndex(['changed_by'], 'agr_hist_changed_by_idx');
            $table->addIndex(['created_at'], 'agr_hist_created_at_idx');
        }

        return $schema;
    }

    /**
     * Insert default vacation types after schema is created
     */
    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void
    {
        // Insert default vacation types if table is empty
        $qb = $this->connection->getQueryBuilder();

        // Check if types already exist
        $result = $qb->select($qb->func()->count('*', 'cnt'))
            ->from('done_vacations_types')
            ->executeQuery();
        $count = (int)$result->fetchOne();
        $result->closeCursor();

        if ($count > 0) {
            return;
        }

        $defaultTypes = [
            ['name' => 'Paid Leave', 'color' => '#4CAF50', 'sort' => 1, 'default_days' => 28],
            ['name' => 'Day-off', 'color' => '#2196F3', 'sort' => 2, 'default_days' => 5],
            ['name' => 'Compensatory Leave', 'color' => '#9C27B0', 'sort' => 3, 'default_days' => 0],
            ['name' => 'Sick Leave', 'color' => '#FF5722', 'sort' => 4, 'default_days' => 0],
            ['name' => 'Unpaid Leave', 'color' => '#607D8B', 'sort' => 5, 'default_days' => 0],
        ];

        $now = new \DateTimeImmutable();

        foreach ($defaultTypes as $type) {
            $typeId = md5($type['name'] . $now->format('Y-m-d H:i:s.u'));

            // Insert vacation type
            $qb = $this->connection->getQueryBuilder();
            $qb->insert('done_vacations_types')
                ->values([
                    'id'         => $qb->createNamedParameter($typeId),
                    'name'       => $qb->createNamedParameter($type['name']),
                    'color'      => $qb->createNamedParameter($type['color']),
                    'sort'       => $qb->createNamedParameter($type['sort']),
                    'deleted'    => $qb->createNamedParameter(false, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_BOOL),
                    'created_at' => $qb->createNamedParameter($now, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
                    'updated_at' => $qb->createNamedParameter($now, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
                ])
                ->executeStatement();

            // Insert default settings for types that have limits
            if ($type['default_days'] > 0) {
                $settingId = md5($typeId . '_setting_' . $now->format('Y-m-d H:i:s.u'));

                $qb = $this->connection->getQueryBuilder();
                $qb->insert('done_vac_type_settings')
                    ->values([
                        'id'              => $qb->createNamedParameter($settingId),
                        'type_id'         => $qb->createNamedParameter($typeId),
                        'value'           => $qb->createNamedParameter($type['default_days']),
                        'display_warning' => $qb->createNamedParameter(true, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_BOOL),
                        'created_at'      => $qb->createNamedParameter($now, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
                        'updated_at'      => $qb->createNamedParameter($now, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_DATETIME_IMMUTABLE),
                    ])
                    ->executeStatement();
            }

            // Small delay to ensure unique IDs
            usleep(1000);
        }
    }
}
