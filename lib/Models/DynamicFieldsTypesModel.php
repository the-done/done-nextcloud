<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Models;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;

/**
 * Class DynamicFieldsTypesModel.
 */
class DynamicFieldsTypesModel extends BaseModel
{
    public const INTEGER = 1;
    public const FLOAT = 2;
    public const STRING = 3;
    public const TEXT = 4;
    public const DATE = 5;
    public const DATETIME = 6;
    public const DROPDOWN = 7;
    public const BOOL = 8;

    /**
     * Get list of dynamic field types mapped to Nextcloud types
     *
     * @return array<int,int|string>
     */
    public static function getComparedFieldsTypes(): array
    {
        return [
            self::INTEGER  => IQueryBuilder::PARAM_INT,
            self::FLOAT    => IQueryBuilder::PARAM_STR,
            self::STRING   => IQueryBuilder::PARAM_STR,
            self::TEXT     => IQueryBuilder::PARAM_LOB,
            self::DATE     => IQueryBuilder::PARAM_DATE_IMMUTABLE,
            self::DATETIME => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            self::BOOL     => IQueryBuilder::PARAM_BOOL,
            self::DROPDOWN => IQueryBuilder::PARAM_STR,
        ];
    }

    /**
     * Get list of dynamic field types
     *
     * @return array<int,string>
     */
    public static function getFieldsTypes(): array
    {
        return [
            self::INTEGER  => 'Integer',
            self::FLOAT    => 'Float',
            self::STRING   => 'String',
            self::TEXT     => 'Text',
            self::DATE     => 'Date',
            self::DATETIME => 'Date and time',
            self::BOOL     => 'Boolean',
            self::DROPDOWN => 'Drop down list',
        ];
    }
}
