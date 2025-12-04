<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Models\Dictionaries;

use OCA\Done\Models\Base_Model;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class Roles_Model.
 */
class Roles_Model extends Base_Model
{
    public string $table = 'done_roles';
    public string $modelTitle = 'Roles in projects';
    public string $modelName = 'rolesDictionary';
    public string $dbTableComment = 'Lookup table: stores the names of employee roles in projects. Uses soft-delete.';

    protected array $hashFields = [
        'name',
        'sort',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Internal unique key for an employee role in a project',
        ],
        'name' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Role name',
            'required'         => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Name of the employee role in a project',
        ],
        'sort' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Sort order',
            'db_comment' => 'Sort order number for the record',
        ],
        'deleted' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Deleted',
            'db_comment' => 'Soft-delete flag (1 - deleted, 0 - active). Deleted records should be excluded from queries.',
        ],
        'created_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC',
        ],
        'updated_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Updated at',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC',
        ],
    ];
}
