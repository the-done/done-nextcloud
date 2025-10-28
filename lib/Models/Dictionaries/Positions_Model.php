<?php

namespace OCA\Done\Models\Dictionaries;

use OCA\Done\Models\Base_Model;
use OCA\Done\Service\BaseService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class Positions_Model.
 */
class Positions_Model extends Base_Model
{
    public string $table = 'done_positions';
    public string $modelTitle = 'Positions';
    public string $modelName = 'positionsDictionary';
    public string $dbTableComment = 'Lookup table: stores the names of employee positions. Uses soft-delete.';

    protected array $hashFields = [
        'name',
        'sort',
    ];

    public array $fields = [
        'id'         => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Internal unique key for an employee position'
        ],
        'name'       => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Position name',
            'required'         => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Name of the employee position'
        ],
        'sort'       => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Sorting',
            'db_comment' => 'Sort order number for the record'
        ],
        'deleted'    => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Deleted',
            'db_comment' => 'Soft-delete flag (1 - deleted, 0 - active). Deleted records should be excluded from queries.'
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