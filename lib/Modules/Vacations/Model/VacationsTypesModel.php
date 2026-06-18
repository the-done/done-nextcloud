<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Vacations\Model;

use OCA\Done\Models\BaseModel;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class VacationsTypesModel.
 *
 * Lookup table for vacation types (paid leave, sick leave, day-off, etc.).
 */
class VacationsTypesModel extends BaseModel
{
    public string $table = 'done_vacations_types';
    public string $modelTitle = 'Types of employee vacations';
    public string $modelName = 'vacationsTypesModel';
    public string $dbTableComment = 'Types of employee vacations. Uses soft-delete.';

    protected array $hashFields = [
        'name',
        'color',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Unique identifier for a vacation type',
        ],
        'name' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Vacation type name',
            'required'         => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Vacation type name (e.g., Paid Leave, Sick Leave, Day-off)',
            'show'       => true,
        ],
        'color' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Color',
            'required'   => false,
            'db_comment' => 'Color for UI display in HEX format (e.g., #FF5733)',
            'show'       => true,
        ],
        'sort' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Sort order',
            'required'   => false,
            'db_comment' => 'Sort order for display',
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
        'deleted' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Deleted',
            'required'   => false,
            'db_comment' => 'Soft-delete flag (1 - deleted, 0 - active)',
        ],
    ];

    /**
     * Get all active vacation types sorted by sort order
     *
     * @return array
     */
    public function getActiveTypes(): array
    {
        $data = $this->getListByFilter([], ['*'], ['sort', 'ASC']);

        foreach ($data as $idx => $item) {
            $data[$idx]['name'] = $this->translateService->getTranslate($item['name']);
        }

        return $data;
    }

    /**
     * @override
     */
    public function getIndexedListByFilter(
        string $indexField = 'id',
        array $filter = [],
        array $fields = ['*'],
        array $orderBy = [],
        array $additionalOrderBy = [],
        bool $needDeleted = false,
    ): array {
        $data = parent::getIndexedListByFilter($indexField, $filter, $fields, $orderBy, $additionalOrderBy, $needDeleted);

        foreach ($data as $idx => $item) {
            $data[$idx]['original_name'] = $item['name'];
            $data[$idx]['name'] = $this->translateService->getTranslate($item['name']);
        }

        return $data;
    }

    /**
     * @override
     */
    public function getItem(
        string $id,
        array $fields = ['*'],
    ): array {
        $item = parent::getItem($id, $fields);

        $item['original_name'] = $item['name'];
        $item['name'] = $this->translateService->getTranslate($item['name']);

        return $item;
    }
}
