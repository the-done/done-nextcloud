<?php

declare(strict_types=1);

namespace OCA\Done\Service;

use OCA\Done\Models\DynamicFields_Model;
use OCA\Done\Models\FieldComment_Model;
use OCA\Done\Models\PermissionsEntities_Model;
use OCP\Server;

class FieldCommentsService
{
    protected FieldComment_Model $fieldCommentModel;
    protected DynamicFields_Model $dynamicFieldsModel;
    protected TranslateService $translateService;
    private static FieldCommentsService $instance;

    public function __construct()
    {
        $this->fieldCommentModel = new FieldComment_Model();
        $this->dynamicFieldsModel = new DynamicFields_Model();
        $this->translateService = TranslateService::getInstance();
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = Server::get(FieldCommentsService::class);
        }
        return self::$instance;
    }

    /**
     * Get comments for specific field
     */
    public function getFieldCommentsForField(int $source, string $field): array
    {
        if (!$this->validateSource($source)) {
            return [];
        }

        return $this->fieldCommentModel->getFieldCommentsForField($source, $field);
    }

    /**
     * Save new comment
     */
    public function saveComment(array $data): array
    {
        [$validatedData, $errors] = $this->validateCommentData($data);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Проверяем, существует ли уже комментарий для этого поля
        if ($this->fieldCommentModel->commentExistsForField(
            (int)$validatedData['source'], 
            $validatedData['field']
        )) {
            return [
                'success' => false, 
                'errors' => [$this->translateService->getTranslate('Comment already exists for this field')]
            ];
        }

        $commentId = $this->fieldCommentModel->addData($validatedData);

        if ($commentId) {
            $comment = $this->fieldCommentModel->getItemByFilter(['id' => $commentId]);
            if (empty($comment)) {
                return ['success' => false, 'errors' => ['Failed to retrieve saved comment']];
            }
            return ['success' => true, 'data' => $comment];
        }

        return ['success' => false, 'errors' => ['Error while saving']];
    }

    /**
     * Update existing comment
     */
    public function updateComment(string $commentId, array $data): array
    {
        [$validatedData, $errors] = $this->validateCommentData($data);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $this->fieldCommentModel->update($validatedData, $commentId);
        $comment = $this->fieldCommentModel->getItemByFilter(['id' => $commentId]);

        if (empty($comment)) {
            return ['success' => false, 'errors' => ['Failed to retrieve updated comment']];
        }

        return ['success' => true, 'data' => $comment];
    }

    /**
     * Delete comment
     */
    public function deleteComment(string $commentId): bool
    {
        try {
            $this->fieldCommentModel->delete($commentId);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all comments for source entity
     */
    public function getFieldCommentsBySource(int $source): array
    {
        if (!$this->validateSource($source)) {
            return [];
        }

        return $this->fieldCommentModel->getFieldCommentsBySource($source);
    }

    /**
     * Validate comment data
     */
    public function validateCommentData(array $data): array
    {
        $errors = [];

        // Validate source
        if (empty($data['source'])) {
            $errors[] = $this->translateService->getTranslate('Source is required');
        } elseif (!$this->validateSource((int)$data['source'])) {
            $errors[] = $this->translateService->getTranslate('Invalid entity');
        }

        // Validate field
        if (empty($data['field'])) {
            $errors[] = $this->translateService->getTranslate('Field is required');
        } elseif (!$this->validateField((int)$data['source'], $data['field'])) {
            $errors[] = $this->translateService->getTranslate('Field does not exist in entity');
        }

        // Validate comment
        if (empty($data['comment'])) {
            $errors[] = $this->translateService->getTranslate('Comment text is required');
        } elseif (strlen(trim($data['comment'])) < 3) {
            $errors[] = $this->translateService->getTranslate('Comment must be at least 3 characters');
        }

        return [$data, $errors];
    }

    /**
     * Validate source entity
     */
    private function validateSource(int $source): bool
    {
        return PermissionsEntities_Model::entityExists($source);
    }

    /**
     * Validate field
     */
    private function validateField(int $source, string $field): bool
    {
        if (!$this->validateSource($source)) {
            return false;
        }

        $sourceFields = $this->getSourceAllFields($source);

        return in_array($field, $sourceFields);
    }

    /**
     * Get all fields for entity
     *
     * @param int $source
     * @return array
     */
    private function getSourceAllFields(int $source): array
    {
        $sourceData      = PermissionsEntities_Model::getPermissionsEntities($source);
        $model           = new $sourceData[$source]['model']();
        $modelFields     = array_keys($model->fields);
        $entityDynFields = array_keys($this->dynamicFieldsModel->getIndexedListByFilter(filter: ['source' => $source]));

        return array_merge($modelFields, $entityDynFields);
    }
}
