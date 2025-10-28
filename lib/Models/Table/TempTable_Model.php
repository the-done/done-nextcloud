<?php

namespace OCA\Done\Models\Table;

use OCA\Done\Models\Base_Model;
use OCA\Done\Models\DynamicFieldDropdownData_Model;
use OCA\Done\Models\DynamicFields_Model;
use OCA\Done\Models\DynamicFieldsData_Model;
use OCA\Done\Models\DynamicFieldsTypes_Model;
use OCA\Done\Service\BaseService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class TempTable_Model.
 */
class TempTable_Model extends Base_Model
{
    public string $table = 'temp_data';
    public string $modelTitle = 'Temporary table';
    public string $modelName = 'tempTableModel';

    public array $fields = [];
    public bool $needPrepareDates = false;

    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    public function getDataForTable(
        Base_Model $commonModel,
        array $selectCommonColumns = [],
        array $selectDynColumns = [],
        array $filter = [],
        array $sortWithinColumns = [],
    ): array {
        $dynamicFieldsModel            = new DynamicFields_Model();
        $dynamicFieldsDataModel        = new DynamicFieldsData_Model();
        $dynamicFieldDropdownDataModel = new DynamicFieldDropdownData_Model();
        $this->primarySlugField        = $commonModel->primarySlugField;

        [$commonFields, $tempTableModelCommonFields] = self::getCommonFieldsForTempTable(
            $commonModel,
            $selectCommonColumns
        );
        [$dynFields, $tempTableModelDynFields] = self::getDynFieldsForTempTable($selectDynColumns);
        $tempTableModelFields = [...$tempTableModelCommonFields, ...$tempTableModelDynFields];

        $commonData          = $commonModel->getListByFilter([], $selectCommonColumns);
        $dynSimpleFieldsData = $dynamicFieldsDataModel->getListByFilter(
            ['dyn_field_id' => ['IN', $selectDynColumns, IQueryBuilder::PARAM_STR_ARRAY]]
        );

        $dynDropdownData = $dynamicFieldDropdownDataModel->getListByFilter(
            ['dyn_field_id' => ['IN', $selectDynColumns, IQueryBuilder::PARAM_STR_ARRAY]]
        );

        $dynData = [...$dynSimpleFieldsData, ...$dynDropdownData];

        $dynDataIndexed   = BaseService::makeHash($dynData, 'record_id', true, 'dyn_field_id', true);
        $dinFieldsIndexed = $dynamicFieldsModel->getIndexedListByFilter(
            'id',
            ['id' => ['IN', $selectDynColumns, IQueryBuilder::PARAM_STR_ARRAY]]
        );

        $this->setFields($tempTableModelFields);

        $fieldsForInsert = !empty($dynFields) ? "{$commonFields},{$dynFields}" : "{$commonFields}";

        $tempTable = "CREATE TEMPORARY TABLE IF NOT EXISTS `*PREFIX*temp_data` ($fieldsForInsert)";

        $this->db->executeQuery($tempTable);

        foreach ($commonData as &$record) {
            $dataToSave                    = [];
            $id                            = $record['id'];
            $dynFieldsDataForRecordGrouped = $dynDataIndexed[$id] ?? [];

            foreach ($dynFieldsDataForRecordGrouped as $dynFieldId => $dynFieldsDataForRecord) {
                $dynFieldType     = (int)$dinFieldsIndexed[$dynFieldId]['field_type'] ?? null;
                $dynFieldMultiple = (bool)$dinFieldsIndexed[$dynFieldId]['multiple'] ?? false;
                $dynFieldData     = $dynFieldMultiple ? $dynFieldsDataForRecord : $dynFieldsDataForRecord[0] ?? null;

                $record[$dynFieldId] = $dynamicFieldsDataModel->getDynFieldValueByType(
                    $dynFieldData,
                    $dynFieldType,
                    $dynFieldMultiple
                );
            }

            foreach ($selectDynColumns as $selectDynColumn) {
                if (!isset($record[$selectDynColumn])) {
                    $record[$selectDynColumn] = null;
                }
            }

            foreach ($record as $field => $value) {
                if (!isset($tempTableModelFields[$field])) {
                    continue;
                }

                $dataToSave[$field] = $value;
            }

            $this->addData($dataToSave);
        }

        $itemsLinked = $this->getLinkedList($filter);

        return $this->prepareItemsFields(
            $this->sortByMultipleFields($itemsLinked, $sortWithinColumns),
            $commonModel->fieldsWithPreparedValues
        );
    }

    public static function getCommonDataTypes(): array
    {
        return [
            IQueryBuilder::PARAM_INT                => 'INT',
            IQueryBuilder::PARAM_STR                => 'VARCHAR(255)',
            IQueryBuilder::PARAM_DATE_IMMUTABLE     => 'DATE',
            IQueryBuilder::PARAM_DATETIME_IMMUTABLE => 'DATETIME',
            IQueryBuilder::PARAM_BOOL               => 'TINYINT(1)',
            IQueryBuilder::PARAM_LOB                => 'LONGTEXT',
        ];
    }

    public static function getCommonFieldsForTempTable(Base_Model $model, array $selectCommonColumns = []): array
    {
        $fields                     = $model->fields;
        $types                      = self::getCommonDataTypes();
        $tempTableColumns           = [
            'id'        => 'id VARCHAR(32) PRIMARY KEY',
            'slug'      => 'slug VARCHAR(32)',
            'slug_type' => 'slug_type INT',
        ];
        $tempTableModelCommonFields = [
            'id'        => [
                'type' => IQueryBuilder::PARAM_STR,
            ],
            'slug'      => [
                'type' => IQueryBuilder::PARAM_STR,
            ],
            'slug_type' => [
                'type' => IQueryBuilder::PARAM_INT,
            ],
        ];

        foreach ($fields as $fieldName => $params) {
            if (!in_array($fieldName, $selectCommonColumns) || in_array($fieldName, $tempTableColumns)) {
                continue;
            }

            $sqlType                                = $types[$params['type']];
            $tempTableColumns[$fieldName]           = "`{$fieldName}` {$sqlType}";
            $tempTableModelCommonFields[$fieldName] = ['type' => $params['type'], 'link' => $params['link'] ?? null];
        }

        return [implode(', ', $tempTableColumns), $tempTableModelCommonFields];
    }

    public static function getDynFieldsForTempTable(array $selectDynColumns = []): array
    {
        $dynamicFieldsModel = new DynamicFields_Model();

        $fields = $dynamicFieldsModel->getListByFilter(
            ['id' => ['IN', $selectDynColumns, IQueryBuilder::PARAM_STR_ARRAY]]
        );

        $result              = $tempTableModelDynFields = [];
        $commonDataTypes     = self::getCommonDataTypes();
        $comparedFieldsTypes = DynamicFieldsTypes_Model::getComparedFieldsTypes();

        foreach ($fields as $field) {
            $fieldType     = $field['field_type'];
            $id            = $field['id'];
            $nextcloudType = $comparedFieldsTypes[$fieldType];
            $sqlType       = $commonDataTypes[$nextcloudType];

            $result[]                     = "`{$id}` {$sqlType}";
            $tempTableModelDynFields[$id] = ['type' => $nextcloudType];
        }

        return [implode(', ', $result), $tempTableModelDynFields];
    }

    /**
     * Sorts multidimensional array by multiple fields with specified directions
     *
     * @param array $array Source array
     * @param array $sortRules Sorting rules in format:
     *     [
     *         [field1, direction],
     *         [field2, direction],
     *         ...
     *     ]
     *     direction: 'ASC' (ascending) or 'DESC' (descending)
     * @return array Sorted array
     */
    public function sortByMultipleFields(array $array, array $sortRules): array
    {
        usort($array, function ($a, $b) use ($sortRules) {
            foreach ($sortRules as $rule) {
                // Check rule correctness
                if (!is_array($rule) || count($rule) < 2) {
                    continue;
                }

                $field     = $rule[0];
                $direction = strtoupper($rule[1]);

                // Check field existence in array
                if (!array_key_exists($field, $a) || !array_key_exists($field, $b)) {
                    continue;
                }

                // Determine comparison type
                $valA = $a[$field];
                $valB = $b[$field];

                // Compare values
                if (is_numeric($valA) && is_numeric($valB)) {
                    $cmp = $valA <=> $valB;
                } else {
                    // Convert to string for comparison (in case of NULL)
                    $cmp = strcmp((string)$valA, (string)$valB);
                }

                // If values are different, return result considering sort direction
                if ($cmp !== 0) {
                    return $direction === 'DESC' ? -$cmp : $cmp;
                }
            }

            return 0;
        });

        return $array;
    }
}