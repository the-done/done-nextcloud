<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


namespace OCA\Done\Models;

use OCA\Done\Service\BaseService;
use OCA\Done\Service\DynFieldDDownDataService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class DynamicFieldsDataAbstract_Model.
 */
abstract class DynamicFieldsDataAbstract_Model extends Base_Model
{
    /**
     * Get dynamic field values by filter
     *
     * @param array<string, mixed> $filter
     * @param array<int, string> $selectDynFields ,
     * @param string[] $orderBy
     * @param string[] $additionalOrderBy
     *
     * @return array<string, mixed>
     */
    public function getDataByFilter(
        array $filter = [],
        array $selectDynFields = [],
        array $orderBy = [],
        array $additionalOrderBy = []
    ): array {
        if (!empty($selectDynFields)) {
            $filter = [
                ...$filter,
                'dyn_field_id' => ['IN', $selectDynFields, IQueryBuilder::PARAM_STR_ARRAY],
            ];
        }

        $list = $this->getListByFilter($filter, ['*'], $orderBy, $additionalOrderBy);

        if (empty($list)) {
            return [];
        }

        return $this->getItems($list);
    }

    /**
     * Get dynamic field values indexed by field by filter
     *
     * @param string $indexField
     * @param array<string, mixed> $filter
     * @param array<int, string> $selectDynFields ,
     * @param string[] $orderBy
     * @param string[] $additionalOrderBy
     *
     * @return array<string, mixed>
     */
    public function getIndexedDataByFilter(
        string $indexField = 'dyn_field_id',
        array $filter = [],
        array $selectDynFields = [],
        array $orderBy = [],
        array $additionalOrderBy = []
    ): array {
        $items = $this->getDataByFilter($filter, $selectDynFields, $orderBy, $additionalOrderBy);

        return BaseService::makeHash($items, $indexField);
    }

    /**
     * Get dynamic field values
     *
     * @param array<string, mixed> $list
     *
     * @return array<string, mixed>
     */
    public function getItems(array $list = []): array
    {
        $result             = [];
        $dynamicFieldsModel = new DynamicFields_Model();
        $dinFieldsIds       = BaseService::getField($list, 'dyn_field_id');
        $dinFieldsIndexed   = $dynamicFieldsModel->getIndexedListByFilter(
            'id',
            ['id' => ['IN', $dinFieldsIds, IQueryBuilder::PARAM_STR_ARRAY]]
        );
        $dynDataIndexed     = BaseService::makeHash($list, 'dyn_field_id', true);

        foreach ($dynDataIndexed as $dynFieldId => $data) {
            $dynFieldMultiple = (bool)$dinFieldsIndexed[$dynFieldId]['multiple'] ?? false;
            $dynFieldTitle    = $dinFieldsIndexed[$dynFieldId]['title'] ?? '';
            $dynFieldType     = (int)$dinFieldsIndexed[$dynFieldId]['field_type'] ?? null;

            $dynFieldData = $dynFieldMultiple ? $data : $data[0] ?? null;
            $id           = $dynFieldData['id'];
            $recordId     = $dynFieldData['record_id'];

            $result[$id] = [
                'title'          => $dynFieldTitle,
                'record_id'      => $recordId,
                'dyn_field_id'   => $dynFieldId,
                'dyn_field_type' => $dynFieldType,
                'slug'           => $id,
                'value'          => $this->getDynFieldValueByType(
                    $dynFieldData,
                    $dynFieldType,
                    $dynFieldMultiple
                ),
            ];
        }

        return $result;
    }

    /**
     * Get dynamic field value by its type
     *
     * @param array<string, mixed> $item
     * @param ?int $dynFieldType
     * @param ?bool $dynFieldMultiple
     *
     * @return int|float|string|array|null
     */
    public function getDynFieldValueByType(
        array $item,
        ?int $dynFieldType = null,
        ?bool $dynFieldMultiple = false,
    ): int|float|string|array|null {
        $value = null;

        if (!$dynFieldType) {
            return null;
        }

        switch ($dynFieldType) {
            case DynamicFieldsTypes_Model::INTEGER:
                $value = $item['int_val'];
                break;
            case DynamicFieldsTypes_Model::FLOAT:
                $value = $item['float_val'];
                break;
            case DynamicFieldsTypes_Model::STRING:
                $value = $item['string_val'];
                break;
            case DynamicFieldsTypes_Model::TEXT:
                $value = $item['text_val'];
                break;
            case DynamicFieldsTypes_Model::DATE:
                $value = $item['date_val'];
                break;
            case DynamicFieldsTypes_Model::DATETIME:
                $value = $item['datetime_val'];
                break;
            case DynamicFieldsTypes_Model::DROPDOWN:
                $values = [];

                $optionsModel = new DynamicFieldDropdownOptions_Model();
                $optionIds    = $dynFieldMultiple ? BaseService::getField(
                    $item,
                    'option_id',
                    true
                ) : [$item['option_id']];
                $optionsList  = $optionsModel->getList($optionIds, ['id', 'option_label'], true);

                if ($dynFieldMultiple) {
                    foreach ($item as $dynFieldItem) {
                        $values[] = $optionsList[$dynFieldItem['option_id']]['option_label'] ?? '';
                    }
                } else {
                    $values[] = $optionsList[$item['option_id']]['option_label'] ?? '';
                }

                return implode(', ', $values);
        }

        return $value;
    }

    public static function getColumnByType(?int $dynFieldType): ?string
    {
        return match ($dynFieldType) {
            DynamicFieldsTypes_Model::INTEGER => 'int_val',
            DynamicFieldsTypes_Model::FLOAT => 'float_val',
            DynamicFieldsTypes_Model::STRING => 'string_val',
            DynamicFieldsTypes_Model::TEXT => 'text_val',
            DynamicFieldsTypes_Model::DATE => 'date_val',
            DynamicFieldsTypes_Model::DATETIME => 'datetime_val',
            DynamicFieldsTypes_Model::DROPDOWN => null,
            default => '',
        };
    }
}