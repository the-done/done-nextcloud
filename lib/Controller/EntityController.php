<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


declare(strict_types=1);

namespace OCA\Done\Controller;

use OCA\Done\Attribute\RequireRole;
use OCA\Done\Models\Dictionaries\GlobalRoles_Model;
use OCA\Done\Models\PermissionsEntities_Model;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\Route;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class EntityController extends BaseController
{
    /**
     * Get entity data
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[Route(type: 'ajax', verb: 'POST', url: '/ajax/getDataToViewEntity')]
    public function getDataToViewEntity(IRequest $request): JSONResponse
    {
        $source = $request->getParam('source');
        $slug   = $request->getParam('slug');

        if (empty($source) || empty($slug) || !PermissionsEntities_Model::entityExists($source)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            ['data' => $this->entitiesService->getDataToViewEntity($source, $slug)],
            Http::STATUS_OK
        );
    }

    /**
     * Save project avatar
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[Route(type: 'ajax', verb: 'POST', url: '/ajax/saveEntityImage')]
    public function saveEntityImage(IRequest $request): JSONResponse
    {
        $source     = (int)$request->getParam('source');
        $slug       = $request->getParam('slug');
        $imageField = $request->getParam('field_name');
        $image      = $request->getUploadedFile('image');

        if (empty($source) || empty($slug) || !PermissionsEntities_Model::entityExists($source)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        if (empty($image)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('No file to upload'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $result['success'] = false;

        $result = $this->appearanceService->saveEntityImage($slug, $image, $imageField, $source);

        if ($result['success']) {
            return new JSONResponse(
                [
                    'message'  => $this->translateService->getTranslate('Saved successfully'),
                    'fileName' => $result['fileName'],
                ],
                Http::STATUS_OK
            );
        } else {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data').
                        $result['message'],
                ],
                Http::STATUS_BAD_REQUEST
            );
        }
    }

    /**
     * Save user field ordering
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[Route(type: 'ajax', verb: 'POST', url: '/ajax/saveFieldsOrdering')]
    public function saveFieldsOrdering(IRequest $request): JSONResponse
    {
        $source = (int)$request->getParam('source');
        $fields = $request->getParam('fields');

        if (empty($source) || !PermissionsEntities_Model::entityExists($source)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        if (empty($fields)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('No data to change'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $fieldsOrderingService = \OCA\Done\Service\FieldsOrderingService::getInstance();
        $result                = $fieldsOrderingService->saveFieldsOrdering($source, $fields);

        if ($result['success']) {
            return new JSONResponse(
                [
                    'message' => $result['message'],
                    'data'    => $result['data'],
                ],
                Http::STATUS_OK
            );
        } else {
            return new JSONResponse(
                [
                    'message' => $result['message'],
                ],
                Http::STATUS_BAD_REQUEST
            );
        }
    }

    /**
     * Reset to default field ordering
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[Route(type: 'ajax', verb: 'POST', url: '/ajax/resetFieldsOrdering')]
    public function resetToDefaultOrdering(IRequest $request): JSONResponse
    {
        $source = (int)$request->getParam('source');

        if (empty($source) || !PermissionsEntities_Model::entityExists($source)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $fieldsOrderingService = \OCA\Done\Service\FieldsOrderingService::getInstance();
        $result                = $fieldsOrderingService->resetToDefaultOrdering($source);

        if ($result['success']) {
            return new JSONResponse(
                [
                    'message' => $result['message'],
                ],
                Http::STATUS_OK
            );
        } else {
            return new JSONResponse(
                [
                    'message' => $result['message'],
                ],
                Http::STATUS_BAD_REQUEST
            );
        }
    }

    /**
     * Get field ordering
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[Route(type: 'ajax', verb: 'POST', url: '/ajax/getFieldsOrdering')]
    public function getFieldsOrdering(IRequest $request): JSONResponse
    {
        $source = (int)$request->getParam('source');

        if (empty($source) || !PermissionsEntities_Model::entityExists($source)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $fieldsOrderingService = \OCA\Done\Service\FieldsOrderingService::getInstance();
        $data                  = $fieldsOrderingService->getFieldsOrdering($source);

        return new JSONResponse(
            [
                'data' => $data,
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Save project color
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRoles_Model::OFFICER])]
    public function saveEntityColor(IRequest $request): JSONResponse
    {
        $entitySlug = $request->getParam('slug');
        $color      = $request->getParam('color');
        $source     = (int)$request->getParam('source');

        if (empty($color)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Color is required'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        if (empty($entitySlug)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to save'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $result = $this->appearanceService->saveEntityColor($entitySlug, $color, $source);

        if ($result['success']) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Color saved successfully'),
                    'color'   => $result['color'],
                ],
                Http::STATUS_OK
            );
        } else {
            return new JSONResponse(
                [
                    'message' => $result['message'],
                ],
                Http::STATUS_BAD_REQUEST
            );
        }
    }

    /**
     * Export entity data to Excel/CSV
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[Route(type: 'ajax', verb: 'POST', url: '/exportToExcel')]
    public function exportToExcel(IRequest $request): DataDownloadResponse|JSONResponse
    {
        $source  = (int)$request->getParam('source');
        $filters = $request->getParam('filters', []);
        $options = $request->getParam('options', []);

        if (empty($source) || !PermissionsEntities_Model::entityExists($source)) {
            return new JSONResponse(
                [
                    'success' => false,
                    'message' => $this->translateService->getTranslate('Invalid entity source'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $result = $this->excelExportService->exportEntityToExcel($source, $filters, $options);

        if (!$result['success']) {
            return new JSONResponse(
                [
                    'success' => false,
                    'message' => $result['message'],
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        // Return file for download
        $fileContent = file_get_contents($result['filePath']);
        $mimeType    = $result['format'] === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        // Clean up temporary file
        $this->excelExportService->cleanupTempFile($result['filePath']);

        return new DataDownloadResponse(
            $fileContent,
            $result['fileName'],
            $mimeType
        );
    }
}
