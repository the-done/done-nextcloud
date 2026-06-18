<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Vacations\Model;

use OCA\Done\Models\BaseModel;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class VacationsTypeSettingsModel.
 */
class VacationsTypeSettingsModel extends BaseModel
{
    public string $table = 'done_vac_type_settings';
    public string $modelTitle = 'Settings for employee vacation types';
    public string $modelName = 'vacationsTypeSettingsModel';
    public string $dbTableComment = 'Settings for employee vacation types.';

    protected array $hashFields = [
        'type_id',
        'value',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Unique identifier for a type setting entry',
        ],
        'type_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Vacation type',
            'required'   => true,
            'link'       => VacationsTypesModel::class,
            'db_comment' => 'Vacation type ID. References oc_done_vacations_types.id',
            'show'       => true,
        ],
        'value' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Setting value',
            'required'   => true,
            'db_comment' => 'Default days limit for this vacation type per year',
            'show'       => true,
        ],
        'display_warning' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Display a warning?',
            'required'   => false,
            'db_comment' => 'Display warning when approaching/exceeding limit',
            'show'       => true,
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
