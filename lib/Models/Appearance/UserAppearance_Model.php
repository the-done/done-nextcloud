<?php

namespace OCA\Done\Models\Appearance;

use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class UserAppearance_Model.
 */
class UserAppearance_Model extends Appearance_Model
{
    public string $table = 'done_user_appearances';
    public string $modelTitle = 'User appearance elements';
    public string $modelName = 'userAppearanceModel';
    public string $dbTableComment = 'User appearances: avatars, symbols, colors, background images. Uses soft-delete.';

    protected array $hashFields = [
        'user_id',
    ];

    public array $fields = [
        'id'         => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Unique identifier for a user appearance record'
        ],
        'user_id'    => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'User',
            'required'   => true,
            'permission' => true,
            'db_comment' => 'User ID. References oc_done_users_data.id'
        ],
        'avatar'     => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Avatar',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'URL of the user avatar'
        ],
        'symbol'     => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Symbol',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'URL of user symbol (emoji)'
        ],
        'bg_image'   => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Background image',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'URL of the user card background image'
        ],
        'color'      => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Color',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'User card color in HEX format (e.g., #RRGGBB)'
        ],
        'created_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Appearance of user created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC'
        ],
        'updated_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Appearance of user updated at',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC'
        ],
        'deleted'    => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Deleted',
            'db_comment' => 'Soft-delete flag (1 - deleted, 0 - active). Deleted records should be excluded from queries.'
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
