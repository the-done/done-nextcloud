<?php

namespace OCA\Done\Models\Table;

use OCA\Done\Models\Base_Model;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class TableColumnViewSettings_Model.
 */
class TableColumnViewSettings_Model extends TableSettings_Model
{
    public string $table = 'done_table_column_view';
    public string $modelTitle = 'Table column parameters';
    public string $modelName = 'tableColumnView';
    public string $dbTableComment = 'Settings for table column display: width, visibility, for a specific user or for all.';

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
            'db_comment' => 'Unique identifier for a column view setting'
        ],
        'user_id'    => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'User',
            'required'   => false,
            'db_comment' => 'User ID (null if setting applies to all users). References oc_done_users_data.id'
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
            'db_comment' => 'Technical name of the table column to which the setting applies'
        ],
        'width'      => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Width',
            'required'   => false,
            'db_comment' => 'Column width in pixels'
        ],
        'hide'       => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Hide column',
            'required'   => false,
            'db_comment' => 'Flag to hide the column (1 - hidden, 0 - visible)'
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