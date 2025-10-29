<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


namespace OCA\Done\Middleware;

use OC\Group\Manager;
use OC_Template;
use OCA\Done\Controller\AdminController;
use OCA\Done\Controller\CommonController;
use OCA\Done\Controller\DictionariesController;
use OCA\Done\Models\Dictionaries\GlobalRoles_Model;
use OCA\Done\Models\User_Model;
use OCA\Done\Models\UsersGlobalRoles_Model;
use OCA\Done\Service\BaseService;
use OCA\Done\Service\DictionariesService;
use OCA\Done\Service\UserService;
use OCA\Files_Sharing\Controller\ShareAPIController;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use \OCP\AppFramework\Middleware;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Server;


class PermissionMiddleware extends Middleware
{
    protected IUserSession $userSession;
    private BaseService $baseService;
    private UserService $userService;
    private Manager|IGroupManager $groupManager;

    public function __construct(
        IUserSession $userSession,
        BaseService $baseService,
        UserService $userService,
        IGroupManager $groupManager,
    ) {
        $this->userSession  = $userSession;
        $this->baseService  = $baseService;
        $this->userService  = $userService;
        $this->groupManager = $groupManager;
    }


    /**
     * @param Controller $controller
     * @param string $methodName
     */
    public function beforeController(Controller $controller, string $methodName): RedirectResponse
    {
        $urlGenerator   = Server::get(IURLGenerator::class);
        $currentUserObj = $this->userSession->getUser();

        if (!$currentUserObj instanceof IUser) {
            $redirectUrl = $urlGenerator->linkToRoute('done.common.index');

            return new RedirectResponse(
                $urlGenerator->linkToRouteAbsolute(
                    'core.login.showLoginForm',
                    ['redirect_url' => $redirectUrl]
                )
            );
        }

        $userModel            = new User_Model();
        $userRolesGlobalModel = new UsersGlobalRoles_Model();
        $currentUserUid       = $currentUserObj->getUID();
        $currentUser          = $userModel->getUserByUuid($currentUserUid);
        $currentUserId        = $currentUser['id'] ?? null;
        $isAdmin              = $this->groupManager->isAdmin($currentUserUid);
        $adminOrDictInstance  = $controller instanceof AdminController || $controller instanceof DictionariesController;

        if ($isAdmin && !$currentUserId) {
            $currentUserId = $userModel->addData([
                'user_id'           => $currentUserUid,
                'user_display_name' => $currentUserUid,
                'lastname'          => $currentUserUid,
                'name'              => $currentUserUid,
                'middle_name'       => $currentUserUid,
            ]);

            $userRolesGlobalModel->addData([
                'user_id' => $currentUserId,
                'role_id' => GlobalRoles_Model::ADMIN,
            ]);
            $userRolesGlobalModel->addData([
                'user_id' => $currentUserId,
                'role_id' => GlobalRoles_Model::OFFICER,
            ]);
        }

        if ($currentUserId && $adminOrDictInstance) {
            $globalRoles = $this->userService->getUserGlobalRoles($currentUserId);
            $roles       = [
                GlobalRoles_Model::ADMIN,
                GlobalRoles_Model::OFFICER,
                GlobalRoles_Model::HEAD,
                GlobalRoles_Model::CURATOR,
            ];

            if (empty(array_intersect($roles, $globalRoles)) && !$isAdmin) {
                $l = Server::get(\OCP\L10N\IFactory::class)->get('lib');
                OC_Template::printErrorPage(
                    '403',
                    $l->t('Not enough permissions'),
                    403
                );
            }
        } elseif (!$currentUserId && !$isAdmin) {
            $l = Server::get(\OCP\L10N\IFactory::class)->get('lib');
            OC_Template::printErrorPage(
                '403',
                $l->t('Not enough permissions'),
                403
            );
        }

        $controllerName = '';

        if ($controller instanceof AdminController) {
            $controllerName = 'admin';
        } elseif ($controller instanceof DictionariesController) {
            $controllerName = 'dictionaries';
        } elseif ($controller instanceof CommonController) {
            $controllerName = 'common';
        }

        $routeName = "done.{$controllerName}.{$methodName}";

        return new RedirectResponse(
            $urlGenerator->linkToRouteAbsolute(
                $routeName,
            )
        );
    }
}