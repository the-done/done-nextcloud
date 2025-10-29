<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


namespace OCA\Done\Models\Table;

use OCA\Done\Models\Base_Model;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class TableSortColumns_Model.
 */
class TableSortColumns_Model extends TableSettings_Model
{
    public string $table = 'done_table_s_columns';
    public string $modelTitle = 'Table columns sorting';
    public string $modelName = 'tableSortColumns';
    public string $dbTableComment = 'Settings for the order of columns in tables for a specific user or for all.';

    protected array $hashFields = [
        'source',
        'user_id',
        'column',
        'ordering',
    ];

    public array $fields = [
        'id'         => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'required'   => true,
            'db_comment' => 'Unique identifier for a column order setting'
        ],
        'user_id'    => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'User',
            'required'   => false,
            'db_comment' => 'User ID (null if the setting is for all users). References oc_done_users_data.id'
        ],
        'source'     => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Section',
            'required'   => true,
            'db_comment' => 'Identifier of the application section. Possible values: 1 (Users), 2 (Projects), 3 (Teams), 4 (Payments)'
        ],
        'column'     => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Column',
            'required'   => true,
            'db_comment' => 'Technical name of the table column'
        ],
        'ordering'   => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Sorting',
            'required'   => true,
            'db_comment' => 'Order number of the column in the table (from left to right)'
        ],
        'for_all'    => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'For all',
            'required'   => false,
            'db_comment' => 'Flag to apply settings to all users (1 - for all, 0 - only for the specified `user_id`)'
        ],
        'created_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC'
        ],
        'updated_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Updated at',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC'
        ],
    ];
}