<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


namespace OCA\Done\Models;

use Doctrine\DBAL\Types\Types;
use OCA\Done\Service\BaseService;
use OCA\Done\Service\DynFieldDDownDataService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class DynamicFieldsData_Model.
 */
class DynamicFieldsData_Model extends DynamicFieldsDataAbstract_Model
{
    public string $table = 'done_dyn_fields_data';
    public string $modelTitle = 'Dynamic fields data';
    public string $modelName = 'dynamicFieldsDataModel';
    public string $dbTableComment = 'Dynamic fields data (EAV): stores values of various types, linked to records in other tables.';

    protected array $hashFields = [
        'dyn_field_id',
        'record_id',
        'int_val',
        'float_val',
        'string_val',
        'text_val',
        'date_val',
        'datetime_val',
    ];

    public array $fields = [
        'id'           => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Internal unique key for a record containing a dynamic field value',
        ],
        'dyn_field_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Dynamic field id',
            'required'   => true,
            'db_comment' => 'Dynamic field ID. References oc_done_dynamic_fields.id',
        ],
        'record_id'    => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Record id',
            'required'   => true,
            'db_comment' => 'Polymorphic reference to the record ID to which the value is linked. The entity type is determined by the `source` field in the `oc_done_dynamic_fields` table',
        ],
        'int_val'      => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Integer',
            'required'   => false,
            'db_comment' => 'Integer value (used if the field type in `oc_done_dynamic_fields` is INTEGER)',
        ],
        'float_val'    => [
            'type'       => Types::DECIMAL,
            'title'      => 'Float',
            'required'   => false,
            'db_comment' => 'Float value (used if the field type in `oc_done_dynamic_fields` is FLOAT)',
        ],
        'string_val'   => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'String',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment'       => 'String value (used if the field type in `oc_done_dynamic_fields` is STRING)',
        ],
        'text_val'     => [
            'type'             => IQueryBuilder::PARAM_LOB,
            'title'            => 'Text',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment'       => 'Text value (used if the field type in `oc_done_dynamic_fields` is TEXT)',
        ],
        'date_val'     => [
            'type'       => IQueryBuilder::PARAM_DATE_IMMUTABLE,
            'title'      => 'Date',
            'required'   => false,
            'db_comment' => 'Date value (used if the field type in `oc_done_dynamic_fields` is DATE)',
        ],
        'datetime_val' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_MUTABLE,
            'title'      => 'Date and time',
            'required'   => false,
            'db_comment' => 'Datetime value (used if the field type in `oc_done_dynamic_fields` is DATETIME)',
        ],
        'created_at'   => [
            'type'       => IQueryBuilder::PARAM_DATE_IMMUTABLE,
            'title'      => 'Created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC',
        ],
        'updated_at'   => [
            'type'       => IQueryBuilder::PARAM_DATE_IMMUTABLE,
            'title'      => 'Updated at',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC',
        ],
    ];

    public function saveDynamicFieldItem(
        int $dynFieldType,
        string $dynFieldId,
        string $recordId,
        int|float|string|array|null $value,
        bool $isSave,
        string $slug = null,
    ): array|string {
        if (empty($slug)) {
            $dynFieldData = $this->getItemByFilter(
                ['dyn_field_id' => $dynFieldId, 'record_id' => $recordId],
            );

            if (!empty($dynFieldData)) {
                $slug   = $dynFieldData['id'];
                $isSave = false;
            }
        }

        return $this->saveDynamicFieldsData(
            $dynFieldId,
            $recordId,
            $dynFieldType,
            $value,
            $isSave,
            $slug
        );
    }

    /**
     * Save dynamic field value
     *
     * @param string $dynFieldId
     * @param string $recordId
     * @param int $dynFieldType
     * @param int|float|string|array|null $value
     * @param bool $isSave
     * @param ?string $slug
     *
     * @return array|string
     * @throws \Exception
     */
    public function saveDynamicFieldsData(
        string $dynFieldId,
        string $recordId,
        int $dynFieldType,
        int|float|string|array|null $value,
        bool $isSave,
        string $slug = null,
    ): array|string {
        $data = [
            'dyn_field_id' => $dynFieldId,
            'record_id'    => $recordId,
        ];

        switch ($dynFieldType) {
            case DynamicFieldsTypes_Model::INTEGER:
                $data['int_val'] = (int)$value ?: null;
                break;
            case DynamicFieldsTypes_Model::FLOAT:
                $data['float_val'] = (float)$value ?: null;
                break;
            case DynamicFieldsTypes_Model::STRING:
                $data['string_val'] = (string)$value ?: null;
                break;
            case DynamicFieldsTypes_Model::TEXT:
                $data['text_val'] = (string)$value ?: null;
                break;
            case DynamicFieldsTypes_Model::DATE:
                $data['date_val'] = !empty($value) ? (new \DateTimeImmutable($value)) : null;
                break;
            case DynamicFieldsTypes_Model::DATETIME:
                $data['datetime_val'] = !empty($value) ? (new \DateTimeImmutable($value)) : null;
                break;
            case DynamicFieldsTypes_Model::DROPDOWN:
                $dropdownService = DynFieldDDownDataService::getInstance();
                $result          = $dropdownService->saveDropdownData($dynFieldId, $recordId, $value);

                if ($result['success']) {
                    return $result['data'] ?? [];
                } else {
                    return [];
                }
        }

        if (!$isSave) {
            $this->update($data, $slug);
        } else {
            $slug = $this->addData($data);
        }

        return $slug;
    }

    /**
     * Get dynamic field values for record
     *
     * @param string $recordId
     *
     * @return array<string, mixed>
     */
    public function getDataForRecord(string $recordId): array
    {
        $regularData = $this->getRegularDataForRecord($recordId);

        $dropdownService = DynFieldDDownDataService::getInstance();
        $dropdownData    = $dropdownService->getDropdownDataForRecord($recordId);

        return array_merge($regularData, $dropdownData);
    }

    /**
     * Get regular dynamic field values for record (excluding DROPDOWN)
     *
     * @param string $recordId
     *
     * @return array<string, mixed>
     */
    private function getRegularDataForRecord(string $recordId): array
    {
        $list = $this->getListByFilter(['record_id' => $recordId]);

        if (empty($list)) {
            return [];
        }

        return $this->getItems($list);
    }
}