<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Migration;

use Doctrine\DBAL\Schema\SchemaException;
use OCA\Done\Models\Dictionaries\GlobalRoles_Model;
use OCA\Done\Models\DynamicFields_Model;
use OCA\Done\Models\DynamicFieldsTypes_Model;
use OCA\Done\Models\FieldComment_Model;
use OCA\Done\Models\PermissionsEntities_Model;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Initial migration for public release
 * Creates all tables based on model definitions with proper comments
 */
class Version1000Date20251017000001 extends SimpleMigrationStep
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

        if (!$schema->hasTable('done_user_appearances')) {
            $table = $schema->createTable('done_user_appearances');
            $table->setComment('User appearances: avatars, symbols, colors, background images. Uses soft-delete.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a user appearance record',
                ]
            )->setLength(32);
            $table->addColumn(
                'user_id',
                'string',
                ['notnull' => true, 'comment' => 'User ID. References oc_done_users_data.id']
            )->setLength(32);
            $table->addColumn('avatar', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
            $table->addColumn('symbol', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
            $table->addColumn('bg_image', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
            $table->addColumn('color', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
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

        if (!$schema->hasTable('done_c_settings_data')) {
            $table = $schema->createTable('done_c_settings_data');
            $table->setComment('User application settings data: setting values, types, and user preferences.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Internal unique key for a user',
                ]
            )->setLength(32);
            $table->addColumn(
                'user_id',
                'string',
                ['notnull' => true, 'comment' => 'User ID. References oc_done_users_data.id']
            )->setLength(32);
            $table->addColumn(
                'setting_id',
                'integer',
                [
                    'notnull' => true,
                    'comment' => 'Setting identifier. Possible values: 1 (Cache Time), 2 (Hide empty fields in preview), 3 (User Language)',
                ]
            );
            $table->addColumn(
                'type_id',
                'integer',
                [
                    'notnull' => true,
                    'comment' => 'Setting value type. Possible values: 1 (Checkbox), 2 (String), 3 (Number), 4 (Select), 5 (Textarea)',
                ]
            );
            $table->addColumn(
                'value',
                'string',
                ['notnull' => false, 'default' => '', 'comment' => 'The value of the user setting']
            )->setLength(255);
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

        if (!$schema->hasTable('done_contracts')) {
            $table = $schema->createTable('done_contracts');
            $table->setComment(
                'Lookup table: stores employee contract types (e.g., Full-time, Contractor). Uses soft-delete.'
            );

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Internal unique key for an employee contract type',
                ]
            )->setLength(32);
            $table->addColumn('name', 'string', ['notnull' => true])->setLength(255);
            $table->addColumn(
                'sort',
                'integer',
                ['notnull' => false, 'default' => 0, 'comment' => 'Sort order number for the record']
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

        if (!$schema->hasTable('done_customers')) {
            $table = $schema->createTable('done_customers');
            $table->setComment('Lookup table: stores the names of the company\\');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Internal unique key for a customer',
                ]
            )->setLength(32);
            $table->addColumn('name', 'string', ['notnull' => true])->setLength(255);
            $table->addColumn(
                'sort',
                'integer',
                ['notnull' => false, 'default' => 0, 'comment' => 'Sort order number for the record']
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

        if (!$schema->hasTable('done_directions')) {
            $table = $schema->createTable('done_directions');
            $table->setComment('Lookup table: stores the names of the company\\');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Internal unique key for a company department or direction',
                ]
            )->setLength(32)->setAutoincrement(false);
            $table->addColumn('name', 'string', ['notnull' => true])->setLength(255);
            $table->addColumn(
                'sort',
                'integer',
                ['notnull' => false, 'default' => 0, 'comment' => 'Sort order number for the record']
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

        if (!$schema->hasTable('done_global_roles')) {
            $table = $schema->createTable('done_global_roles');
            $table->setComment('Lookup table: stores the names of global user roles. Uses soft-delete.');

            $table->addColumn(
                'id',
                'integer',
                [
                    'autoincrement' => true,
                    'notnull'       => true,
                    'comment'       => 'Internal unique key for a global role',
                ]
            );
            $table->addColumn('name', 'string', ['notnull' => true])->setLength(255);
            $table->addColumn(
                'sort',
                'integer',
                ['notnull' => false, 'default' => 0, 'comment' => 'Sort order number for the record']
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

        if (!$schema->hasTable('done_positions')) {
            $table = $schema->createTable('done_positions');
            $table->setComment('Lookup table: stores the names of employee positions. Uses soft-delete.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Internal unique key for an employee position',
                ]
            )->setLength(32);
            $table->addColumn('name', 'string', ['notnull' => true])->setLength(255);
            $table->addColumn(
                'sort',
                'integer',
                ['notnull' => false, 'default' => 0, 'comment' => 'Sort order number for the record']
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

        if (!$schema->hasTable('done_roles')) {
            $table = $schema->createTable('done_roles');
            $table->setComment('Lookup table: stores the names of employee roles in projects. Uses soft-delete.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Internal unique key for an employee role in a project',
                ]
            )->setLength(32);
            $table->addColumn('name', 'string', ['notnull' => true])->setLength(255);
            $table->addColumn(
                'sort',
                'integer',
                ['notnull' => false, 'default' => 0, 'comment' => 'Sort order number for the record']
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

        if (!$schema->hasTable('done_stages')) {
            $table = $schema->createTable('done_stages');
            $table->setComment(
                'Lookup table: stores the names of project stages (e.g., Presale, Development, Completed). Uses soft-delete.'
            );

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Internal unique key for a project stage',
                ]
            )->setLength(32);
            $table->addColumn('name', 'string', ['notnull' => true])->setLength(255);
            $table->addColumn(
                'sort',
                'integer',
                ['notnull' => false, 'default' => 0, 'comment' => 'Sort order number for the record']
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

        if (!$schema->hasTable('done_dyn_ddown_data')) {
            $table = $schema->createTable('done_dyn_ddown_data');
            $table->setComment(
                'Dynamic Dropdown fields data (EAV): stores selected option ID for dropdown fields, linked to records in other tables.'
            );

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Internal unique key for a record containing a dynamic field value',
                ]
            )->setLength(32);
            $table->addColumn(
                'dyn_field_id',
                'string',
                ['notnull' => true, 'comment' => 'Dynamic field ID. References oc_done_dynamic_fields.id']
            )->setLength(32);
            $table->addColumn(
                'record_id',
                'string',
                [
                    'notnull' => true,
                    'comment' => 'Polymorphic reference to the record ID to which the value is linked. The entity type is determined by the `source` field in the `oc_done_dynamic_fields` table',
                ]
            )->setLength(32);
            $table->addColumn(
                'option_id',
                'string',
                [
                    'notnull' => false,
                    'default' => '',
                    'comment' => 'Option id (used if the field type in `oc_done_dynamic_fields` is DROPDOWN)',
                ]
            )->setLength(32);
            $table->addColumn(
                'created_at',
                'date_immutable',
                ['notnull' => false, 'default' => null, 'comment' => 'Record creation timestamp in UTC']
            );
            $table->addColumn(
                'updated_at',
                'datetime_immutable',
                ['notnull' => false, 'default' => null, 'comment' => 'Record last update timestamp in UTC']
            );

            $table->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('done_dyn_ddown_options')) {
            $table = $schema->createTable('done_dyn_ddown_options');
            $table->setComment(
                'Options for Dynamic Dropdown fields (EAV): stores individual options with values and labels for dropdown fields.'
            );

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Internal unique key for a record containing a dynamic field value',
                ]
            )->setLength(32);
            $table->addColumn(
                'dyn_field_id',
                'string',
                ['notnull' => true, 'comment' => 'Dynamic field ID. References oc_done_dynamic_fields.id']
            )->setLength(32);
            $table->addColumn('option_label', 'string', ['notnull' => true, 'comment' => 'Option label'])->setLength(
                255
            );
            $table->addColumn(
                'ordering',
                'integer',
                [
                    'notnull' => false,
                    'default' => 0,
                    'comment' => 'Ordering option in option list for dynamic field type DROPDOWN',
                ]
            );
            $table->addColumn(
                'created_at',
                'date_immutable',
                ['notnull' => false, 'default' => null, 'comment' => 'Record creation timestamp in UTC']
            );
            $table->addColumn(
                'updated_at',
                'datetime_immutable',
                ['notnull' => false, 'default' => null, 'comment' => 'Record last update timestamp in UTC']
            );

            $table->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('done_dyn_fields_data')) {
            $table = $schema->createTable('done_dyn_fields_data');
            $table->setComment(
                'Dynamic fields data (EAV): stores values of various types, linked to records in other tables.'
            );

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Internal unique key for a record containing a dynamic field value',
                ]
            )->setLength(32);
            $table->addColumn(
                'dyn_field_id',
                'string',
                ['notnull' => true, 'comment' => 'Dynamic field ID. References oc_done_dynamic_fields.id']
            )->setLength(32);
            $table->addColumn(
                'record_id',
                'string',
                [
                    'notnull' => true,
                    'comment' => 'Polymorphic reference to the record ID to which the value is linked. The entity type is determined by the `source` field in the `oc_done_dynamic_fields` table',
                ]
            )->setLength(32);
            $table->addColumn(
                'int_val',
                'integer',
                [
                    'notnull' => false,
                    'default' => 0,
                    'comment' => 'Integer value (used if the field type in `oc_done_dynamic_fields` is INTEGER)',
                ]
            );
            $table->addColumn('string_val', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
            $table->addColumn('text_val', 'text', ['notnull' => false]);
            $table->addColumn(
                'date_val',
                'date_immutable',
                [
                    'notnull' => false,
                    'default' => null,
                    'comment' => 'Date value (used if the field type in `oc_done_dynamic_fields` is DATE)',
                ]
            );
            $table->addColumn(
                'created_at',
                'date_immutable',
                ['notnull' => false, 'default' => null, 'comment' => 'Record creation timestamp in UTC']
            );
            $table->addColumn(
                'updated_at',
                'datetime_immutable',
                ['notnull' => false, 'default' => null, 'comment' => 'Record last update timestamp in UTC']
            );

            $table->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('done_dynamic_fields')) {
            $table = $schema->createTable('done_dynamic_fields');
            $table->setComment(
                'Lookup table for dynamic fields: defines field settings, their data types, and which section they belong to.'
            );

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Internal unique key for a dynamic field',
                ]
            )->setLength(32);
            $table->addColumn('title', 'string', ['notnull' => true])->setLength(255);
            $table->addColumn(
                'field_type',
                'integer',
                [
                    'notnull' => true,
                    'comment' => 'Field type. Possible values: 1 (INTEGER), 2 (FLOAT), 3 (STRING), 4 (TEXT), 5 (DATE), 6 (DATETIME), 7 (DROPDOWN), 8 (DROPDOWN FROM SOURCE), 9 (BOOL)',
                ]
            );
            $table->addColumn(
                'source',
                'integer',
                [
                    'notnull' => true,
                    'comment' => 'Identifier of the application section the field belongs to. Possible values: 1 (Users), 2 (Projects), 3 (Teams), 4 (Payments)',
                ]
            );
            $table->addColumn(
                'required',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                    'comment' => 'Required field flag (1 - required, 0 - optional)',
                ]
            );
            $table->addColumn(
                'multiple',
                'boolean',
                ['notnull' => false, 'default' => false, 'comment' => 'Multiple field flag (1 - multiple, 0 - single)']
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

        if (!$schema->hasTable('done_field_comments')) {
            $table = $schema->createTable('done_field_comments');
            $table->setComment(
                'Comments for core and dynamic fields. Core fields are addressed by name; dynamic fields are addressed by dynamic field id (references oc_done_dynamic_fields.id).'
            );

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for comment',
                ]
            )->setLength(32);
            $table->addColumn(
                'source',
                'integer',
                [
                    'notnull' => true,
                    'comment' => 'Entity source identifier. See OCA\Done\Models\PermissionsEntities_Model constants (e.g., 1 - Users, 2 - Projects).',
                ]
            );
            $table->addColumn('field', 'string', ['notnull' => true])->setLength(255);
            $table->addColumn('comment', 'text', ['notnull' => true]);
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

        if (!$schema->hasTable('done_fields_orderings')) {
            $table = $schema->createTable('done_fields_orderings');
            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Internal unique key for a user',
                ]
            )->setLength(32);
            $table->addColumn(
                'user_id',
                'string',
                ['notnull' => true, 'comment' => 'User ID. References oc_done_users_data.id']
            )->setLength(32);
            $table->addColumn(
                'entity_id',
                'integer',
                [
                    'notnull' => true,
                    'comment' => 'Identifier of the application section. Possible values: 1 (Users), 2 (Projects), 3 (Teams), 4 (Payments)',
                ]
            );
            $table->addColumn(
                'field',
                'string',
                [
                    'notnull' => true,
                    'comment' => 'Technical name of the column from the table indicated by `entity_id` (e.g., `name` from `oc_done_projects`)',
                ]
            )->setLength(255);
            $table->addColumn(
                'ordering',
                'integer',
                ['notnull' => false, 'default' => 0, 'comment' => 'Ordering number of the field in the entity card']
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

        if (!$schema->hasTable('done_g_roles_permit')) {
            $table = $schema->createTable('done_g_roles_permit');
            $table->setComment('Access permissions for global roles to fields in various application sections.');

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
                ['notnull' => true, 'comment' => 'Global role identifier. References oc_done_global_roles.id']
            );
            $table->addColumn(
                'entity_id',
                'integer',
                [
                    'notnull' => true,
                    'comment' => 'Identifier of the application section. Possible values: 1 (Users), 2 (Projects), 3 (Teams), 4 (Payments)',
                ]
            );
            $table->addColumn(
                'field',
                'string',
                [
                    'notnull' => true,
                    'comment' => 'Technical name of the column from the table indicated by `entity_id` (e.g., `name` from `oc_done_projects`)',
                ]
            )->setLength(255);
            $table->addColumn(
                'can_view',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                    'comment' => 'Permission to view the field (1 - yes, 0 - no). If `can_view=1` and `can_read=0`, the user sees the field but not its value',
                ]
            );
            $table->addColumn(
                'can_read',
                'boolean',
                ['notnull' => false, 'default' => false, 'comment' => 'Permission to read the field']
            );
            $table->addColumn(
                'can_write',
                'boolean',
                ['notnull' => false, 'default' => false, 'comment' => 'Permission to change the field']
            );
            $table->addColumn(
                'can_delete',
                'boolean',
                ['notnull' => false, 'default' => false, 'comment' => 'Permission to delete the field']
            );
            $table->addColumn(
                'can_view_add_info',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                    'comment' => '[DEPRECATED] Permission to view additional information about the field (1 - yes, 0 - no)',
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

        if (!$schema->hasTable('done_table_column_view')) {
            $table = $schema->createTable('done_table_column_view');
            $table->setComment('Settings for table column display: width, visibility, for a specific user or for all.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a column view setting',
                ]
            )->setLength(32);
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => false,
                    'default' => '',
                    'comment' => 'User ID (null if setting applies to all users). References oc_done_users_data.id',
                ]
            )->setLength(32);
            $table->addColumn(
                'source',
                'integer',
                [
                    'notnull' => true,
                    'comment' => 'Identifier of the application section. Possible values: 1 (Users), 2 (Projects), 3 (Teams), 4 (Payments)',
                ]
            );
            $table->addColumn(
                'column',
                'string',
                ['notnull' => true, 'comment' => 'Technical name of the table column to which the setting applies']
            )->setLength(255);
            $table->addColumn(
                'width',
                'integer',
                ['notnull' => false, 'default' => 0, 'comment' => 'Column width in pixels']
            );
            $table->addColumn(
                'hide',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                    'comment' => 'Flag to hide the column (1 - hidden, 0 - visible)',
                ]
            );
            $table->addColumn(
                'for_all',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                    'comment' => 'Flag to apply settings to all users (1 - for all, 0 - only for the specified `user_id`)',
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

        if (!$schema->hasTable('done_tables_filter')) {
            $table = $schema->createTable('done_tables_filter');
            $table->setComment('Table filtering settings: saved user filters or global filters.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a filter setting',
                ]
            )->setLength(32);
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => false,
                    'default' => '',
                    'comment' => 'User ID (null if the setting is for all users). References oc_done_users_data.id',
                ]
            )->setLength(32);
            $table->addColumn(
                'source',
                'integer',
                [
                    'notnull' => true,
                    'comment' => 'Identifier of the application section. Possible values: 1 (Users), 2 (Projects), 3 (Teams), 4 (Payments)',
                ]
            );
            $table->addColumn(
                'column',
                'string',
                ['notnull' => true, 'comment' => 'Technical name of the column to filter by']
            )->setLength(255);
            $table->addColumn(
                'condition',
                'integer',
                [
                    'notnull' => true,
                    'comment' => 'Filter condition type. Possible values: 1 (is), 2 (is not), 3 (contains), 4 (does not contain), 5 (is empty), 6 (is not empty), 7 (includes), 8 (does not include)',
                ]
            );
            $table->addColumn(
                'value',
                'string',
                ['notnull' => false, 'default' => '', 'comment' => 'Value to filter by']
            )->setLength(255);
            $table->addColumn(
                'for_all',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                    'comment' => 'Flag to apply the filter to all users (1 - for all, 0 - only for the specified `user_id`)',
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

        if (!$schema->hasTable('done_table_s_columns')) {
            $table = $schema->createTable('done_table_s_columns');
            $table->setComment('Settings for the order of columns in tables for a specific user or for all.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a column order setting',
                ]
            )->setLength(32);
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => false,
                    'default' => '',
                    'comment' => 'User ID (null if the setting is for all users). References oc_done_users_data.id',
                ]
            )->setLength(32);
            $table->addColumn(
                'source',
                'integer',
                [
                    'notnull' => true,
                    'comment' => 'Identifier of the application section. Possible values: 1 (Users), 2 (Projects), 3 (Teams), 4 (Payments)',
                ]
            );
            $table->addColumn(
                'column',
                'string',
                ['notnull' => true, 'comment' => 'Technical name of the table column']
            )->setLength(255);
            $table->addColumn(
                'ordering',
                'integer',
                ['notnull' => true, 'comment' => 'Order number of the column in the table (from left to right)']
            );
            $table->addColumn(
                'for_all',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                    'comment' => 'Flag to apply settings to all users (1 - for all, 0 - only for the specified `user_id`)',
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

        if (!$schema->hasTable('done_table_s_w_columns')) {
            $table = $schema->createTable('done_table_s_w_columns');
            $table->setComment('Settings for sorting data within table columns: sort direction and order.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a sorting setting',
                ]
            )->setLength(32);
            $table->addColumn(
                'user_id',
                'string',
                [
                    'notnull' => false,
                    'default' => '',
                    'comment' => 'User ID (null if the setting is for all users). References oc_done_users_data.id',
                ]
            )->setLength(32);
            $table->addColumn(
                'source',
                'integer',
                [
                    'notnull' => true,
                    'comment' => 'Identifier of the application section. Possible values: 1 (Users), 2 (Projects), 3 (Teams), 4 (Payments)',
                ]
            );
            $table->addColumn(
                'column',
                'string',
                ['notnull' => true, 'comment' => 'Technical name of the column to sort by']
            )->setLength(255);
            $table->addColumn(
                'sort',
                'boolean',
                ['notnull' => false, 'default' => false, 'comment' => 'Sort direction (1 - ASC, 0 - DESC)']
            );
            $table->addColumn(
                'sort_ordering',
                'integer',
                ['notnull' => false, 'default' => 0, 'comment' => 'Sort order number (for multi-column sorting)']
            );
            $table->addColumn(
                'for_all',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                    'comment' => 'Flag to apply settings to all users (1 - for all, 0 - only for the specified `user_id`)',
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

        if (!$schema->hasTable('done_times_history')) {
            $table = $schema->createTable('done_times_history');
            $table->setComment('Change history for time reports: logs for auditing and tracking modifications.');

            $table->addColumn(
                'id',
                'integer',
                [
                    'autoincrement' => true,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a change history record',
                ]
            );
            $table->addColumn(
                'report_id',
                'string',
                ['notnull' => true, 'comment' => 'Time report ID. References oc_done_times_data.id']
            )->setLength(32);
            $table->addColumn(
                'datetime',
                'datetime_immutable',
                ['notnull' => true, 'comment' => 'Timestamp of the change in UTC']
            );
            $table->addColumn(
                'action',
                'string',
                ['notnull' => true, 'comment' => 'Type of action (e.g., update, delete)']
            )->setLength(255);
            $table->addColumn('field', 'text', ['notnull' => true, 'comment' => 'Name of the modified field']);
            $table->addColumn('valold', 'text', ['notnull' => true, 'comment' => 'Old value of the field']);
            $table->addColumn('valnew', 'text', ['notnull' => true, 'comment' => 'New value of the field']);
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

        if (!$schema->hasTable('done_times_log')) {
            $table = $schema->createTable('done_times_log');
            $table->addColumn('id', 'string', ['autoincrement' => false, 'notnull' => true])->setLength(32);
            $table->addColumn('report_id', 'string', ['notnull' => true])->setLength(32);
            $table->addColumn('status_id', 'integer', ['notnull' => true]);
            $table->addColumn('comment', 'text', ['notnull' => false]);
            $table->addColumn('created_at', 'datetime_immutable', ['notnull' => false, 'default' => null]);
            $table->addColumn(
                'updated_at',
                'datetime_immutable',
                ['notnull' => false, 'default' => null, 'comment' => 'Record last update timestamp in UTC']
            );

            $table->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('done_times_data')) {
            $table = $schema->createTable('done_times_data');
            $table->setComment('Employee time tracking: reports on time spent on projects and tasks.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a time tracking entry',
                ]
            )->setLength(32);
            $table->addColumn(
                'date',
                'date_immutable',
                ['notnull' => true, 'comment' => 'Date for which the time report is made']
            );
            $table->addColumn(
                'user_id',
                'string',
                ['notnull' => true, 'comment' => 'User (employee) ID. References oc_done_users_data.id']
            )->setLength(32);
            $table->addColumn(
                'project_id',
                'string',
                [
                    'notnull' => true,
                    'comment' => 'ID of the project the work was done for. References oc_done_projects.id',
                ]
            )->setLength(32);
            $table->addColumn('task_link', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
            $table->addColumn('description', 'text', ['notnull' => true]);
            $table->addColumn('comment', 'text', ['notnull' => false]);
            $table->addColumn(
                'minutes',
                'integer',
                ['notnull' => true, 'unsigned' => true, 'comment' => 'Number of minutes spent']
            );
            $table->addColumn(
                'is_downtime',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                    'comment' => 'Downtime flag (1 - yes, this was downtime, 0 - no, this was work time)',
                ]
            );
            $table->addColumn(
                'status_id',
                'integer',
                [
                    'notnull' => false,
                    'default' => 0,
                    'comment' => '[FUTURE] Status of the time report. Possible value: 1 (Sent). Other statuses are reserved.',
                ]
            );
            $table->addColumn(
                'sort',
                'integer',
                [
                    'notnull' => false,
                    'default' => 0,
                    'comment' => 'Sort order for time report entries within a single day',
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

        if (!$schema->hasTable('done_users_data')) {
            $table = $schema->createTable('done_users_data');
            $table->setComment('User data: employee profiles, contact information, HR data. Uses soft-delete.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a user (employee)',
                ]
            )->setLength(32);
            $table->addColumn(
                'user_id',
                'string',
                ['notnull' => false, 'default' => '', 'comment' => 'User UID (login) in the external Nextcloud system']
            )->setLength(255);
            $table->addColumn('user_display_name', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
            $table->addColumn('lastname', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
            $table->addColumn('name', 'string', ['notnull' => true])->setLength(255);
            $table->addColumn('middle_name', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
            $table->addColumn(
                'position_id',
                'string',
                ['notnull' => false, 'default' => '', 'comment' => 'Position ID. References oc_done_positions.id']
            )->setLength(32);
            $table->addColumn(
                'contract_type_id',
                'string',
                ['notnull' => false, 'default' => '', 'comment' => 'Contract type ID. References oc_done_contracts.id']
            )->setLength(32);
            $table->addColumn(
                'deleted',
                'boolean',
                [
                    'notnull' => false,
                    'default' => false,
                    'comment' => 'Soft-delete flag (1 - deleted, 0 - active). Deleted records should be excluded from queries.',
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

        if (!$schema->hasTable('done_users_roles_g')) {
            $table = $schema->createTable('done_users_roles_g');
            $table->setComment('Links users to global roles: defines a user\\');

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
                'user_id',
                'string',
                ['notnull' => true, 'comment' => 'User ID. References oc_done_users_data.id']
            )->setLength(32);
            $table->addColumn(
                'role_id',
                'integer',
                ['notnull' => true, 'comment' => 'Global role ID. References oc_done_global_roles.id']
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

        if (!$schema->hasTable('done_users_roles')) {
            $table = $schema->createTable('done_users_roles');
            $table->setComment(
                'Links users to roles in projects: defines which role an employee performs in a specific project.'
            );

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a user role assignment in a project',
                ]
            )->setLength(32);
            $table->addColumn(
                'user_id',
                'string',
                ['notnull' => true, 'comment' => 'User ID. References oc_done_users_data.id']
            )->setLength(32);
            $table->addColumn(
                'role_id',
                'string',
                ['notnull' => true, 'comment' => 'Role in project ID. References oc_done_roles.id']
            )->setLength(32);
            $table->addColumn(
                'project_id',
                'string',
                ['notnull' => true, 'comment' => 'Project ID. References oc_done_projects.id']
            )->setLength(32);
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

        if (!$schema->hasTable('done_userstodirections')) {
            $table = $schema->createTable('done_userstodirections');
            $table->setComment('Links users to directions: defines which company directions employees belong to.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for the user-to-direction link',
                ]
            )->setLength(32);
            $table->addColumn(
                'user_id',
                'string',
                ['notnull' => true, 'comment' => 'User ID. References oc_done_users_data.id']
            )->setLength(32);
            $table->addColumn(
                'direction_id',
                'string',
                ['notnull' => true, 'comment' => 'Direction ID. References oc_done_directions.id']
            )->setLength(32);
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

        if (!$schema->hasTable('done_pr_appearances')) {
            $table = $schema->createTable('done_pr_appearances');
            $table->setComment('Project appearances: avatars, symbols, colors, background images. Uses soft-delete.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a project appearance record',
                ]
            )->setLength(32);
            $table->addColumn(
                'project_id',
                'string',
                ['notnull' => true, 'comment' => 'Project ID. References oc_done_projects.id']
            )->setLength(32);
            $table->addColumn('avatar', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
            $table->addColumn('symbol', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
            $table->addColumn('bg_image', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
            $table->addColumn('color', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
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

        if (!$schema->hasTable('done_projects')) {
            $table = $schema->createTable('done_projects');
            $table->setComment('Company projects: core data, planning, management, budgeting. Uses soft-delete.');

            $table->addColumn(
                'id',
                'string',
                [
                    'autoincrement' => false,
                    'notnull'       => true,
                    'comment'       => 'Unique identifier for a project',
                ]
            )->setLength(32);
            $table->addColumn('name', 'string', ['notnull' => true])->setLength(255);
            $table->addColumn('nickname', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
            $table->addColumn('annotation', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
            $table->addColumn('description', 'string', ['notnull' => false, 'default' => ''])->setLength(255);
            $table->addColumn(
                'start_date_plan',
                'date_immutable',
                ['notnull' => false, 'default' => null, 'comment' => 'Planned project start date']
            );
            $table->addColumn(
                'direction_id',
                'string',
                ['notnull' => false, 'default' => '', 'comment' => 'Direction ID. References oc_done_directions.id']
            )->setLength(32);
            $table->addColumn(
                'owner_id',
                'string',
                ['notnull' => false, 'default' => '', 'comment' => 'Project owner ID. References oc_done_users_data.id']
            )->setLength(32);
            $table->addColumn(
                'stage_id',
                'string',
                ['notnull' => false, 'default' => '', 'comment' => 'Project stage ID. References oc_done_stages.id']
            )->setLength(32);
            $table->addColumn(
                'customer_id',
                'string',
                ['notnull' => false, 'default' => '', 'comment' => 'Customer ID. References oc_done_customers.id']
            )->setLength(32);
            $table->addColumn(
                'project_manager_id',
                'string',
                [
                    'notnull' => false,
                    'default' => '',
                    'comment' => 'Project manager ID. References oc_done_users_data.id',
                ]
            )->setLength(32);
            $table->addColumn(
                'project_head_id',
                'string',
                ['notnull' => false, 'default' => '', 'comment' => 'Project head ID. References oc_done_users_data.id']
            )->setLength(32);
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

        return $schema;
    }

    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $globalRolesModel = new GlobalRoles_Model();

        if ($this->connection->tableExists('done_global_roles') && empty($globalRolesModel->getList())) {
            $query = $this->connection->getQueryBuilder();
            $query->insert('done_global_roles');
            $query->values([
                'id'   => $query->createNamedParameter(GlobalRoles_Model::ADMIN, IQueryBuilder::PARAM_INT),
                'name' => $query->createNamedParameter('Administrator', IQueryBuilder::PARAM_STR),
                'sort' => $query->createNamedParameter(1, IQueryBuilder::PARAM_INT),
            ]);
            $query->execute();

            $query->insert('done_global_roles');
            $query->values([
                'id'   => $query->createNamedParameter(GlobalRoles_Model::OFFICER, IQueryBuilder::PARAM_INT),
                'name' => $query->createNamedParameter('Director', IQueryBuilder::PARAM_STR),
                'sort' => $query->createNamedParameter(2, IQueryBuilder::PARAM_INT),
            ]);
            $query->execute();

            $query->insert('done_global_roles');
            $query->values([
                'id'   => $query->createNamedParameter(GlobalRoles_Model::HEAD, IQueryBuilder::PARAM_INT),
                'name' => $query->createNamedParameter('Manager', IQueryBuilder::PARAM_STR),
                'sort' => $query->createNamedParameter(3, IQueryBuilder::PARAM_INT),
            ]);
            $query->execute();

            $query->insert('done_global_roles');
            $query->values([
                'id'   => $query->createNamedParameter(GlobalRoles_Model::CURATOR, IQueryBuilder::PARAM_INT),
                'name' => $query->createNamedParameter('Curator', IQueryBuilder::PARAM_STR),
                'sort' => $query->createNamedParameter(4, IQueryBuilder::PARAM_INT),
            ]);
            $query->execute();

            $query->insert('done_global_roles');
            $query->values([
                'id'   => $query->createNamedParameter(GlobalRoles_Model::EMPLOYEE, IQueryBuilder::PARAM_INT),
                'name' => $query->createNamedParameter('Employee', IQueryBuilder::PARAM_STR),
                'sort' => $query->createNamedParameter(5, IQueryBuilder::PARAM_INT),
            ]);
            $query->execute();

            $query->insert('done_global_roles');
            $query->values([
                'id'   => $query->createNamedParameter(GlobalRoles_Model::FINANCE, IQueryBuilder::PARAM_INT),
                'name' => $query->createNamedParameter('Finance', IQueryBuilder::PARAM_STR),
                'sort' => $query->createNamedParameter(6, IQueryBuilder::PARAM_INT),
            ]);
            $query->execute();
        }

        if (
            $schema->hasTable('done_projects')
            && $schema->hasTable('done_users_data')
            && $schema->hasTable('done_dynamic_fields')
        ) {
            $dynamicFieldsModel = new DynamicFields_Model();
            $fieldCommentModel = new FieldComment_Model();

            $dynFields = [
                [
                    'title'      => 'Annotation',
                    'field_type' => DynamicFieldsTypes_Model::STRING,
                    'source'     => PermissionsEntities_Model::PROJECT_ENTITY,
                    'required'   => false,
                    'multiple'   => false,
                    'comment'    => 'Brief project annotation',
                ],
                [
                    'title'      => 'Description',
                    'field_type' => DynamicFieldsTypes_Model::STRING,
                    'source'     => PermissionsEntities_Model::PROJECT_ENTITY,
                    'required'   => false,
                    'multiple'   => false,
                    'comment'    => 'Detailed project description',
                ],
                [
                    'title'      => 'Planned start date',
                    'field_type' => DynamicFieldsTypes_Model::DATE,
                    'source'     => PermissionsEntities_Model::PROJECT_ENTITY,
                    'required'   => false,
                    'multiple'   => false,
                    'comment'    => 'Planned project start date',
                ],
            ];

            foreach ($dynFields as $dynField) {
                $dynamicFieldsModel->addData(
                    [
                        'title'      => $dynField['title'],
                        'field_type' => $dynField['field_type'],
                        'source'     => $dynField['source'],
                        'required'   => $dynField['required'],
                        'multiple'   => $dynField['multiple'],
                    ]
                );

                $dynFieldId = $dynamicFieldsModel->getItemByFilter([
                    'title'      => $dynField['title'],
                    'field_type' => $dynField['field_type'],
                    'source'     => $dynField['source'],
                    'required'   => $dynField['required'],
                    'multiple'   => $dynField['multiple'],
                ])['id'] ?? null;

                if ($dynFieldId) {
                    $fieldCommentModel->addData(
                        [
                            'source'  => $dynField['source'],
                            'field'   => $dynFieldId,
                            'comment' => $dynField['comment'],
                        ]
                    );
                }
            }
        }
    }
}
