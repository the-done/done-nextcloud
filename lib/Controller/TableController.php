<?php

declare(strict_types=1);

namespace OCA\Done\Controller;

use OCA\Done\Models\Table\TableColumnViewSettings_Model;
use OCA\Done\Models\Table\TableFilter_Model;
use OCA\Done\Models\Table\TableSortColumns_Model;
use OCA\Done\Models\Table\TableSortWithinColumns_Model;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;

class TableController extends AdminController
{
    /**
     * Save table column view
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveTableColumnView(IRequest $request): JSONResponse
    {
        $source = $request->getParam('source');
        $column = $request->getParam('column');
        $width  = $request->getParam('width');
        $hide   = $request->getParam('hide', false);
        $forAll = $request->getParam('for_all', false);
        $slug   = $request->getParam('slug');
        $isSave = empty($slug);

        if (empty($width)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to save'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $tableColumnViewSettingsModel = new TableColumnViewSettings_Model();
        $userId                       = $this->userService->getCurrentUserId();

        $data = [
            'user_id' => $userId,
            'source'  => $source,
            'column'  => $column,
            'hide'    => (bool)$hide,
            'width'   => $width,
            'for_all' => (bool)$forAll,
        ];

        if ($forAll) {
            unset($data['user_id']);
        }

        [$data, $errors] = $tableColumnViewSettingsModel->validateData($data, $isSave);

        if (!empty($errors)) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => $errors,
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $slug = $isSave ? $tableColumnViewSettingsModel->addData($data) : $tableColumnViewSettingsModel->update(
            $data,
            $slug
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
     * Save table column sorting
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveTableSortColumns(IRequest $request): JSONResponse
    {
        $source   = $request->getParam('source');
        $column   = $request->getParam('column');
        $ordering = $request->getParam('ordering');
        $forAll   = $request->getParam('for_all', false);
        $slug     = $request->getParam('slug');
        $isSave   = empty($slug);

        $tableSortColumnsModel = new TableSortColumns_Model();
        $userId                = $this->userService->getCurrentUserId();

        $data = [
            'user_id'  => $userId,
            'source'   => $source,
            'column'   => $column,
            'ordering' => $ordering,
            'for_all'  => (bool)$forAll,
        ];

        if ($forAll) {
            unset($data['user_id']);
        }

        [$data, $errors] = $tableSortColumnsModel->validateData($data, $isSave);

        if (!empty($errors)) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => $errors,
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $slug = $isSave ? $tableSortColumnsModel->addData($data) : $tableSortColumnsModel->update($data, $slug);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Saved successfully'),
                'slug'    => $slug,
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Save table column sorting
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveTableSortColumnsMultiple(IRequest $request): JSONResponse
    {
        $sortData              = $request->getParam('sort_data');
        $source                = $request->getParam('source');
        $forAll                = (bool)$request->getParam('for_all', false);
        $userId                = $this->userService->getCurrentUserId();
        $tableSortColumnsModel = new TableSortColumns_Model();

        foreach ($sortData as $idx => $column) {
            $ordering = $idx + 1;

            $data = [
                'user_id'  => $userId,
                'source'   => $source,
                'column'   => $column,
                'ordering' => $ordering,
                'for_all'  => $forAll,
            ];

            $filter = [
                'user_id' => $userId,
                'source'  => $source,
                'column'  => $column,
                'for_all' => $forAll,
            ];

            if ($forAll) {
                unset($data['user_id'], $filter['user_id']);
            }

            $tableSortColumnsModel->upsertByFilter($data, $filter);
        }

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Saved successfully'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Save table sorting within columns
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveTableSortWithinColumns(IRequest $request): JSONResponse
    {
        $source       = $request->getParam('source');
        $column       = $request->getParam('column');
        $sort         = $request->getParam('sort', 'ASC');
        $sortOrdering = $request->getParam('sort_ordering');
        $forAll       = (bool)$request->getParam('for_all', false);
        $slug         = $request->getParam('slug');
        $isSave       = empty($slug);

        $tableSortWithinColumnsModel = new TableSortWithinColumns_Model();
        $userId                      = $this->userService->getCurrentUserId();
        $sort                        = $sort === 'ASC';

        $data = [
            'user_id'       => $userId,
            'source'        => $source,
            'column'        => $column,
            'sort'          => $sort,
            'sort_ordering' => $sortOrdering,
            'for_all'       => $forAll,
        ];

        if ($forAll) {
            unset($data['user_id']);
        }

        [$data, $errors] = $tableSortWithinColumnsModel->validateData($data, $isSave);

        if (!empty($errors)) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => $errors,
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $slug = $isSave ? $tableSortWithinColumnsModel->addData($data) : $tableSortWithinColumnsModel->update(
            $data,
            $slug
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
     * Multiple save table sorting within columns
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveTableSortWithinColumnsMultiple(IRequest $request): JSONResponse
    {
        $sortData                    = $request->getParam('sort_data');
        $source                      = $request->getParam('source');
        $userId                      = $this->userService->getCurrentUserId();
        $tableSortWithinColumnsModel = new TableSortWithinColumns_Model();

        foreach ($sortData as $idx => $data) {
            $sortOrdering = $idx + 1;
            $column       = $data['column'];
            $sort         = $data['sort'] ?? 'ASC';
            $forAll       = (bool)$data['for_all'] ?? false;

            $sort = $sort === 'ASC';

            $data = [
                'user_id'       => $userId,
                'source'        => $source,
                'column'        => $column,
                'sort'          => $sort,
                'sort_ordering' => $sortOrdering,
                'for_all'       => $forAll,
            ];

            $filter = [
                'user_id' => $userId,
                'source'  => $source,
                'column'  => $column,
                'for_all' => $forAll,
            ];

            if ($forAll) {
                unset($data['user_id'], $filter['user_id']);
            }

            $tableSortWithinColumnsModel->upsertByFilter($data, $filter);
        }

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Saved successfully'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Save table filter
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveTableFilter(IRequest $request): JSONResponse
    {
        $source    = $request->getParam('source');
        $column    = $request->getParam('column');
        $condition = $request->getParam('condition');
        $value     = $request->getParam('value');
        $forAll    = $request->getParam('for_all', false);
        $slug      = $request->getParam('slug');
        $isSave    = empty($slug);

        $tableFilterModel = new TableFilter_Model();
        $userId           = $this->userService->getCurrentUserId();

        if (in_array(
            $condition,
            [TableFilter_Model::CONTAINS_CONDITION, TableFilter_Model::DOES_NOT_CONTAIN_CONDITION]
        )
        ) {
            $value = implode(',', $value);
        }

        $data = [
            'user_id'   => $userId,
            'source'    => $source,
            'column'    => $column,
            'condition' => $condition,
            'value'     => $value,
            'for_all'   => (bool)$forAll,
        ];

        if ($forAll) {
            unset($data['user_id']);
        }

        [$data, $errors] = $tableFilterModel->validateData($data, $isSave);

        if (!empty($errors)) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => $errors,
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $slug = $isSave ? $tableFilterModel->addData($data) : $tableFilterModel->update($data, $slug);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Saved successfully'),
                'slug'    => $slug,
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Delete table column view
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deleteTableColumnView(IRequest $request): JSONResponse
    {
        $slug = $request->getParam('slug');

        if (empty($slug)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to delete'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        (new TableColumnViewSettings_Model())->delete($slug);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Deleted successfully'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Delete table column sorting
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deleteTableSortColumns(IRequest $request): JSONResponse
    {
        $slug                  = $request->getParam('slug');
        $source                = $request->getParam('source');
        $tableSortColumnsModel = new TableSortColumns_Model();
        $currentUserId         = $this->userService->getCurrentUserId();

        if (empty($slug) && !empty($currentUserId) && !empty($source)) {
            $tableSortColumnsModel->deleteByFilter(['user_id' => $currentUserId, 'source' => $source]);
        } elseif (!empty($slug)) {
            $tableSortColumnsModel->delete($slug);
        } else {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to delete'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Deleted successfully'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Delete table sorting within columns
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deleteTableSortWithinColumns(IRequest $request): JSONResponse
    {
        $slug                        = $request->getParam('slug');
        $source                      = $request->getParam('source');
        $tableSortWithinColumnsModel = new TableSortWithinColumns_Model();
        $currentUserId               = $this->userService->getCurrentUserId();

        if (empty($slug) && !empty($currentUserId) && !empty($source)) {
            $tableSortWithinColumnsModel->deleteByFilter(['user_id' => $currentUserId, 'source' => $source]);
        } elseif (!empty($slug)) {
            $tableSortWithinColumnsModel->delete($slug);
        } else {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to delete'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Deleted successfully'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Delete table filter
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deleteTableFilter(IRequest $request): JSONResponse
    {
        $slug             = $request->getParam('slug');
        $source           = $request->getParam('source');
        $tableFilterModel = new TableFilter_Model();
        $currentUserId    = $this->userService->getCurrentUserId();

        if (empty($slug) && !empty($currentUserId) && !empty($source)) {
            $tableFilterModel->deleteByFilter(['user_id' => $currentUserId, 'source' => $source]);
        } elseif (!empty($slug)) {
            $tableFilterModel->delete($slug);
        } else {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to delete'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Deleted successfully'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Get filter conditions
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getConditionsForFilter(): JSONResponse
    {
        return new JSONResponse(
            TableFilter_Model::getConditions(),
            Http::STATUS_OK
        );
    }
}