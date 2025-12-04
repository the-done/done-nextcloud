<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Controller;

use OCA\Done\Service\AppearanceService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * Controller for handling file requests
 */
class FileController extends OCSController
{
    private AppearanceService $appearanceService;

    public function __construct($appName, IRequest $request)
    {
        parent::__construct($appName, $request);
        $this->appearanceService = AppearanceService::getInstance();
    }

    /**
     * Get project file
     *
     * The method is used in the file lib/base.php
     *
     * @param string $entityId
     * @param string $fileType File type
     * @param string $fileName File name
     *
     * @return DataDownloadResponse|DataResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[PublicPage]
    public function getEntityFile(
        string $entityId,
        string $fileType,
        string $fileName
    ): DataDownloadResponse | DataResponse {
        $fileData = $this->appearanceService->getEntityFile($entityId, $fileType, $fileName);

        if ($fileData === null) {
            /** @phpstan-ignore argument.type */
            return new DataResponse(['error' => 'File not found'], Http::STATUS_NOT_FOUND);
        }

        $response = new DataDownloadResponse(
            $fileData['content'],
            $fileData['name'],
            $fileData['mimeType']
        );

        // Cache for 1 hour
        $response->cacheFor(3600, false, true);

        return $response;
    }
}
