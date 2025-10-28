<?php

declare(strict_types=1);

namespace OCA\Done\Controller;

use OC_Template;
use OCA\Done\AppInfo\Application;
use OCA\Done\Models\Dictionaries\GlobalRoles_Model;
use OCA\Done\Models\RolesPermissions_Model;
use OCA\Done\Models\User_Model;
use OCA\Done\Models\UsersGlobalRoles_Model;
use OCA\Done\Models\Times_Model;
use OCA\Done\Models\TimesLog_Model;
use OCA\Done\Models\UsersRolesInProjects_Model;
use OCA\Talk\Exceptions\UnauthorizedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Server;

class CommonController extends BaseController
{
    #[NoCSRFRequired]
    #[NoAdminRequired]
    #[FrontpageRoute(verb: 'GET', url: '/')]
    public function index(): TemplateResponse|RedirectResponse
    {
        $appId          = Application::APP_ID;
        $currentUserObj = $this->userSession->getUser();

        if (!$currentUserObj instanceof IUser) {
            $urlGenerator = Server::get(IURLGenerator::class);
            $redirectUrl  = $urlGenerator->linkToRoute('done.common.index');

            return new RedirectResponse(
                $urlGenerator->linkToRouteAbsolute(
                    'core.login.showLoginForm',
                    ['redirect_url' => $redirectUrl]
                )
            );
        }

        \OCP\Util::addStyle($appId, 'main');

        $currentUserUid  = $currentUserObj->getUID();
        $currentUser     = (new User_Model())->getUserByUuid($currentUserUid);
        $currentUserId = $currentUser['id'] ?? null;

        if ($currentUserId) {
            \OCP\Util::addScript($appId, 'admin');
            \OCP\Util::addTranslations($appId);
        } else {
            $l = Server::get(\OCP\L10N\IFactory::class)->get('done');
            OC_Template::printErrorPage(
                '403',
                $l->t('Not enough permissions'),
                403
            );
        }

        return new TemplateResponse($appId, 'app/app', [
            'l' => $this->translateService->l10n
        ]);
    }

    /**
     * Get user statistics
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getUserStatistics(IRequest $request): JSONResponse
    {
        $dateFrom = $request->getParam('date_from');
        $dateTo   = $request->getParam('date_to');
        $projects = $request->getParam('projects');

        if (empty($dateFrom) || empty($dateTo)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Select interval')
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $userId = $this->userService->getCurrentUserId();

        return new JSONResponse(
            $this->userService->getUserStatistics($dateFrom, $dateTo, $userId, $projects ?? []),
            Http::STATUS_OK
        );
    }

    /**
     * Create report
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function saveUserTimeInfo(
        IRequest $request
    ): JSONResponse {
        $data           = $request->getParam('data');
        $currentUserObj = $this->userSession->getUser();
        $currentUserUid = $currentUserObj?->getUID();
        $currentUser    = (new User_Model())->getUserByUuid($currentUserUid);

        if (empty($currentUser)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Internal user is missing'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $timesModel        = new Times_Model();
        $data['user_id']   = $currentUser['id'];
        $data['status_id'] = Times_Model::SENT;

        [$data, $errors] = $timesModel->validateData(
            $data,
            true,
            $data['is_downtime'] ? ['description'] : []
        );

        if (!empty($errors)) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => $errors,
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        if ($timesModel->addData($data)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Report added successfully'),
                ],
                Http::STATUS_OK
            );
        }

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('An error occurred while creating the report'),
            ],
            Http::STATUS_BAD_REQUEST
        );
    }

    /**
     * Edit report
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function editUserTimeInfo(IRequest $request): JSONResponse
    {
        $data = $request->getParam('data');
        $slug = $request->getParam('slug');

        $timesModel = new Times_Model();
        [$data, $errors] = $timesModel->validateData($data);

        if (!empty($errors)) {
            return new JSONResponse(
                [
                    'error_type' => 'validation',
                    'message'    => $errors,
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $timesModel->update($data, $slug);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Report saved successfully'),
                'data'    => $data,
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Edit report sorting
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function editReportSort(IRequest $request): JSONResponse
    {
        $sort = $request->getParam('sort');
        $slug = $request->getParam('slug');

        $timesModel = new Times_Model();
        $reportId = $timesModel->getItemIdBySlug($slug);

        if (empty($reportId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('No data to save'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $timesModel->update(['sort' => ($sort ?? 0)], $reportId);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Report sorting saved'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Multiple edit report sorting
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function editReportSortMultiple(IRequest $request): JSONResponse
    {
        $data = $request->getParam('data');

        if (empty($data)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('No data to save'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $timesModel = new Times_Model();

        foreach ($data as $item) {
            if (empty($item['sort']) || empty($item['slug'])) {
                continue;
            }

            $timesModel->update(['sort' => $item['sort']], $item['slug']);
        }

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Report sorting saved'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Delete report
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deleteUserTimeInfo(IRequest $request): JSONResponse
    {
        $slug = $request->getParam('slug');

        if (empty($slug)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while deleting'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        (new Times_Model())->delete($slug);

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Report deleted'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Get report
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getUserTimeInfo(IRequest $request): JSONResponse
    {
        $slug = $request->getParam('slug');

        if (empty($slug)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('An error occurred while retrieving the report'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            (new Times_Model())->getItemByFilter(['id' => $slug]),
            Http::STATUS_OK
        );
    }

    /**
     * Get user permissions
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getPermissions(IRequest $request): JSONResponse
    {
        $input = $request->getParams();

        $userSlug     = $input['slug'] ?? null;
        $userSlugType = $input['slug_type'] ?? null;

        $defaultRights  = GlobalRoles_Model::getUsersDefaultRights();
        $currentUserObj = $this->userSession->getUser();

        if (!$currentUserObj && empty($userSlug)) {
            return new JSONResponse(
                [
                    'common' => $defaultRights,
                    'fields' => [],
                ],
                Http::STATUS_OK
            );
        }

        $currentUserUid = $currentUserObj->getUID();
        $userModel = new User_Model();

        if (!empty($userSlug)) {
            $userId  = $userModel->getItemIdBySlug($userSlug);
        } else {
            $currentUser    = $userModel->getUserByUuid($currentUserUid);
            $userId       = $currentUser['id'] ?? null;
        }

        if (!$userId && $this->groupManager->isAdmin($currentUserUid)) {
            $userId = $userModel->addFirstUser($currentUserUid);
        }

        $globalRoles = $this->userService->getUserGlobalRoles($userId);

        $commonPermissions = $userId ?
            (new UsersGlobalRoles_Model())->getRights($globalRoles) :
            $defaultRights;

        $fieldsPermissions = $userId ? (new RolesPermissions_Model())->getFieldsFullPermissions(
            ['global_role_id' => ['IN', $globalRoles]]
        ) : [];

        return new JSONResponse(
            [
                'common' => $commonPermissions,
                'fields' => $fieldsPermissions,
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Change report status
     *
     * @return JSONResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function sendReportToNextStatus(IRequest $request): JSONResponse
    {
        $reportId   = $request->getParam('reportId');
        $nextStatusId = (int)$request->getParam('nextStatusId');
        $comment      = '';

        $timesModel    = new Times_Model();
        $timesLogModel = new TimesLog_Model();

        $currentStatusId   = $timesModel->getCurrentStatusId($reportId);
        $availableStatuses = $timesModel->getAvailableNextStatuses($currentStatusId);

        if (!in_array($nextStatusId, $availableStatuses)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Unable to change status'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $requirements = $timesModel->getRequirementsForNextStatus($nextStatusId);

        if ($requirements['comment'] ?? false) {
            $comment = $request->getParam('comment');

            if (empty($comment)) {
                return new JSONResponse(
                    [
                        'message' => $this->translateService->getTranslate('A comment is required'),
                    ],
                    Http::STATUS_BAD_REQUEST
                );
            }
        }

        $timesModel->update(['status_id' => $nextStatusId], $reportId);
        $timesLogModel->addData(
            [
                'report_id'  => $reportId,
                'status_id'  => $nextStatusId,
                'comment'    => $comment,
                'created_at' => (new \DateTimeImmutable()),
            ]
        );

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Status changed successfully'),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Get projects user is assigned to
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getUserProjects(): JSONResponse
    {
        $userId = $this->userService->getCurrentUserId();

        if (empty($userId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to get projects'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            (new UsersRolesInProjects_Model())->getUserProjects($userId),
            Http::STATUS_OK
        );
    }

    /**
     * Get projects user is assigned to, sorted by last usage
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getUserProjectsForReport(): JSONResponse
    {
        $userId = $this->userService->getCurrentUserId();

        if (empty($userId)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('Not enough data to get projects'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        return new JSONResponse(
            (new UsersRolesInProjects_Model())->getUserProjectsForReport($userId),
            Http::STATUS_OK
        );
    }

    public function getTranslate(IRequest $request): JSONResponse
    {
        $key = $request->getParam('key');
        $isPlural = $request->getParam('key');
        $options   = $request->getParam('options');

        return new JSONResponse(
            $this->translateService->getTranslate($key, $isPlural, $options ?? []),
            Http::STATUS_OK
        );
    }
}
