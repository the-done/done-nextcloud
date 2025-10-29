<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


namespace OCA\Done\Models\Dictionaries;

use OCA\Done\Models\Base_Model;
use OCA\Done\Service\BaseService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class Contracts_Model.
 */
class Contracts_Model extends Base_Model
{
    public string $table = 'done_contracts';
    public string $modelTitle = 'Contract types';
    public string $modelName = 'contractTypesDictionary';
    public string $dbTableComment = 'Lookup table: stores employee contract types (e.g., Full-time, Contractor). Uses soft-delete.';

    protected array $hashFields = [
        'name',
        'sort',
    ];

    public array $fields = [
        'id'         => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Internal unique key for an employee contract type'
        ],
        'name'       => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Contract type name',
            'required'         => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Name of the employee contract type'
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