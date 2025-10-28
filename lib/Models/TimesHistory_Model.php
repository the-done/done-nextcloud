<?php

namespace OCA\Done\Models;

use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class TimesHistory_Model.
 */
class TimesHistory_Model extends Base_Model
{
    public string $table = 'done_times_history';
    public string $modelTitle = 'Change history for time reports';
    public string $modelName = 'timesHistoryModel';
    public string $dbTableComment = 'Change history for time reports: logs for auditing and tracking modifications.';

    public array $fields = [
        'id'         => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'ID',
            'required'   => true,
            'db_comment' => 'Unique identifier for a change history record',
        ],
        'report_id'  => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Report ID',
            'required'   => true,
            'db_comment' => 'Time report ID. References oc_done_times_data.id',
        ],
        'datetime'   => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Date',
            'required'   => true,
            'db_comment' => 'Timestamp of the change in UTC',
        ],
        'action'     => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Action',
            'required'   => true,
            'db_comment' => 'Type of action (e.g., update, delete)',
        ],
        'field'      => [
            'type'       => IQueryBuilder::PARAM_LOB,
            'title'      => 'Field',
            'required'   => true,
            'db_comment' => 'Name of the modified field',
        ],
        'valold'     => [
            'type'       => IQueryBuilder::PARAM_LOB,
            'title'      => 'Old value',
            'required'   => true,
            'db_comment' => 'Old value of the field',
        ],
        'valnew'     => [
            'type'       => IQueryBuilder::PARAM_LOB,
            'title'      => 'New value',
            'required'   => true,
            'db_comment' => 'New value of the field',
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