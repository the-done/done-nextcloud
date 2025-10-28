<?php

namespace OCA\Done\Models;

use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class TimesLog_Model.
 */
class TimesLog_Model extends Base_Model
{
    public string $table = 'done_times_log';
    public string $modelTitle = 'Employee Time Tracking Log';
    public string $modelName = 'timesLogModel';

    protected array $hashFields = [
        'report_id', 'status_id'
    ];

    public array $fields = [
        'id'         => [
            'type' => IQueryBuilder::PARAM_STR,
            'title' => 'ID',
            'required' => true
        ],
        'report_id'  => [
            'type' => IQueryBuilder::PARAM_STR,
            'title' => 'Report ID',
            'required' => true
        ],
        'status_id'  => [
            'type' => IQueryBuilder::PARAM_INT,
            'title' => 'Report status ID',
            'required' => true
        ],
        'comment'    => [
            'type' => IQueryBuilder::PARAM_LOB,
            'title' => 'Comment',
            'required' => false,
            'validation_rules' => [
                'trim' => true,
            ],
        ],
        'created_at'  => [
            'type'     => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'    => 'Created at',
            'required' => false,
        ],
        'updated_at'  => [
            'type'     => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'    => 'Updated at',
            'required' => false,
        ],
    ];
}