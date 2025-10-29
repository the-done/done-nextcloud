<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


namespace OCA\Done\Models\Dictionaries;

use OCA\Done\Models\Base_Model;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class Direction_Model.
 */
class Direction_Model extends Base_Model
{
    public string $table = 'done_directions';
    public string $modelTitle = 'Directions';
    public string $modelName = 'directionsDictionary';
    public string $dbTableComment = 'Lookup table: stores the names of the company\'s business directions. Uses soft-delete.';

    protected array $hashFields = [
        'name',
        'sort',
    ];

    public array $fields = [
        'id'         => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Internal unique key for a company department or direction'
        ],
        'name'       => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Direction name',
            'required'         => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Name of the company direction (e.g., Product Development)'
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