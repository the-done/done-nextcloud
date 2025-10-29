<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


declare(strict_types=1);

namespace OCA\Done\Service;

use OCA\Done\Models\DynamicFields_Model;
use OCA\Done\Models\FieldsOrdering_Model;
use OCA\Done\Models\PermissionsEntities_Model;
use OCP\IDBConnection;
use OCP\Server;

class FieldsOrderingService
{
    private static FieldsOrderingService $instance;

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
            self::$instance = Server::get(FieldsOrderingService::class);
        }
        return self::$instance;
    }

    /**
     * Validation of field ordering data
     *
     * @param int   $entityId Entity ID
     * @param array $fields   Object of fields with ordering {"name": 1, "email": 2, ...}
     * @return array [$validatedData, $errors]
     */
    public function validateFieldsOrdering(int $entityId, array $fields): array
    {
        $errors = [];
        $validatedData = [];

        // Check entity existence
        if (!PermissionsEntities_Model::entityExists($entityId)) {
            $errors[] = $this->translateService->getTranslate('Invalid entity');
            return [$validatedData, $errors];
        }

        // Get entity model for field validation
        $entities = PermissionsEntities_Model::getPermissionsEntities($entityId);
        $modelClass = $entities[$entityId]['model'];
        $entityModel = new $modelClass();
        $availableEntityFields = $entityModel->getAvailableEntityFields();

        $dynFieldsModel = new DynamicFields_Model();
        $entityDynFields = $dynFieldsModel->getDynamicFieldsForSource($entityId);

        foreach ($entityDynFields as $dynField) {
            $availableEntityFields[] = $dynField['id'];
        }

        foreach ($fields as $field => $ordering) {
            // Field name validation
            if (empty($field)) {
                $errors[] = $this->translateService->getTranslate('Field name is required');
                continue;
            }

            // Check that field exists in Entity
            if (!in_array($field, $availableEntityFields)) {
                $errors[] = $this->translateService->getTranslate('Field ' . $field . ' does not exist in entity');
                continue;
            }

            // Ordering validation
            if (!is_numeric($ordering) || $ordering < 0) {
                $errors[] = $this->translateService->getTranslate('Ordering must be a non-negative number');
                continue;
            }

            $validatedData[] = [
                'field'    => $field,
                'ordering' => (int)$ordering
            ];
        }

        return [$validatedData, $errors];
    }

    /**
     * Save/update field ordering
     *
     * @param int   $entityId Entity ID
     * @param array $fields   Array of fields with ordering
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function saveFieldsOrdering(int $entityId, array $fields): array
    {
        // Data validation
        [$validatedFields, $errors] = $this->validateFieldsOrdering($entityId, $fields);

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode(', ', $errors),
                'data'    => []
            ];
        }

        // Get current user
        $userId = $this->userService->getCurrentUserId();

        if (empty($userId)) {
            return [
                'success' => false,
                'message' => $this->translateService->getTranslate('User not found'),
                'data'    => []
            ];
        }

        $model = new FieldsOrdering_Model();
        $savedData = [];

        try {
            $this->db->beginTransaction();

            foreach ($validatedFields as $fieldData) {
                $field = $fieldData['field'];
                $ordering = $fieldData['ordering'];

                // Filter for searching existing record
                $filter = [
                    'user_id'   => $userId,
                    'entity_id' => $entityId,
                    'field'     => $field
                ];

                // Data for save/update
                $data = [
                    'user_id'   => $userId,
                    'entity_id' => $entityId,
                    'field'     => $field,
                    'ordering'  => $ordering
                ];

                // Data validation through model
                [$validatedData, $validationErrors] = $model->validateData($data, true);

                if (!empty($validationErrors)) {
                    throw new \Exception(implode(', ', $validationErrors));
                }

                // Use upsertByFilter for automatic INSERT/UPDATE
                $id = $model->upsertByFilter($validatedData, $filter);

                if ($id) {
                    $savedData[] = array_merge($validatedData, ['id' => $id]);
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => $this->translateService->getTranslate('Fields ordering saved successfully'),
                'data'    => $savedData
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $this->translateService->getTranslate('Error saving fields ordering') . ': ' .
                    $e->getMessage(),
                'data'    => []
            ];
        }
    }

    /**
     * Get field ordering for user and entity
     *
     * @param int $entityId Entity ID
     * @return array Array of fields with ordering, sorted by ordering and field name
     */
    public function getFieldsOrdering(int $entityId): array
    {
        $userId = $this->userService->getCurrentUserId();

        if (empty($userId)) {
            return [];
        }

        $model = new FieldsOrdering_Model();
        $records = $model->getListByFilter([
            'user_id'   => $userId,
            'entity_id' => $entityId
            ],
            ['user_id','entity_id','field','ordering',]
        );

        // Sort by ordering (primary) and field name (secondary)
        usort($records, function ($a, $b) {
            if ($a['ordering'] !== $b['ordering']) {
                return $a['ordering'] <=> $b['ordering'];
            }
            return strcmp($a['field'], $b['field']);
        });

        return $records;
    }

    /**
     * Reset to default ordering (complete removal of all records for user and entity)
     *
     * @param int $entityId Entity ID
     * @return array ['success' => bool, 'message' => string]
     */
    public function resetToDefaultOrdering(int $entityId): array
    {
        $userId = $this->userService->getCurrentUserId();
        if (empty($userId)) {
            return [
                'success' => false,
                'message' => $this->translateService->getTranslate('User not found')
            ];
        }

        $model = new FieldsOrdering_Model();

        // Get all records for user and entity BEFORE transaction
        $records = $model->getListByFilter([
            'user_id'   => $userId,
            'entity_id' => $entityId
        ]);

        // If records not found, settings were not set
        if (empty($records)) {
            return [
                'success' => true,
                'message' => $this->translateService->getTranslate('Fields ordering is already in default state')
            ];
        }

        try {
            $this->db->beginTransaction();

            // Complete removal of each record
            foreach ($records as $record) {
                $model->delete($record['id']);
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => $this->translateService->getTranslate('Fields ordering reset to default successfully')
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $this->translateService->getTranslate('Error resetting fields ordering')
            ];
        }
    }
}
