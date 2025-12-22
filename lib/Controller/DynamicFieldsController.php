<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Controller;

use OCA\Done\Models\DynamicFieldDropdownData_Model;
use OCA\Done\Models\DynamicFieldDropdownOptions_Model;
use OCA\Done\Models\DynamicFields_Model;
use OCA\Done\Models\DynamicFieldsData_Model;
use OCA\Done\Models\DynamicFieldsTypes_Model;
use OCA\Done\Models\RolesPermissions_Model;
use OCA\Done\Models\Table\TableColumnViewSettings_Model;
use OCA\Done\Models\Table\TableFilter_Model;
use OCA\Done\Models\Table\TableSortColumns_Model;
use OCA\Done\Models\Table\TableSortWithinColumns_Model;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class DynamicFieldsController extends AdminController
{
    /**
     * Save dynamic field value
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function saveDynamicFieldsData(IRequest $request): JSONResponse
    {
        $dynFieldId = $request->getParam('dyn_field_id');
        $recordId = $request->getParam('record_id');
        $value = $request->getParam('value');
        $slug = $request->getParam('slug');
        $isSave = empty($slug);

        if (empty($dynFieldId) || empty($recordId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $dynamicFieldsModel = new DynamicFields_Model();
        $dynamicFieldsDataModel = new DynamicFieldsData_Model();

        $dynField = $dynamicFieldsModel->getItem($dynFieldId);
        $dynFieldType = (int)$dynField['field_type'];
        $required = (bool)$dynField['required'];
        $title = $dynField['title'];

        if ($required && (!isset($value) || $value == '')) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => [
                        $dynFieldId => $this->translateService->getTranslate(
                            "The dynamic field «{$title}» is required",
                            ['title' => $title]
                        ),
                    ],
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $slug = $dynamicFieldsDataModel->saveDynamicFieldItem(
            $dynFieldType,
            $dynFieldId,
            $recordId,
            $value,
            $isSave,
            $slug,
        );

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Saved successfully'),
                'slug'    => $slug,
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Multiple save dynamic field values
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function saveDynamicFieldsDataMultiple(IRequest $request): JSONResponse
    {
        $data = $request->getParam('data');
        $recordId = $request->getParam('record_id');

        if (empty($data) || empty($recordId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $dynamicFieldsModel = new DynamicFields_Model();
        $dynamicFieldsDataModel = new DynamicFieldsData_Model();
        $slugs = $dynFieldsTypes = $validationErrors = [];

        foreach ($data as $item) {
            $dynFieldId = $item['dyn_field_id'];
            $value = $item['value'] ?? null;

            $dynField = $dynamicFieldsModel->getItem($dynFieldId);
            $dynFieldsTypes[$dynFieldId] = (int)$dynField['field_type'];
            $required = (bool)$dynField['required'];
            $title = $dynField['title'];

            if ($required && (!isset($value) || $value == '')) {
                $validationErrors[$dynFieldId] = "The dynamic field «{$title}» is required";
            }
        }

        if (!empty($validationErrors)) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => $validationErrors,
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        foreach ($data as $item) {
            $dynFieldId = $item['dyn_field_id'];
            $value = $item['value'] ?? null;
            $slug = $item['slug'] ?? null;
            $dynFieldType = $dynFieldsTypes[$dynFieldId];
            $isSave = empty($slug);

            $result = $dynamicFieldsDataModel->saveDynamicFieldItem(
                $dynFieldType,
                $dynFieldId,
                $recordId,
                $value,
                $isSave,
                $slug,
            );

            if (\is_array($result)) {
                foreach ($result as $slug) {
                    $slugs[] = $slug;
                }
            } else {
                $slugs[] = $result;
            }
        }

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Saved successfully'),
                'slugs'   => $slugs,
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Save dynamic field
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function saveDynamicField(IRequest $request): JSONResponse
    {
        $title = $request->getParam('title');
        $fieldType = $request->getParam('field_type');
        $source = $request->getParam('source');
        $required = $request->getParam('required', false);
        $slug = $request->getParam('slug');
        $isMultiple = $request->getParam('multiple', false);
        $isSave = empty($slug);

        if (empty($title) || empty($fieldType) || empty($source)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $dynamicFieldsModel = new DynamicFields_Model();

        $data = [
            'title'      => $title,
            'field_type' => $fieldType,
            'source'     => $source,
            'required'   => $required,
            'multiple'   => $isMultiple,
        ];

        if (!$isSave) {
            $dynFieldId = $dynamicFieldsModel->getItemIdBySlug($slug);
            $dynamicFieldsModel->update($data, $dynFieldId);
            $message = 'Field edited successfully';
        } else {
            $dynFieldId = $dynamicFieldsModel->addData($data);
            $message = 'Field created successfully';
        }

        return new JSONResponse(
            [
                'message' => $message,
                'slug'    => $dynFieldId,
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Delete dynamic field
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function deleteDynamicField(IRequest $request): JSONResponse
    {
        $slug = $request->getParam('slug');
        $dynamicFieldsModel = new DynamicFields_Model();
        $dynamicFieldsDataModel = new DynamicFieldsData_Model();
        $dynamicFieldsDropdownDataModel = new DynamicFieldDropdownData_Model();
        $dynamicFieldsDropdownOptionsModel = new DynamicFieldDropdownOptions_Model();
        $rolesPermissionsModel = new RolesPermissions_Model();
        $tableFilterModel = new TableFilter_Model();
        $tableSortColumnsModel = new TableSortColumns_Model();
        $tableSortWithinColumnsModel = new TableSortWithinColumns_Model();
        $tableColumnViewSettingModel = new TableColumnViewSettings_Model();
        $dynFieldId = $dynamicFieldsModel->getItemIdBySlug($slug);

        if (empty($dynFieldId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $dynamicFieldsModel->delete($dynFieldId);
        $dynamicFieldsDataModel->deleteByFilter(['dyn_field_id' => $dynFieldId]);
        $dynamicFieldsDropdownDataModel->deleteByFilter(['dyn_field_id' => $dynFieldId]);
        $dynamicFieldsDropdownOptionsModel->deleteByFilter(['dyn_field_id' => $dynFieldId]);
        $rolesPermissionsModel->deleteByFilter(['field' => $dynFieldId]);
        $tableFilterModel->deleteByFilter(['column' => $dynFieldId]);
        $tableSortColumnsModel->deleteByFilter(['column' => $dynFieldId]);
        $tableSortWithinColumnsModel->deleteByFilter(['column' => $dynFieldId]);
        $tableColumnViewSettingModel->deleteByFilter(['column' => $dynFieldId]);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Deleted successfully'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Get dynamic fields for entity
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getDynamicFieldsForSource(IRequest $request): JSONResponse
    {
        $source = (int)$request->getParam('source');

        if (empty($source)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            (new DynamicFields_Model())->getDynamicFieldsForSource($source),
            Http::STATUS_OK
        );
    }

    /**
     * Get dynamic field data for record
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getDinFieldsDataForRecord(IRequest $request): JSONResponse
    {
        $recordId = $request->getParam('record_id');

        if (empty($recordId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving data'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $data = (new DynamicFieldsData_Model())->getDataForRecord($recordId);

        return new JSONResponse(
            array_values($data),
            Http::STATUS_OK
        );
    }

    /**
     * Get dynamic field types
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getDinFieldsTypes(): JSONResponse
    {
        return new JSONResponse(
            DynamicFieldsTypes_Model::getFieldsTypes(),
            Http::STATUS_OK
        );
    }
}
