<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class FieldCommentsController extends AdminController
{
    /**
     * Get comments for specific field
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getFieldComments(IRequest $request): JSONResponse
    {
        $source = $request->getParam('source');
        $field = $request->getParam('field');

        if (empty($source) || empty($field)) {
            return new JSONResponse([
                'message' => $this->translateService->getTranslate('Missing required parameters'),
            ], Http::STATUS_BAD_REQUEST);
        }

        $comments = $this->fieldCommentsService->getFieldCommentsForField((int)$source, $field);

        return new JSONResponse([
            'data' => $comments,
        ], Http::STATUS_OK);
    }

    /**
     * Save or update field comment
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function saveFieldComment(IRequest $request): JSONResponse
    {
        $data = $request->getParam('data');
        $commentId = $request->getParam('commentId');

        [$validatedData, $errors] = $this->fieldCommentsService->validateCommentData($data);

        if (!empty($errors)) {
            return new JSONResponse([
                'error_type' => 'validation',
                'message'    => $errors,
            ], Http::STATUS_BAD_REQUEST);
        }

        if (!empty($commentId)) {
            $result = $this->fieldCommentsService->updateComment($commentId, $validatedData);
        } else {
            $result = $this->fieldCommentsService->saveComment($validatedData);
        }

        if ($result['success']) {
            return new JSONResponse([
                'message' => $this->translateService->getTranslate('Comment saved successfully'),
                'data'    => $result['data'],
            ], Http::STATUS_OK);
        }

        // Проверяем, есть ли конкретные ошибки от сервиса
        $errorMessage = $this->translateService->getTranslate('Error saving comment');

        if (isset($result['errors']) && !empty($result['errors'])) {
            $errorMessage = $result['errors'][0];
        }

        return new JSONResponse([
            'message' => $errorMessage,
        ], Http::STATUS_BAD_REQUEST);
    }

    /**
     * Delete field comment
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function deleteFieldComment(IRequest $request): JSONResponse
    {
        $commentId = $request->getParam('commentId');

        if (empty($commentId)) {
            return new JSONResponse([
                'message' => $this->translateService->getTranslate('Comment ID is required'),
            ], Http::STATUS_BAD_REQUEST);
        }

        $success = $this->fieldCommentsService->deleteComment($commentId);

        if ($success) {
            return new JSONResponse([
                'message' => $this->translateService->getTranslate('Comment deleted successfully'),
            ], Http::STATUS_OK);
        }

        return new JSONResponse([
            'message' => $this->translateService->getTranslate('Error deleting comment'),
        ], Http::STATUS_BAD_REQUEST);
    }

    /**
     * Get all comments for source entity
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getFieldCommentsBySource(IRequest $request): JSONResponse
    {
        $source = $request->getParam('source');

        if (empty($source)) {
            return new JSONResponse([
                'message' => $this->translateService->getTranslate('Source is required'),
            ], Http::STATUS_BAD_REQUEST);
        }

        $comments = $this->fieldCommentsService->getFieldCommentsBySource((int)$source);

        return new JSONResponse([
            'data' => $comments,
        ], Http::STATUS_OK);
    }
}
