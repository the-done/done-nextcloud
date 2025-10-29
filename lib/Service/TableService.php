<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


declare(strict_types=1);

namespace OCA\Done\Service;

use OCA\Done\Models\Base_Model;
use OCA\Done\Models\DynamicFields_Model;
use OCA\Done\Models\RolesPermissions_Model;
use OCA\Done\Models\Table\TableColumnViewSettings_Model;
use OCA\Done\Models\Table\TableFilter_Model;
use OCA\Done\Models\Table\TableSortColumns_Model;
use OCA\Done\Models\Table\TableSortWithinColumns_Model;
use OCA\Done\Models\Table\TempTable_Model;
use OCP\Server;

class TableService
{
    private const ITEM_INDEX_PRIMARY = 0;
    private const ITEM_INDEX_SECONDARY = 1;

    private const FIELD_PROPERTIES_TO_REMOVE = ['link', 'permission', 'required', 'type'];

    protected UserService $userService;
    protected TranslateService $translateService;
    private static TableService $instance;

    public function __construct(
        UserService $userService,
        TranslateService $translateService,
    ) {
        $this->userService      = $userService;
        $this->translateService = $translateService;
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = Server::get(TableService::class);
        }

        return self::$instance;
    }

    /**
     * Get table
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getTable(Base_Model $commonModel, int $source, string $userId): array
    {
        $dynamicFieldsModel    = new DynamicFields_Model();
        $rolesPermissionsModel = new RolesPermissions_Model();

        $commonFields  = $commonModel->fields;
        $dynamicFields = BaseService::makeHash($dynamicFieldsModel->getDynamicFieldsForSource($source), 'id');
        $globalRoles   = $this->userService->getUserGlobalRoles($userId);

        $filter = [
            'entity_id'      => $source,
            'global_role_id' => ['IN', $globalRoles],
        ];

        $permissions = $rolesPermissionsModel->getFieldsReadPermissions($source, $filter);

        [$tableColumnView, $tableSortColumns, $tableSortWithinColumns, $tableFilter] = $this->getTableSettings(
            $userId,
            $source
        );

        [$tableRules, $hideFields] = self::getTableRules(
            $tableColumnView,
            $tableSortColumns,
            $tableSortWithinColumns,
            $tableFilter
        );

        [
            $commonFields,
            $dynamicFields,
            $commonColumnsFilter,
            $dynamicColumnsFilter,
            $fieldsWithOrdering,
            $sortWithinColumns,
        ] = self::prepareFields(
            $commonFields,
            $dynamicFields,
            $tableRules
        );

        $canReadFields = $this->getCanReadFields($permissions);

        [$selectCommonColumns, $selectDynColumns] = $this->getSelectableColumns(
            $commonFields,
            $dynamicFields,
            $canReadFields
        );

        $commonFields  = array_intersect_key($commonFields, array_flip($selectCommonColumns));
        $dynamicFields = array_intersect_key($dynamicFields, array_flip($selectDynColumns));

        $allColumns               = [...$commonFields, ...$dynamicFields];
        $filterInner              = [...$commonColumnsFilter, ...$dynamicColumnsFilter];
        $allFieldsWithoutOrdering = array_fill_keys(array_keys($allColumns), 0);

        $allColumnsOrdering = $this->sortFields(
            $allFieldsWithoutOrdering,
            $fieldsWithOrdering,
            $hideFields,
            $allColumns,
            $tableColumnView
        );

        return [
            'selectCommonColumns' => $selectCommonColumns,
            'selectDynColumns'    => $selectDynColumns,
            'filterInner'         => $filterInner,
            'allColumnsOrdering'  => $allColumnsOrdering,
            'sortWithinColumns'   => $sortWithinColumns,
            'settings'            => [
                'tableColumnView'        => $tableColumnView,
                'tableSortColumns'       => $tableSortColumns,
                'tableSortWithinColumns' => $tableSortWithinColumns,
                'tableFilter'            => $tableFilter,
            ],
        ];
    }

    /**
     * Get fields that user cannot read
     */
    private function getCanReadFields(array $permissions): array
    {
        return array_keys(array_filter($permissions, fn($value) => (bool)$value));
    }

    /**
     * Get selectable columns for common and dynamic fields
     */
    private function getSelectableColumns(
        array $commonFields,
        array $dynamicFields,
        array $canReadFields
    ): array {
        $selectCommonColumns = array_intersect(
            array_keys($commonFields),
            $canReadFields
        );

        $selectDynColumns = array_intersect(
            array_keys($dynamicFields),
            $canReadFields
        );

        return [$selectCommonColumns, $selectDynColumns];
    }

    public function getTableSettings(string $userId, int $source): array
    {
        $tableColumnViewSettingsModel = new TableColumnViewSettings_Model();
        $tableSortColumnsModel        = new TableSortColumns_Model();
        $tableSortWithinColumnsModel  = new TableSortWithinColumns_Model();
        $tableFilterModel             = new TableFilter_Model();

        $tableColumnView        = $tableColumnViewSettingsModel->getData($userId, $source);
        $tableSortColumns       = $tableSortColumnsModel->getData($userId, $source);
        $tableSortWithinColumns = $tableSortWithinColumnsModel->getData($userId, $source);
        $tableFilter            = $tableFilterModel->getData($userId, $source);

        return [$tableColumnView, $tableSortColumns, $tableSortWithinColumns, $tableFilter];
    }

    public static function getTableRules(
        array $tableColumnView = [],
        array $tableSortColumns = [],
        array $tableSortWithinColumns = [],
        array $tableFilter = []
    ): array {
        $tableRules = [];

        [$tableRules, $hideFields] = self::prepareTableColumnView($tableColumnView, $tableRules);
        $tableRules = self::prepareTableSortColumns($tableSortColumns, $tableRules);
        $tableRules = self::prepareTableSortWColumns($tableSortWithinColumns, $tableRules);
        $tableRules = self::prepareTableFilter($tableFilter, $tableRules);

        return [$tableRules, $hideFields];
    }

    public static function prepareFields(array $commonFields, array $dynamicFields, array $tableRules): array
    {
        $commonColumnsFilter = $dynamicColumnsFilter = $allColumnsOrdering =
        $commonColumnsSortWithinColumns = $dynamicColumnsSortWithinColumns = [];

        $commonFields = self::processFieldSet(
            $commonFields,
            $tableRules,
            $commonColumnsFilter,
            $commonColumnsSortWithinColumns,
            $allColumnsOrdering,
            true
        );

        $dynamicFields = self::processFieldSet(
            $dynamicFields,
            $tableRules,
            $dynamicColumnsFilter,
            $dynamicColumnsSortWithinColumns,
            $allColumnsOrdering,
            false
        );

        $sortWithinColumns = $commonColumnsSortWithinColumns + $dynamicColumnsSortWithinColumns;
        ksort($sortWithinColumns);

        return [
            $commonFields,
            $dynamicFields,
            $commonColumnsFilter,
            $dynamicColumnsFilter,
            $allColumnsOrdering,
            $sortWithinColumns,
        ];
    }

    /**
     * Process field set (common or dynamic)
     */
    private static function processFieldSet(
        array $fields,
        array $tableRules,
        array &$columnsFilter,
        array &$columnsSortWithinColumns,
        array &$allColumnsOrdering,
        bool $isCommonFields
    ): array {
        foreach ($fields as $fieldName => $fieldData) {
            $fieldRules = $tableRules[$fieldName] ?? [];

            // Process filters
            if (isset($fieldRules['filter_settings']['filterInner'])) {
                $columnsFilter = [
                    ...$columnsFilter,
                    ...$fieldRules['filter_settings']['filterInner'],
                ];
            }

            // Process sorting within columns
            if (isset($fieldRules['sort_settings']['sort'])) {
                $sortOrdering                            = $fieldRules['sort_settings']['sort_ordering'] ?? 0;
                $columnsSortWithinColumns[$sortOrdering] = [
                    $fieldName,
                    $fieldRules['sort_settings']['sort'],
                ];
            }

            // Process column order
            if (isset($fieldRules['ordering_settings']['ordering'])) {
                $allColumnsOrdering[$fieldName] = $fieldRules['ordering_settings']['ordering'];
            }

            $fields[$fieldName]['rules'] = self::prepareRules($fieldRules);

            // Remove unnecessary properties only for common fields
            if ($isCommonFields) {
                foreach (self::FIELD_PROPERTIES_TO_REMOVE as $property) {
                    unset($fields[$fieldName][$property]);
                }
            }
        }

        return $fields;
    }

    public static function prepareRules(array $rules): array
    {
        $result = [];

        if (isset($rules['filter_settings'])) {
            $result['filter_settings']         = $rules['filter_settings']['filterPublic'];
            $result['filter_settings']['slug'] = $rules['filter_settings']['slug'] ?? '';
        }

        if (isset($rules['ordering_settings'])) {
            $result['ordering_settings'] = $rules['ordering_settings'];
        }

        if (isset($rules['sort_settings'])) {
            $result['sort_settings'] = $rules['sort_settings'];
        }

        if (isset($rules['view_settings'])) {
            $result['view_settings'] = $rules['view_settings'];
        }

        return $result;
    }

    public function sortFields(
        array $allFieldsWithoutOrdering = [],
        array $fieldsWithOrdering = [],
        array $hideFields = [],
        array $allColumns = [],
        array $tableColumnView = [],
    ): array {
        $result = [];

        uasort($fieldsWithOrdering, function ($a, $b) use ($fieldsWithOrdering) {
            if ($a != $b) {
                return $a - $b;
            }

            $keyA = array_search($a, $fieldsWithOrdering);
            $keyB = array_search($b, $fieldsWithOrdering);

            return strcmp($keyA, $keyB);
        });

        $fieldsWithOrdering = array_values(array_keys($fieldsWithOrdering));
        $maxOrdering        = array_key_last($fieldsWithOrdering) ?? 0;

        foreach ($allFieldsWithoutOrdering as $fieldName => $ordering) {
            if (in_array($fieldName, $fieldsWithOrdering)) {
                continue;
            }

            $maxOrdering++;
            $fieldsWithOrdering[$maxOrdering] = $fieldName;
        }

        foreach ($fieldsWithOrdering as $idx => $fieldName) {
            if (!isset($allColumns[$fieldName])) {
                unset($fieldsWithOrdering[$idx]);
            }

            $result[$idx] = [
                'key'    => $fieldName,
                'hidden' => in_array($fieldName, $hideFields),
                'title'  => $this->translateService->getTranslate($allColumns[$fieldName]['title']) ?? '',
                'rules'  => $allColumns[$fieldName]['rules'] ?? null,
                'info'   => $tableColumnView[$fieldName] ?? null,
            ];
        }

        return array_values($result);
    }

    public static function prepareTableColumnView(
        array $tableColumnView = [],
        array $tableRules = []
    ): array {
        $hideFields = [];

        foreach ($tableColumnView as $column => $items) {
            $item = self::getFirstAvailableItem($items);
            if ($item === null) {
                continue;
            }

            $tableRules[$column] = [
                'view_settings' => [
                    'width' => $item['width'],
                    'hide'  => (bool)$item['hide'],
                    'slug'  => $item['id'],
                ],
            ];

            if ($item['hide']) {
                $hideFields[] = $column;
            }
        }

        return [$tableRules, $hideFields];
    }

    public static function prepareTableSortColumns(
        array $tableSortColumns = [],
        array $tableRules = []
    ): array {
        foreach ($tableSortColumns as $column => $items) {
            $item = self::getFirstAvailableItem($items);
            if ($item === null) {
                continue;
            }

            $columnItem          = $tableRules[$column] ?? [];
            $tableRules[$column] = [
                ...$columnItem,
                'ordering_settings' => [
                    'slug'     => $item['id'],
                    'ordering' => $item['ordering'],
                ],
            ];
        }

        return $tableRules;
    }

    public static function prepareTableSortWColumns(
        array $tableSortWithinColumns = [],
        array $tableRules = []
    ): array {
        foreach ($tableSortWithinColumns as $column => $items) {
            $item = self::getFirstAvailableItem($items);
            if ($item === null) {
                continue;
            }

            $columnItem          = $tableRules[$column] ?? [];
            $tableRules[$column] = [
                ...$columnItem,
                'sort_settings' => [
                    'slug'          => $item['id'],
                    'sort'          => $item['sort'] ? 'ASC' : 'DESC',
                    'sort_ordering' => (int)$item['sort_ordering'],
                ],
            ];
        }

        return $tableRules;
    }

    public static function prepareTableFilter(
        array $tableFilter = [],
        array $tableRules = []
    ): array {
        foreach ($tableFilter as $column => $items) {
            $item = self::getFirstAvailableItem($items);
            if ($item === null) {
                continue;
            }

            $columnItem = $tableRules[$column] ?? [];
            [$filterInner, $filterPublic] = TableFilter_Model::makeColumnFilter(
                $column,
                $item['condition'],
                $item['value']
            );

            $tableRules[$column] = [
                ...$columnItem,
                'filter_settings' => [
                    'slug'         => $item['id'],
                    'filterInner'  => $filterInner,
                    'filterPublic' => $filterPublic,
                ],
            ];
        }

        return $tableRules;
    }

    /**
     * Get first available item from items array
     */
    private static function getFirstAvailableItem(array $items): ?array
    {
        if (isset($items[self::ITEM_INDEX_PRIMARY])) {
            return $items[self::ITEM_INDEX_PRIMARY];
        }

        if (isset($items[self::ITEM_INDEX_SECONDARY])) {
            return $items[self::ITEM_INDEX_SECONDARY];
        }

        return null;
    }

    /**
     * Get table data for entity
     */
    public function getTableDataForEntity(Base_Model $model, int $entityType, string $userId): array
    {
        $tempTableModel = new TempTable_Model();

        $tableData = $this->getTable($model, $entityType, $userId);

        $data = $tempTableModel->getDataForTable(
            $model,
            $tableData['selectCommonColumns'],
            $tableData['selectDynColumns'],
            $tableData['filterInner'],
            $tableData['sortWithinColumns'],
        );

        return [
            'allColumnsOrdering' => $tableData['allColumnsOrdering'],
            'data'               => $data,
            'settings'           => $tableData['settings'],
        ];
    }
}