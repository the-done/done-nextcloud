<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


declare(strict_types=1);

namespace OCA\Done\Controller;

use OCA\Done\Service\DynFieldDDownOptionsService;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;

class DynamicFieldsOptionController extends AdminController
{
    /**
     * Save dropdown option (add or update)
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveDropdownOption(IRequest $request): JSONResponse
    {
        $dynFieldId = $request->getParam('dyn_field_id');
        $optionLabel = $request->getParam('option_label');
        $ordering = $request->getParam('ordering') ? (int)$request->getParam('ordering') : null;
        $slug = $request->getParam('slug');

        if (empty($dynFieldId) || empty($optionLabel)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $service = DynFieldDDownOptionsService::getInstance();
        $result = $service->saveOption($dynFieldId, $optionLabel, $ordering, $slug);

        if ($result['success']) {
            return new JSONResponse(
                [
                    'message' => $result['message'],
                    'data' => $result['data'],
                ],
                Http::STATUS_OK
            );
        }

        return new JSONResponse(
            [
                'message' => $result['message'],
            ],
            Http::STATUS_BAD_REQUEST
        );
    }

    /**
     * Delete dropdown option
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deleteDropdownOption(IRequest $request): JSONResponse
    {
        $slug = $request->getParam('slug');

        if (empty($slug)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $service = DynFieldDDownOptionsService::getInstance();
        $result = $service->deleteOption($slug);

        if ($result['success']) {
            return new JSONResponse(
                [
                    'message' => $result['message'],
                ],
                Http::STATUS_OK
            );
        }

        return new JSONResponse(
            [
                'message' => $result['message'],
            ],
            Http::STATUS_BAD_REQUEST
        );
    }

    /**
     * Get dropdown options for field
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getDropdownOptions(IRequest $request): JSONResponse
    {
        $dynFieldId = $request->getParam('dyn_field_id');

        if (empty($dynFieldId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $service = DynFieldDDownOptionsService::getInstance();
        $result = $service->getOptionsForField($dynFieldId);

        if ($result['success']) {
            return new JSONResponse(
                $result['data'],
                Http::STATUS_OK
            );
        }

        return new JSONResponse(
            [
                'message' => $result['message'],
            ],
            Http::STATUS_BAD_REQUEST
        );
    }

    /**
     * Reorder dropdown options
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function reorderDropdownOptions(IRequest $request): JSONResponse
    {
        $dynFieldId = $request->getParam('dyn_field_id');
        $optionIds = $request->getParam('option_ids');

        if (empty($dynFieldId) || empty($optionIds) || !is_array($optionIds)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $service = DynFieldDDownOptionsService::getInstance();
        $result = $service->reorderOptions($dynFieldId, $optionIds);

        if ($result['success']) {
            return new JSONResponse(
                [
                    'message' => $result['message'],
                ],
                Http::STATUS_OK
            );
        }

        return new JSONResponse(
            [
                'message' => $result['message'],
            ],
            Http::STATUS_BAD_REQUEST
        );
    }
}
