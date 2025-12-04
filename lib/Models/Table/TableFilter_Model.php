<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Models\Table;

use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class TableFilter_Model.
 */
class TableFilter_Model extends TableSettings_Model
{
    public string $table = 'done_tables_filter';
    public string $modelTitle = 'Custom table settings';
    public string $modelName = 'tablesFilterModel';
    public string $dbTableComment = 'Table filtering settings: saved user filters or global filters.';

    protected array $hashFields = [
        'source',
        'user_id',
        'column',
        'condition',
        'value',
    ];

    public const IS_CONDITION = 1;
    public const IS_NOT_CONDITION = 2;
    public const LIKE_CONDITION = 3;
    public const IS_NOT_LIKE_CONDITION = 4;
    public const IS_EMPTY_CONDITION = 5;
    public const IS_NOT_EMPTY_CONDITION = 6;
    //    public const STARTS_WITH_CONDITION = 5;
    //    public const ENDS_WITH_CONDITION = 6;
    public const CONTAINS_CONDITION = 7;
    public const DOES_NOT_CONTAIN_CONDITION = 8;

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'required'   => true,
            'db_comment' => 'Unique identifier for a filter setting',
        ],
        'user_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'User',
            'required'   => false,
            'db_comment' => 'User ID (null if the setting is for all users). References oc_done_users_data.id',
        ],
        'source' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Section',
            'required'   => true,
            'db_comment' => 'Identifier of the application section. Possible values: 1 (Users), 2 (Projects), 3 (Teams), 4 (Payments)',
        ],
        'column' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Column',
            'required'   => true,
            'db_comment' => 'Technical name of the column to filter by',
        ],
        'condition' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Filter condition',
            'required'   => true,
            'db_comment' => 'Filter condition type. Possible values: 1 (is), 2 (is not), 3 (contains), 4 (does not contain), 5 (is empty), 6 (is not empty), 7 (includes), 8 (does not include)',
        ],
        'value' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Filter value',
            'required'   => false,
            'db_comment' => 'Value to filter by',
        ],
        'for_all' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'For all',
            'required'   => false,
            'db_comment' => 'Flag to apply the filter to all users (1 - for all, 0 - only for the specified `user_id`)',
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

    public static function getConditions(): array
    {
        return [
            [
                'name'      => 'Is',
                'value'     => self::IS_CONDITION,
                'showInput' => true,
                'multiple'  => false,
            ],
            [
                'name'      => 'Is not',
                'value'     => self::IS_NOT_CONDITION,
                'showInput' => true,
                'multiple'  => false,
            ],
            [
                'name'      => 'Is like',
                'value'     => self::LIKE_CONDITION,
                'showInput' => true,
                'multiple'  => false,
            ],
            [
                'name'      => 'Is not like',
                'value'     => self::IS_NOT_LIKE_CONDITION,
                'showInput' => true,
                'multiple'  => false,
            ],
            [
                'name'      => 'Contains',
                'value'     => self::CONTAINS_CONDITION,
                'showInput' => true,
                'multiple'  => true,
            ],
            [
                'name'      => 'Does not contain',
                'value'     => self::DOES_NOT_CONTAIN_CONDITION,
                'showInput' => true,
                'multiple'  => true,
            ],
            [
                'name'      => 'Is empty',
                'value'     => self::IS_EMPTY_CONDITION,
                'showInput' => false,
                'multiple'  => false,
            ],
            [
                'name'      => 'Is not empty',
                'value'     => self::IS_NOT_EMPTY_CONDITION,
                'showInput' => false,
                'multiple'  => false,
            ],
            // self::STARTS_WITH_CONDITION      => 'Starts with',
            // self::ENDS_WITH_CONDITION        => 'Ends with',
        ];
    }

    public static function makeColumnFilter(string $column, int $condition, array | int | string | null $value = null): array
    {
        $filterInner = [];

        switch ($condition) {
            case self::IS_CONDITION:
                $filterInner[$column] = $value;
                break;

            case self::IS_NOT_CONDITION:
                $filterInner[$column] = ['!=', $value];
                break;

            case self::LIKE_CONDITION:
                $filterInner[$column] = [
                    'LIKE',
                    $value,
                    IQueryBuilder::PARAM_STR,
                ];
                break;

            case self::IS_NOT_LIKE_CONDITION:
                $filterInner[$column] = [
                    'NOT LIKE',
                    $value,
                    IQueryBuilder::PARAM_STR,
                ];
                break;

            case self::CONTAINS_CONDITION:
                $value = explode(',', $value);
                $filterInner[$column] = [
                    'IN',
                    $value,
                    IQueryBuilder::PARAM_STR_ARRAY,
                ];
                break;

            case self::DOES_NOT_CONTAIN_CONDITION:
                $value = explode(',', $value);
                $filterInner[$column] = [
                    'NOT IN',
                    $value,
                    IQueryBuilder::PARAM_STR_ARRAY,
                ];
                break;

            case self::IS_EMPTY_CONDITION:
                $filterInner[$column] = ['OR', [['IS NULL'], ['=', '']]];
                break;

            case self::IS_NOT_EMPTY_CONDITION:
                $filterInner[$column] = ['AND', [['IS NOT NULL'], ['!=', '']]];
                break;
                //            case self::STARTS_WITH_CONDITION:
                //                break;
                //            case self::ENDS_WITH_CONDITION:
                //                break;
        }

        $filterPublic = [
            'condition' => $condition,
            'value'     => $value,
        ];

        return [$filterInner, $filterPublic];
    }
}
