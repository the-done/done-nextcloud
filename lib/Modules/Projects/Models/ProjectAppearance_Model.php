<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Projects\Models;

use OCA\Done\Models\Appearance\Appearance_Model;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class ProjectAppearance_Model.
 */
class ProjectAppearance_Model extends Appearance_Model
{
    public string $table = 'done_pr_appearances';
    public string $modelTitle = 'Project appearance elements';
    public string $modelName = 'projectAppearanceModel';
    public string $dbTableComment = 'Project appearances: avatars, symbols, colors, background images. Uses soft-delete.';

    protected array $hashFields = [
        'project_id',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Unique identifier for a project appearance record',
        ],
        'project_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Project',
            'required'   => true,
            'permission' => true,
            'db_comment' => 'Project ID. References oc_done_projects.id',
        ],
        'avatar' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Avatar',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'URL of the project avatar',
        ],
        'symbol' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Symbol',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Project symbol (emoji)',
        ],
        'bg_image' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Background image',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'URL of the project background image',
        ],
        'color' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Color',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Project color in HEX format (e.g., #RRGGBB)',
        ],
        'created_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Appearance of project created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC',
        ],
        'updated_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Appearance of project updated at',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC',
        ],
        'deleted' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Deleted',
            'db_comment' => 'Soft-delete flag (1 - deleted, 0 - active). Deleted records should be excluded from queries.',
        ],
    ];

    public array $appearanceFields = [
        'avatar',
        'symbol',
        'bg_image',
        'color',
    ];

    public array $appearanceFieldsWithFile = [
        'avatar',
        'symbol',
        'bg_image',
    ];
}
