<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Service;

use OCA\Done\Models\DynamicFieldDropdownOptions_Model;
use OCA\Done\Models\DynamicFields_Model;
use OCP\IDBConnection;
use OCP\Server;

class DynFieldDDownOptionsService
{
    private static DynFieldDDownOptionsService $instance;

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
     * Save dropdown option (add or update)
     *
     * @param string      $dynFieldId
     * @param string      $optionLabel
     * @param null|int    $ordering
     * @param null|string $slug
     *
     * @return array
     */
    public function saveOption(
        string $dynFieldId,
        string $optionLabel,
        ?int $ordering = null,
        ?string $slug = null
    ): array {
        try {
            [$validatedData, $errors] = $this->validateOptionData($dynFieldId, $optionLabel);

            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => implode(', ', $errors),
                    'data'    => [],
                ];
            }

            if (!$this->validateFieldExists($dynFieldId)) {
                return [
                    'success' => false,
                    'message' => $this->translateService->getTranslate('Dynamic field not found'),
                    'data'    => [],
                ];
            }

            $excludeOptionId = null;

            if (!empty($slug)) {
                $excludeOptionId = (new DynamicFieldDropdownOptions_Model())->getItemIdBySlug($slug);
            }

            if (!$this->validateOptionUniqueness($dynFieldId, $optionLabel, $excludeOptionId)) {
                return [
                    'success' => false,
                    'message' => $this->translateService->getTranslate('Option already exists for this field'),
                    'data'    => [],
                ];
            }

            $isSave = empty($slug);
            $model = new DynamicFieldDropdownOptions_Model();

            if ($isSave && $ordering === null) {
                $validatedData['ordering'] = $model->getNextOrdering($dynFieldId);
            }

            if ($isSave) {
                $optionId = $model->addData($validatedData);
                $message = $this->translateService->getTranslate('Option created successfully');
            } else {
                $optionId = $model->getItemIdBySlug($slug);
                $model->update($validatedData, $optionId);
                $message = $this->translateService->getTranslate('Option updated successfully');
            }

            $savedOption = $model->getItem($optionId);

            return [
                'success' => true,
                'message' => $message,
                'data'    => $savedOption,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $this->translateService->getTranslate('Error saving option') . ': ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Delete dropdown option
     *
     * @param string $slug
     *
     * @return array
     */
    public function deleteOption(string $slug): array
    {
        try {
            $model = new DynamicFieldDropdownOptions_Model();
            $optionId = $model->getItemIdBySlug($slug);

            if (empty($optionId)) {
                return [
                    'success' => false,
                    'message' => $this->translateService->getTranslate('Option not found'),
                    'data'    => [],
                ];
            }

            $model->delete($optionId);

            return [
                'success' => true,
                'message' => $this->translateService->getTranslate('Option deleted successfully'),
                'data'    => [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $this->translateService->getTranslate('Error deleting option') . ': ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Get options for dynamic field
     *
     * @param string $dynFieldId
     *
     * @return array
     */
    public function getOptionsForField(string $dynFieldId): array
    {
        try {
            $model = new DynamicFieldDropdownOptions_Model();
            $options = $model->getOptionsForField($dynFieldId);

            return [
                'success' => true,
                'message' => '',
                'data'    => $options,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $this->translateService->getTranslate('Error getting options') . ': ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Reorder options
     *
     * @param string $dynFieldId
     * @param array  $optionIds
     *
     * @return array
     */
    public function reorderOptions(string $dynFieldId, array $optionIds): array
    {
        try {
            $this->db->beginTransaction();

            $model = new DynamicFieldDropdownOptions_Model();
            $ordering = 1;

            foreach ($optionIds as $optionId) {
                $model->update(['ordering' => $ordering], $optionId);
                $ordering++;
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => $this->translateService->getTranslate('Options reordered successfully'),
                'data'    => [],
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();

            return [
                'success' => false,
                'message' => $this->translateService->getTranslate('Error reordering options') . ': ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Validate option data
     *
     * @param string $dynFieldId
     * @param string $optionLabel
     *
     * @return array
     */
    public function validateOptionData(string $dynFieldId, string $optionLabel): array
    {
        $errors = [];

        $optionLabel = trim($optionLabel);

        if (empty($dynFieldId)) {
            $errors[] = $this->translateService->getTranslate('Dynamic field ID is required');
        }

        if (empty($optionLabel)) {
            $errors[] = $this->translateService->getTranslate('Option label is required');
        }

        $validatedData = [
            'dyn_field_id' => $dynFieldId,
            'option_label' => $optionLabel,
        ];

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
     * Validate option uniqueness
     *
     * @param string      $dynFieldId
     * @param string      $optionLabel
     * @param null|string $excludeOptionId
     *
     * @return bool
     */
    private function validateOptionUniqueness(
        string $dynFieldId,
        string $optionLabel,
        ?string $excludeOptionId = null
    ): bool {
        $model = new DynamicFieldDropdownOptions_Model();
        $existingOption = $model->getOptionByFieldAndLabel($dynFieldId, $optionLabel);

        if (empty($existingOption)) {
            return true;
        }

        if (!empty($excludeOptionId) && $existingOption['id'] === $excludeOptionId) {
            return true;
        }

        return false;
    }
}
