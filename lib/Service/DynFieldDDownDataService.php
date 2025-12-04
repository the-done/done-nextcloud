<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Service;

use OCA\Done\Models\DynamicFieldDropdownData_Model;
use OCA\Done\Models\DynamicFieldDropdownOptions_Model;
use OCA\Done\Models\DynamicFields_Model;
use OCA\Done\Models\DynamicFieldsTypes_Model;
use OCP\IDBConnection;
use OCP\Server;

class DynFieldDDownDataService
{
    private static DynFieldDDownDataService $instance;

    protected UserService $userService;
    protected TranslateService $translateService;
    protected IDBConnection $db;

    public function __construct(
        UserService $userService,
        TranslateService $translateService,
        IDBConnection $db
    ) {
        $this->userService = $userService;
        $this->translateService = $translateService;
        $this->db = $db;
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = Server::get(self::class);
        }

        return self::$instance;
    }

    /**
     * Save dropdown data (main method for saving)
     *
     * @param string            $dynFieldId
     * @param string            $recordId
     * @param null|array|string $value
     *
     * @return array
     */
    public function saveDropdownData(
        string $dynFieldId,
        string $recordId,
        array | string | null $value,
    ): array {
        $dynamicFieldDropdownDataModel = new DynamicFieldDropdownData_Model();
        $savedIds = [];

        try {
            if ($value === null) {
                $this->deleteDropdownData($dynFieldId, $recordId);

                return [
                    'success' => true,
                    'message' => $this->translateService->getTranslate('Dropdown data cleared successfully'),
                    'data'    => [],
                ];
            }

            [$validatedData, $errors] = $this->validateDropdownData($dynFieldId, $recordId, $value);

            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => implode(', ', $errors),
                    'data'    => [],
                ];
            }

            $this->deleteDropdownData($dynFieldId, $recordId);

            if ($validatedData['value']['multiple']) {
                // For multiple selection
                foreach ($validatedData['value']['data'] as $item) {
                    $optionSlug = $item['slug'] ?? null;

                    if (!empty($optionSlug) && !$this->validateOptionExists($dynFieldId, $optionSlug)) {
                        return [
                            'success' => false,
                            'message' => $this->translateService->getTranslate('Option not found: ') . $optionSlug,
                            'data'    => [],
                        ];
                    }
                }

                foreach ($validatedData['value']['data'] as $item) {
                    $optionSlug = $item['slug'] ?? null;

                    $data = [
                        'dyn_field_id' => $dynFieldId,
                        'record_id'    => $recordId,
                        'option_id'    => $optionSlug,
                    ];

                    if (!empty($dynamicFieldDropdownDataModel->getItemByFilter($data))) {
                        continue;
                    }

                    $savedIds[] = $dynamicFieldDropdownDataModel->addData($data);
                }
            } else {
                // For single selection
                $optionSlug = $validatedData['value']['data']['slug'] ?? null;

                if (!empty($optionSlug) && !$this->validateOptionExists($dynFieldId, $optionSlug)) {
                    return [
                        'success' => false,
                        'message' => $this->translateService->getTranslate('Option not found: ') . $optionSlug,
                        'data'    => [],
                    ];
                }

                $data = [
                    'dyn_field_id' => $dynFieldId,
                    'record_id'    => $recordId,
                    'option_id'    => $optionSlug,
                ];

                $savedIds[] = $dynamicFieldDropdownDataModel->addData($data);
            }

            return [
                'success' => true,
                'message' => $this->translateService->getTranslate('Dropdown data saved successfully'),
                'data'    => $savedIds,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $this->translateService->getTranslate('Error saving dropdown data') . ': ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Get dropdown data for record
     *
     * @param string      $recordId
     * @param null|string $dynFieldId
     *
     * @return array
     */
    public function getDropdownDataForRecord(string $recordId, ?string $dynFieldId = null): array
    {
        try {
            $dropdownDataModel = new DynamicFieldDropdownData_Model();
            $optionsModel = new DynamicFieldDropdownOptions_Model();

            $filter = ['record_id' => $recordId];

            if ($dynFieldId) {
                $filter['dyn_field_id'] = $dynFieldId;
            }

            $dropdownData = $dropdownDataModel->getListByFilter($filter);
            $dataHashedByDynField = BaseService::makeHash($dropdownData, 'dyn_field_id', true);

            if (empty($dropdownData)) {
                return [];
            }

            $optionIds = array_column($dropdownData, 'option_id');
            $options = $optionsModel->getList($optionIds, ['id', 'option_label'], true);

            $result = [];

            foreach ($dataHashedByDynField as $fieldId => $items) {
                $fieldData = [
                    'dyn_field_id'   => $fieldId,
                    'dyn_field_type' => DynamicFieldsTypes_Model::DROPDOWN,
                    'record_id'      => $recordId,
                    'value'          => [],
                ];

                foreach ($items as $item) {
                    $optionData = $options[$item['option_id']] ?? null;
                    $fieldData['value'][] = [
                        'option_slug'  => $item['option_id'],
                        'option_label' => $optionData['option_label'],
                    ];
                }

                $result[] = $fieldData;
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Delete dropdown data
     *
     * @param string $dynFieldId
     * @param string $recordId
     *
     * @return array
     */
    public function deleteDropdownData(string $dynFieldId, string $recordId): array
    {
        try {
            $model = new DynamicFieldDropdownData_Model();

            $filter = [
                'dyn_field_id' => $dynFieldId,
                'record_id'    => $recordId,
            ];

            $model->deleteByFilter($filter);

            return [
                'success' => true,
                'message' => $this->translateService->getTranslate('Dropdown data deleted successfully'),
                'data'    => [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $this->translateService->getTranslate('Error deleting dropdown data') . ': ' . $e->getMessage(
                ),
                'data' => [],
            ];
        }
    }

    /**
     * Validate dropdown data
     *
     * @param string            $dynFieldId
     * @param string            $recordId
     * @param null|array|string $value
     *
     * @return array
     */
    private function validateDropdownData(string $dynFieldId, string $recordId, array | string | null $value): array
    {
        $errors = [];

        if (empty($dynFieldId)) {
            $errors[] = $this->translateService->getTranslate('Dynamic field ID is required');
        }

        if (empty($recordId)) {
            $errors[] = $this->translateService->getTranslate('Record ID is required');
        }

        if (!$this->validateFieldExists($dynFieldId)) {
            $errors[] = $this->translateService->getTranslate('Dynamic field not found');
        }

        if (empty($value)) {
            $validatedData = [
                'dyn_field_id' => $dynFieldId,
                'record_id'    => $recordId,
                'value'        => [],
            ];
        } else {
            if (!\is_array($value)) {
                $errors[] = $this->translateService->getTranslate('Option IDs must be string or array');
            }

            $validatedData = [
                'dyn_field_id' => $dynFieldId,
                'record_id'    => $recordId,
                'value'        => $value,
            ];
        }

        return [$validatedData, $errors];
    }

    /**
     * Validate field exists
     *
     * @param string $dynFieldId
     *
     * @return bool
     */
    private function validateFieldExists(string $dynFieldId): bool
    {
        $dynamicFieldsModel = new DynamicFields_Model();
        $field = $dynamicFieldsModel->getItem($dynFieldId);

        return !empty($field);
    }

    /**
     * Validate option exists
     *
     * @param null|string $dynFieldId
     * @param null|string $optionSlug
     *
     * @return bool
     */
    private function validateOptionExists(?string $dynFieldId = '', ?string $optionSlug = ''): bool
    {
        $optionsModel = new DynamicFieldDropdownOptions_Model();
        $option = $optionsModel->getItemByFilter(['id' => $optionSlug, 'dyn_field_id' => $dynFieldId]);

        return !empty($option);
    }
}
