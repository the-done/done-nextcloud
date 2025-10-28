<?php

namespace OCA\Done\Models;

use OCA\Done\Service\BaseService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class CustomSettingsData_Model.
 */
class CustomSettingsData_Model extends Base_Model
{
    public string $table = 'done_c_settings_data';
    public string $modelTitle = 'Custom settings data';
    public string $modelName = 'customSettingsDataModel';
    public string $dbTableComment = 'User application settings data: setting values, types, and user preferences.';

    protected array $hashFields = [
        'user_id',
        'setting_id',
        'type_id',
        'value',
    ];

    public array $fields = [
        'id'         => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'required'   => true,
            'db_comment' => 'Internal unique key for a user\'s custom setting record',
        ],
        'user_id'    => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'User id',
            'required'   => true,
            'db_comment' => 'User ID. References oc_done_users_data.id',
        ],
        'setting_id' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Setting ID',
            'required'   => true,
            'db_comment' => 'Setting identifier. Possible values: 1 (Cache Time), 2 (Hide empty fields in preview), 3 (User Language)',
        ],
        'type_id'    => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Setting type',
            'required'   => true,
            'db_comment' => 'Setting value type. Possible values: 1 (Checkbox), 2 (String), 3 (Number), 4 (Select), 5 (Textarea)',
        ],
        'value'      => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Setting value',
            'required'   => false,
            'db_comment' => 'The value of the user setting',
        ],
        'created_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Creation date',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC',
        ],
        'updated_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Update date',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC',
        ],
    ];
}