<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Middleware;

use OC\Group\Manager;
use OCA\Done\Controller\AdminController;
use OCA\Done\Controller\DictionariesController;
use OCA\Done\Exception\PermissionDeniedException;
use OCA\Done\Models\Dictionaries\GlobalRolesModel;
use OCA\Done\Models\UserModel;
use OCA\Done\Models\UsersGlobalRolesModel;
use OCA\Done\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Server;

class PermissionMiddleware extends Middleware
{
    protected IUserSession $userSession;
    private UserService $userService;
    private IGroupManager | Manager $groupManager;

    public function __construct(
        IUserSession $userSession,
        UserService $userService,
        IGroupManager $groupManager,
    ) {
        $this->userSession = $userSession;
        $this->userService = $userService;
        $this->groupManager = $groupManager;
    }

    /**
     * Check permissions before the controller is executed
     *
     * @param Controller $controller
     * @param string     $methodName
     *
     * @throws PermissionDeniedException
     */
    public function beforeController($controller, $methodName): void
    {
        $currentUserObj = $this->userSession->getUser();

        if (!$currentUserObj instanceof IUser) {
            throw new PermissionDeniedException('User not logged in');
        }

        $userModel = new UserModel();
        $userRolesGlobalModel = new UsersGlobalRolesModel();
        $currentUserUid = $currentUserObj->getUID();
        $currentUser = $userModel->getUserByUuid($currentUserUid);
        $currentUserId = $currentUser['id'] ?? null;
        $isAdmin = $this->groupManager->isAdmin($currentUserUid);
        $adminOrDictInstance = $controller instanceof AdminController || $controller instanceof DictionariesController;

        // Automatically create user for admin on first login
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
                'role_id' => GlobalRolesModel::ADMIN,
            ]);
            $userRolesGlobalModel->addData([
                'user_id' => $currentUserId,
                'role_id' => GlobalRolesModel::OFFICER,
            ]);
        }

        // Check access permissions for AdminController and DictionariesController
        if ($currentUserId && $adminOrDictInstance) {
            $globalRoles = $this->userService->getUserGlobalRoles($currentUserId);
            $roles = [
                GlobalRolesModel::ADMIN,
                GlobalRolesModel::OFFICER,
                GlobalRolesModel::HEAD,
                GlobalRolesModel::CURATOR,
            ];

            if (empty(array_intersect($roles, $globalRoles)) && !$isAdmin) {
                throw new PermissionDeniedException('Not enough permissions');
            }
        } elseif (!$currentUserId && !$isAdmin) {
            throw new PermissionDeniedException('Not enough permissions');
        }

        // If all checks passed successfully, simply return (middleware should return nothing)
    }

    /**
     * Handle permission denied exceptions
     *
     * @param Controller $controller
     * @param string     $methodName
     * @param \Exception $exception
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function afterException($controller, $methodName, \Exception $exception): Response
    {
        if ($exception instanceof PermissionDeniedException) {
            $l = Server::get(\OCP\L10N\IFactory::class)->get('done');
            $request = Server::get(IRequest::class);

            // Determine if this is an API request or a regular web request
            $isAjaxRequest = stripos($request->getHeader('Accept'), 'application/json') !== false
                || str_contains($request->getPathInfo(), '/ajax/');

            if ($isAjaxRequest) {
                // For AJAX/API requests return JSON

                return new JSONResponse(
                    ['error' => $exception->getMessage()],
                    Http::STATUS_FORBIDDEN
                );
            }
            // For regular requests return error page or redirect to login
            $currentUserObj = $this->userSession->getUser();

            if (!$currentUserObj instanceof IUser) {
                // If user is not logged in - redirect to login
                $urlGenerator = Server::get(IURLGenerator::class);
                $redirectUrl = $urlGenerator->linkToRoute('done.common.index');

                return new RedirectResponse(
                    $urlGenerator->linkToRouteAbsolute(
                        'core.login.showLoginForm',
                        ['redirect_url' => $redirectUrl]
                    )
                );
            }
            // If logged in but no permissions - show 403
            $response = new TemplateResponse('core', '403', [
                'message' => $l->t('Not enough permissions'),
            ], TemplateResponse::RENDER_AS_ERROR);
            $response->setStatus(Http::STATUS_FORBIDDEN);

            return $response;
        }

        throw $exception;
    }
}
