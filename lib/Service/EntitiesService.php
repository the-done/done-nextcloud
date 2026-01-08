<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Service;

use OCA\Done\Models\BaseModel;
use OCA\Done\Models\CustomSettingsDataModel;
use OCA\Done\Models\CustomSettingsModel;
use OCA\Done\Models\DynamicFieldDropdownDataModel;
use OCA\Done\Models\DynamicFieldsDataModel;
use OCA\Done\Models\DynamicFieldsModel;
use OCA\Done\Models\PermissionsEntitiesModel;
use OCP\Server;

class EntitiesService
{
    private TranslateService $translateService;

    public function __construct()
    {
        $this->translateService = TranslateService::getInstance();
    }

    /** @var EntitiesService */
    private static EntitiesService $instance;

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = Server::get(self::class);
        }

        return self::$instance;
    }

    /**
     * Get entity data
     *
     * @param int    $source
     * @param string $slug
     *
     * @return array
     */
    public function getDataToViewEntity(int $source, string $slug): array
    {
        $userService = UserService::getInstance();
        $customSettingsData = new CustomSettingsDataModel();
        $dynFieldsModel = new DynamicFieldsModel();
        $dynFieldsDataModel = new DynamicFieldsDataModel();
        $dynamicFieldDropdownDataModel = new DynamicFieldDropdownDataModel();
        $fieldsOrderingService = FieldsOrderingService::getInstance();
        $sourceData = PermissionsEntitiesModel::getPermissionsEntities($source);
        $model = new $sourceData[$source]['model']();
        $model->needPrepareDates = false;
        $modelFields = $model->fields;

        $hideEmptyFields = (bool)$customSettingsData->getItemByFilter(
            [
                'user_id'    => $userService->getCurrentUserId(),
                'setting_id' => CustomSettingsModel::HIDE_EMPTY_FIELDS_IN_PREVIEW,
            ]
        )['value'];

        $itemId = $model->getItemIdBySlug($slug, true);
        $item = $model->getLinkedItem($itemId, ['*'], false, true);

        $entityDynFields = $dynFieldsModel->getIndexedListByFilter(filter: ['source' => $source]);
        $dynFieldsData = $dynFieldsDataModel->getIndexedDataByFilter(filter: ['record_id' => $itemId]);
        $dynDropdownData = $dynamicFieldDropdownDataModel->getIndexedDataByFilter(filter: ['record_id' => $itemId]);

        foreach ($dynFieldsData as $key => $dynFieldData) {
            $item[$key] = $dynFieldData['value'] ?? '';
            $modelFields[$key] = $dynFieldData;
        }

        foreach ($dynDropdownData as $key => $dynFieldData) {
            $item[$key] = $dynFieldData['value'] ?? '';
            $modelFields[$key] = $dynFieldData;
        }

        $item = $model->prepareDataBeforeSend($item, $source);

        // Check for user field ordering at the beginning
        $fieldsOrdering = $fieldsOrderingService->getFieldsOrdering($source);
        $hasCustomOrdering = !empty($fieldsOrdering);

        $entityData = $this->addModelData(
            $modelFields,
            $item,
            $fieldsOrdering,
            $entityDynFields,
            $dynFieldsData,
            $hasCustomOrdering,
            $hideEmptyFields
        );

        $entityData = $this->addExternalData($model, $entityData, $itemId);

        return $this->addAppearanceData($model, $entityData, $sourceData, $source, $itemId);
    }

    /**
     * Sort model data based on user-defined field ordering
     *
     * Sorts the model data array using user-defined field ordering.
     * Fields with custom ordering are prioritized, others maintain original order.
     *
     * @param array $modelData   Array of model data items
     * @param array $orderingMap Map of field names to their ordering values
     *
     * @return array Sorted model data with original_index removed
     */
    public function sortModelData(array $modelData, array $orderingMap = []): array
    {
        usort($modelData, static function ($a, $b) use ($orderingMap) {
            $aHasOrdering = isset($orderingMap[$a['field']]);
            $bHasOrdering = isset($orderingMap[$b['field']]);

            // If both fields have ordering
            if ($aHasOrdering && $bHasOrdering) {
                $aOrdering = $orderingMap[$a['field']];
                $bOrdering = $orderingMap[$b['field']];

                if ($aOrdering !== $bOrdering) {
                    return $aOrdering <=> $bOrdering;
                }

                // Secondary sort by original index
                return $a['original_index'] <=> $b['original_index'];
            }

            // If only one field has ordering
            if ($aHasOrdering) {
                return -1; // Field with ordering goes first
            }

            if ($bHasOrdering) {
                return 1; // Field with ordering goes first
            }

            // If both fields don't have ordering - keep original order
            return $a['original_index'] <=> $b['original_index'];
        });

        // Remove service field original_index
        foreach ($modelData as &$item) {
            unset($item['original_index']);
        }

        return $modelData;
    }

    /**
     * Add external data to entity data array
     *
     * Processes linked models and adds their data to the entity data.
     * Used for displaying related entities (e.g., user roles, project teams).
     *
     * @param BaseModel  $model      The base model instance
     * @param array      $entityData Current entity data array
     * @param int|string $itemId     Item identifier
     *
     * @return array Entity data with external_data section
     */
    public function addExternalData(
        BaseModel $model,
        array $entityData,
        int | string $itemId
    ): array {
        foreach ($model->linkedModels as $linkedModel => $params) {
            $filterField = $params['filter_field'];
            $keyField = $params['key_field'];
            $sourceTitle = $params['title'];
            $frontendType = $params['frontend_type'];

            $filter = [
                $filterField => $itemId,
            ];

            $linkedItems = (new $linkedModel())->getLinkedList($filter, ['*'], false, true);

            $entityData['external_data'][] = [
                'title'         => $this->translateService->getTranslate($sourceTitle),
                'frontend_type' => $frontendType,
                'value'         => BaseService::getField($linkedItems, $keyField),
            ];
        }

        return $entityData;
    }

    /**
     * Add appearance data to entity data array
     *
     * Retrieves appearance-related fields (colors, images, etc.) from the
     * appearance model and adds them to the entity data.
     *
     * @param BaseModel  $model      The base model instance
     * @param array      $entityData Current entity data array
     * @param array      $sourceData Source entity configuration
     * @param int        $source     Source entity ID
     * @param int|string $itemId     Item identifier
     *
     * @return array Entity data with appearance_data section
     */
    public function addAppearanceData(
        BaseModel $model,
        array $entityData,
        array $sourceData,
        int $source,
        int | string $itemId
    ): array {
        if ($model->appearanceModel !== '') {
            $appearanceModel = new $model->appearanceModel();

            $modelFields = $appearanceModel->fields;
            $appearanceFields = $appearanceModel->appearanceFields;
            $appearanceFieldsWithFile = $appearanceModel->appearanceFieldsWithFile;

            $appearances = $appearanceModel->getItemByFilter([$sourceData[$source]['foreign_key'] => $itemId]);

            foreach ($appearances as $field => $value) {
                $title = $modelFields[$field]['title'] ?? '';

                $isFileField = \in_array($field, $appearanceFieldsWithFile);

                $fileUrl = '';

                if ($isFileField && !empty($value)) {
                    $fileUrl = '/apps/done/file/' . $itemId . '/' . $field . '/' . $value;
                }

                if (!\in_array($field, $appearanceFields) || empty($title)) {
                    continue;
                }

                $entityData['appearance_data'][] = [
                    'title'         => $this->translateService->getTranslate($title),
                    'field_name'    => $field,
                    'value'         => $value,
                    'is_file_field' => $isFileField,
                    'file_url'      => $fileUrl,
                ];
            }
        }

        return $entityData;
    }

    /**
     * Add model data to entity data array
     *
     * Processes model fields, applies user field ordering, handles dynamic fields,
     * and filters empty fields based on user preferences.
     *
     * @param array $modelFields       Model field definitions
     * @param array $item              Item data from database
     * @param array $fieldsOrdering    User-defined field ordering
     * @param array $entityDynFields   Dynamic fields for the entity
     * @param array $dynFieldsData     Dynamic field values
     * @param bool  $hasCustomOrdering Whether user has custom field ordering
     * @param bool  $hideEmptyFields   Whether to hide empty fields
     *
     * @return array Entity data with model_data section
     */
    public function addModelData(
        array $modelFields,
        array $item,
        array $fieldsOrdering,
        array $entityDynFields,
        array $dynFieldsData,
        bool $hasCustomOrdering,
        bool $hideEmptyFields,
    ): array {
        $entityData = $orderingMap = $fieldsInModelData = $availableDynEntityFields = [];

        foreach ($entityDynFields as $key => $entityDynField) {
            $availableDynEntityFields[] = $key;
        }

        // Create ordering map only if there is user ordering
        if ($hasCustomOrdering) {
            foreach ($fieldsOrdering as $orderingItem) {
                $orderingMap[$orderingItem['field']] = $orderingItem['ordering'];
            }
        }

        // Form model_data with adding a field key and original position
        $originalIndex = 0;

        foreach ($item as $field => $value) {
            $title = $modelFields[$field]['title'] ?? '';

            if (empty($title) || $field == 'id') {
                continue;
            }

            if (empty($value) && $hideEmptyFields) {
                continue;
            }

            $entityData['model_data'][] = [
                'title'          => $this->translateService->getTranslate($title),
                'value'          => $value,
                'field'          => $field, // Add the original field name
                'original_index' => $originalIndex++, // Add the original position
            ];
        }

        // If there are new dynamic fields that have not yet been initiated for the entity,
        // we pass fields with empty values to the Preview.
        // If the entity has such fields, the "Hide empty values" flag is triggered only
        // after the entity is re-saved.

        foreach ($entityData['model_data'] as $modelData) {
            $fieldsInModelData[] = $modelData['field'];
        }

        if (empty($dynFieldsData) || \count($dynFieldsData) < \count($availableDynEntityFields)) {
            foreach ($availableDynEntityFields as $key => $availableDynField) {
                if (!\in_array($availableDynField, $fieldsInModelData)) {
                    $entityData['model_data'][] = [
                        'title'          => $entityDynFields[$availableDynField]['title'],
                        'value'          => '',
                        'field'          => $availableDynField,
                        'original_index' => $key,
                    ];
                }
            }
        }

        // Sort ONLY if there is user ordering
        if ($hasCustomOrdering) {
            $entityData['model_data'] = $this->sortModelData($entityData['model_data'], $orderingMap);
        }

        return $entityData;
    }
}
