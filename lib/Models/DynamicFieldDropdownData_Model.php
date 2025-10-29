<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


namespace OCA\Done\Models;

use OCA\Done\Service\BaseService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class DynamicFieldDropdownData_Model.
 */
class DynamicFieldDropdownData_Model extends DynamicFieldsDataAbstract_Model
{
    public string $table = 'done_dyn_ddown_data';
    public string $modelTitle = 'Dynamic dropdown fields data';
    public string $modelName = 'dynamicDropdownFieldsDataModel';
    public string $dbTableComment = 'Dynamic Dropdown fields data (EAV): stores selected option ID for dropdown fields, linked to records in other tables.';

    protected array $hashFields = [
        'dyn_field_id',
        'record_id',
        'option_id',
    ];

    public array $fields = [
        'id'           => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Internal unique key for a record containing a dynamic field value'
        ],
        'dyn_field_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Dynamic field id',
            'required'   => true,
            'db_comment' => 'Dynamic field ID. References oc_done_dynamic_fields.id'
        ],
        'record_id'    => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Record id',
            'required'   => true,
            'db_comment' => 'Polymorphic reference to the record ID to which the value is linked. The entity type is determined by the `source` field in the `oc_done_dynamic_fields` table'
        ],
        'option_id'      => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Option id',
            'required'   => false,
            'db_comment' => 'Option id (used if the field type in `oc_done_dynamic_fields` is DROPDOWN)'
        ],
        'created_at'   => [
            'type'       => IQueryBuilder::PARAM_DATE_IMMUTABLE,
            'title'      => 'Created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC'
        ],
        'updated_at'   => [
            'type'       => IQueryBuilder::PARAM_DATE_IMMUTABLE,
            'title'      => 'Updated at',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC'
        ],
    ];
}