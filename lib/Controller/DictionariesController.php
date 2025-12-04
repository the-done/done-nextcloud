<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Controller;

use OCA\Done\Models\User_Model;
use OCA\Done\Models\UsersGlobalRoles_Model;
use OCA\Done\Service\BaseService;
use OCA\Done\Service\DictionariesService;
use OCA\Done\Service\TranslateService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IRequest;
use OCP\IUserSession;

class DictionariesController extends OCSController
{
    protected IUserSession $userSession;
    private DictionariesService $dictionariesService;
    protected TranslateService $translateService;

    public function __construct(
        $appName,
        IRequest $request,
        IUserSession $userSession,
        DictionariesService $dictionariesService,
    ) {
        parent::__construct($appName, $request);
        $this->userSession = $userSession;
        $this->dictionariesService = $dictionariesService;
        $this->translateService = TranslateService::getInstance();
    }

    /**
     * @NoAdminRequired
     *
     * @NoCSRFRequired
     */
    public function saveDict(IRequest $request): JSONResponse
    {
        $dictTitle = $request->getParam('title');
        $data = $request->getParam('data');
        $slug = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');

        $dictionaryModel = $this->dictionariesService->getDictionaryModel($dictTitle);

        if (!empty($slug)) {
            $itemId = $dictionaryModel->getItemIdBySlug($slug);
            $dictionaryModel->update($data, $itemId);
        } else {
            $itemId = $dictionaryModel->addData($data);
        }

        if ($itemId) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Saved successfully'),
                    'slug'    => $itemId,
                ],
                Http::STATUS_OK
            );
        }

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('An error occurred while saving'),
            ],
            Http::STATUS_BAD_REQUEST
        );
    }

    /**
     * @NoAdminRequired
     *
     * @NoCSRFRequired
     */
    public function deleteDictItem(IRequest $request): JSONResponse
    {
        $dictTitle = $request->getParam('title');
        $slug = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');

        if (empty($dictTitle) || (empty($slug) && empty($slugType))) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $dictionaryModel = $this->dictionariesService->getDictionaryModel($dictTitle);
        $itemId = $dictionaryModel->getItemIdBySlug($slug);

        $dictionaryModel->delete($itemId);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Deleted successfully'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * @NoAdminRequired
     *
     * @NoCSRFRequired
     */
    public function getDictionaryData(IRequest $request): JSONResponse
    {
        $dictTitle = $request->getParam('title');

        if (empty($dictTitle)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $data = $this->dictionariesService->getDictionary($dictTitle);

        return new JSONResponse(
            $data,
            Http::STATUS_OK
        );
    }

    /**
     * @NoAdminRequired
     *
     * @NoCSRFRequired
     */
    public function getDictionaryItemData(IRequest $request): JSONResponse
    {
        $dictTitle = $request->getParam('title');
        $slug = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');

        if (empty($dictTitle) || (empty($slug) && empty($slugType))) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            $this->dictionariesService->getDictionaryItem($dictTitle, $slug, $slugType),
            Http::STATUS_OK
        );
    }

    /**
     * @NoAdminRequired
     *
     * @NoCSRFRequired
     */
    public function getGlobalRoles(): JSONResponse
    {
        return new JSONResponse(
            $this->dictionariesService->globalRolesDictionary->getIndexedListByFilter('id', [], ['id', 'name']),
            Http::STATUS_OK
        );
    }

    /**
     * Get specific user roles
     *
     * @NoAdminRequired
     *
     * @NoCSRFRequired
     */
    public function getUserGlobalRoles(IRequest $request): JSONResponse
    {
        $slug = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');

        $userId = (new User_Model())->getItemIdBySlug($slug);

        if (empty($userId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $data = (new UsersGlobalRoles_Model())->getListByFilter(['user_id' => ['=', $userId]]);
        $roles = $this->dictionariesService->globalRolesDictionary->getIndexedListByFilter('id', [], ['id', 'name']);

        foreach ($data as $idx => $item) {
            $roleId = $item['role_id'];
            $data[$idx]['rname'] = $roles[$roleId]['name'];
        }

        return new JSONResponse(
            $data,
            Http::STATUS_OK
        );
    }

    /**
     * Get users by global role
     *
     * @NoAdminRequired
     *
     * @NoCSRFRequired
     */
    public function getUsersByGlobalRole(IRequest $request): JSONResponse
    {
        $slug = $request->getParam('slug');
        $slugType = $request->getParam('slug_type');

        $roleId = $this->dictionariesService->globalRolesDictionary->getItemIdBySlug($slug);

        if (empty($roleId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $data = (new UsersGlobalRoles_Model())->getListByFilter(['role_id' => ['=', $roleId]]);
        $usersIds = BaseService::getField($data, 'user_id');
        $users = (new User_Model())
            ->getIndexedUsersFullNameWithPosition(['id' => ['IN', $usersIds, IQueryBuilder::PARAM_STR_ARRAY]]);

        foreach ($data as $idx => $item) {
            $userId = $item['user_id'];
            $user = $users[$userId] ?? [];

            if (empty($user)) {
                continue;
            }

            $data[$idx]['uname'] = $user['uname'] ?? '';
        }

        return new JSONResponse(
            $data,
            Http::STATUS_OK
        );
    }

    /**
     * Get next sort number in dictionary
     *
     * @NoAdminRequired
     *
     * @NoCSRFRequired
     */
    public function getNextSortInDict(IRequest $request): JSONResponse
    {
        $dictTitle = $request->getParam('title');

        if (empty($dictTitle)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $model = $this->dictionariesService->getDictionaryModel($dictTitle);

        if (!$model) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $lastSortItem = $model->getItemByFilter([], ['id', 'sort'], ['sort', 'DESC']);
        $nextSort = !empty($lastSortItem) ? $lastSortItem['sort'] + 1 : 0;

        return new JSONResponse(
            $nextSort,
            Http::STATUS_OK
        );
    }
}
