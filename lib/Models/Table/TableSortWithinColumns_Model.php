<?php

namespace OCA\Done\Models\Table;

use OCA\Done\Models\Base_Model;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class TableSortWithinColumns_Model.
 */
class TableSortWithinColumns_Model extends TableSettings_Model
{
    public string $table = 'done_table_s_w_columns';
    public string $modelTitle = 'Sorting values within table columns';
    public string $modelName = 'tableSortWithinColumns';
    public string $dbTableComment = 'Settings for sorting data within table columns: sort direction and order.';

    protected array $hashFields = [
        'source',
        'user_id',
        'column',
        'ordering',
    ];

    public array $fields = [
        'id'            => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'required'   => true,
            'db_comment' => 'Unique identifier for a sorting setting',
        ],
        'user_id'       => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'User',
            'required'   => false,
            'db_comment' => 'User ID (null if the setting is for all users). References oc_done_users_data.id',
        ],
        'source'        => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Section',
            'required'   => true,
            'db_comment' => 'Identifier of the application section. Possible values: 1 (Users), 2 (Projects), 3 (Teams), 4 (Payments)',
        ],
        'column'        => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Column',
            'required'   => true,
            'db_comment' => 'Technical name of the column to sort by',
        ],
        'sort'          => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Sorting',
            'required'   => false,
            'db_comment' => 'Sort direction (1 - ASC, 0 - DESC)',
        ],
        'sort_ordering' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Order of sorting',
            'required'   => false,
            'db_comment' => 'Sort order number (for multi-column sorting)',
        ],
        'for_all'       => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'For all',
            'required'   => false,
            'db_comment' => 'Flag to apply settings to all users (1 - for all, 0 - only for the specified `user_id`)',
        ],
        'created_at'    => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC',
        ],
        'updated_at'    => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Updated at',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC',
        ],
    ];
}