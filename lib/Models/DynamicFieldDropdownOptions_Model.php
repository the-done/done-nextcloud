<?php

namespace OCA\Done\Models;

use OCA\Done\Service\BaseService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class DynamicFieldDropdownData_Model.
 */
class DynamicFieldDropdownOptions_Model extends Base_Model
{
    public string $table = 'done_dyn_ddown_options';
    public string $modelTitle = 'Dynamic dropdown fields options';
    public string $modelName = 'dynamicFieldDropdownOptionsModel';
    public string $dbTableComment = 'Options for Dynamic Dropdown fields (EAV): stores individual options with values and labels for dropdown fields.';

    protected array $hashFields = [
        'dyn_field_id',
        'option_label',
    ];

    public array $fields = [
        'id'           => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Internal unique key for a record containing a dynamic field value'
        ],
        'dyn_field_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Dynamic field id',
            'required'   => true,
            'db_comment' => 'Dynamic field ID. References oc_done_dynamic_fields.id'
        ],
        'option_label'    => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Option label',
            'required'   => true,
            'db_comment' => 'Option label'
        ],
        'ordering'      => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Ordering',
            'required'   => false,
            'db_comment' => 'Ordering option in option list for dynamic field type DROPDOWN'
        ],
        'created_at'   => [
            'type'       => IQueryBuilder::PARAM_DATE_IMMUTABLE,
            'title'      => 'Created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC'
        ],
        'updated_at'   => [
            'type'       => IQueryBuilder::PARAM_DATE_IMMUTABLE,
            'title'      => 'Updated at',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC'
        ],
    ];

    /**
     * Get options for dynamic field with ordering
     *
     * @param string $dynFieldId
     * @return array
     */
    public function getOptionsForField(string $dynFieldId): array
    {
        return $this->getListByFilter(
            ['dyn_field_id' => $dynFieldId],
            ['*'],
            ['ordering', 'ASC'],
            ['option_label', 'ASC']
        );
    }

    /**
     * Get next ordering number for field
     *
     * @param string $dynFieldId
     * @return int
     */
    public function getNextOrdering(string $dynFieldId): int
    {
        $options = $this->getListByFilter(
            ['dyn_field_id' => $dynFieldId],
            ['ordering'],
            ['ordering', 'DESC']
        );

        if (empty($options)) {
            return 1;
        }

        return (int)$options[0]['ordering'] + 1;
    }

    /**
     * Get option by field and value
     *
     * @param string $dynFieldId
     * @param string $optionLabel
     * @return array
     */
    public function getOptionByFieldAndLabel(string $dynFieldId, string $optionLabel): array
    {
        return $this->getItemByFilter([
            'dyn_field_id' => $dynFieldId,
            'option_label' => $optionLabel
        ]);
    }
}