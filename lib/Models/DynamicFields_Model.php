<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Models;

use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class DynamicFields_Model.
 */
class DynamicFields_Model extends Base_Model
{
    public string $table = 'done_dynamic_fields';
    public string $modelTitle = 'Dynamic fields';
    public string $modelName = 'dynamicFieldsModel';
    public string $dbTableComment = 'Lookup table for dynamic fields: defines field settings, their data types, and which section they belong to.';

    protected array $hashFields = [
        'title',
        'field_type',
        'source',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Internal unique key for a dynamic field',
        ],
        'title' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Field name',
            'required'         => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Name of the dynamic field displayed in the UI',
        ],
        'field_type' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Field type',
            'required'   => true,
            'db_comment' => 'Field type. Possible values: 1 (INTEGER), 2 (FLOAT), 3 (STRING), 4 (TEXT), 5 (DATE), 6 (DATETIME), 7 (DROPDOWN), 8 (DROPDOWN FROM SOURCE), 9 (BOOL)',
        ],
        'source' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Source',
            'required'   => true,
            'db_comment' => 'Identifier of the application section the field belongs to. Possible values: 1 (Users), 2 (Projects), 3 (Teams), 4 (Payments)',
        ],
        'required' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Required',
            'required'   => false,
            'db_comment' => 'Required field flag (1 - required, 0 - optional)',
        ],
        'multiple' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Multiple',
            'required'   => false,
            'db_comment' => 'Multiple field flag (1 - multiple, 0 - single)',
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

    /**
     * Get list of dynamic field sources
     *
     * @return array<int,string>
     */
    public function getSourcesList(): array
    {
        return [
            PermissionsEntities_Model::USER_ENTITY    => $this->translateService->getTranslate('User card'),
            PermissionsEntities_Model::PROJECT_ENTITY => $this->translateService->getTranslate('Project card'),
        ];
    }

    /**
     * Get list of dynamic fields for source
     *
     * @param ?int $source
     *
     * @return array
     */
    public function getDynamicFieldsForSource(?int $source = null): array
    {
        $sourcesList = $this->getSourcesList();

        if (!isset($sourcesList[$source])) {
            return [];
        }

        return $this->getListByFilter(['source' => $source]);
    }

    /**
     * Add slug and slug_type to record
     *
     * @param array<string, mixed> $item
     *
     * @return array
     */
    public function addSlugToItem(array $item): array
    {
        $item = parent::addSlugToItem($item);
        $item['required'] = (bool)$item['required'];
        $item['multiple'] = (bool)$item['multiple'];

        return $item;
    }

    /**
     * Prepare filter by dynamic fields for table
     *
     * @param array $dynamicColumnsFilter
     *
     * @return array
     */
    public function prepareDynFieldsFilter(array $dynamicColumnsFilter = []): array
    {
        $result = [];

        $dynFields = $this->getIndexedListByFilter(
            'id',
            ['id' => ['IN', array_keys($dynamicColumnsFilter), IQueryBuilder::PARAM_STR_ARRAY]]
        );

        foreach ($dynamicColumnsFilter as $id => $filter) {
            $type = $dynFields[$id]['field_type'] ?? null;
            $column = DynamicFieldsData_Model::getColumnByType($type);

            if (empty($column)) {
                continue;
            }

            $result[$column] = $filter;
        }

        return $result;
    }
}
