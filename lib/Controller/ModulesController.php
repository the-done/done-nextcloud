<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Controller;

use OCA\Done\Demo\DemoModuleService;
use OCA\Done\Modules\BaseModuleService;
use OCA\Done\Service\TranslateService;
use OCA\Done\Service\UserService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Server;

/**
 * Universal controller for all modules
 *
 * Works as central dispatcher:
 * Nextcloud → ModulesController → Specific module
 */
class ModulesController extends OCSController
{
    private TranslateService $translateService;
    private UserService $userService;
    private IUserSession $userSession;
    private IGroupManager $groupManager;

    public function __construct($appName, IRequest $request)
    {
        parent::__construct($appName, $request);
        $this->translateService = TranslateService::getInstance();
        $this->userService = UserService::getInstance();
        $this->userSession = Server::get(IUserSession::class);
        $this->groupManager = Server::get(IGroupManager::class);
    }

    /**
     * @return array{0: array<int>, 1: bool} [globalRoleIds, isNcAdmin]
     */
    private function currentUserRolesAndAdmin(): array
    {
        $userObj = $this->userSession->getUser();

        if (!$userObj instanceof IUser) {
            return [[], false];
        }

        $isNcAdmin = $this->groupManager->isAdmin($userObj->getUID());
        $userId = $this->userService->getCurrentUserId();
        $roles = $userId ? $this->userService->getUserGlobalRoles($userId) : [];

        return [$roles, $isNcAdmin];
    }

    /**
     * Universal method for handling all module requests
     *
     * @param string   $module  Module name
     * @param string   $method  Method name in module
     * @param IRequest $request Request
     *
     * @return JSONResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function handleModuleRequest(string $module, string $method, IRequest $request): JSONResponse
    {
        try {
            // Resolve the user roles (a DB lookup) only when the module could
            // actually be demo-active, so the common installed-module path stays cheap.
            $simulated = DemoModuleService::simulatedDemoModules($request);

            if (DemoModuleService::isDemoActive($module, $simulated)) {
                [$roles, $isNcAdmin] = $this->currentUserRolesAndAdmin();

                if (DemoModuleService::isDemoVisibleForUser($roles, $isNcAdmin)) {
                    $provider = DemoModuleService::getProvider($module);

                    if ($provider !== null) {
                        return new JSONResponse($provider->handle($method, $request), Http::STATUS_OK);
                    }
                }
            }

            if (isset(BaseModuleService::MODULES[$module]) && !class_exists(BaseModuleService::MODULES[$module])) {
                return new JSONResponse([
                    'error'           => $this->translateService->getTranslate('Module not found'),
                    'needPaidVersion' => true,
                ], Http::STATUS_NOT_FOUND);
            }

            if (!isset(BaseModuleService::MODULES[$module]) || !class_exists(BaseModuleService::MODULES[$module])) {
                return new JSONResponse([
                    'error'           => $this->translateService->getTranslate('Module not found'),
                    'needPaidVersion' => false,
                ], Http::STATUS_NOT_FOUND);
            }

            $controllerClass = BaseModuleService::MODULES[$module];

            $moduleController = new $controllerClass(
                $this->appName,
                $request,
            );

            if (!method_exists($moduleController, $method)) {
                return new JSONResponse([
                    'error'  => $this->translateService->getTranslate('Method not found in module'),
                    'module' => $module,
                    'method' => $method,
                ], Http::STATUS_NOT_FOUND);
            }

            $moduleController->logModuleAction($method, $request->getParams());

            // Additional access check through checkModuleAccess (for backward compatibility)
            if (!$moduleController->checkModuleAccess()) {
                return new JSONResponse([
                    'error'   => $this->translateService->getTranslate('Access denied'),
                    'message' => $this->translateService->getTranslate('Access to module denied'),
                ], Http::STATUS_FORBIDDEN);
            }

            // Check access based on RequireRole attribute
            if (!$moduleController->checkMethodAccess($controllerClass, $method)) {
                return new JSONResponse([
                    'error'   => $this->translateService->getTranslate('Access denied'),
                    'message' => $this->translateService->getTranslate('Insufficient permissions to perform the operation'),
                    'module'  => $module,
                    'method'  => $method,
                ], Http::STATUS_FORBIDDEN);
            }

            return $moduleController->{$method}($request);
        } catch (\Exception $e) {
            return new JSONResponse([
                'error'   => $this->translateService->getTranslate('Error executing module request'),
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'module'  => $module,
                'method'  => $method,
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Universal handler for any module
     *
     * @param string   $module  Module name
     * @param IRequest $request
     *
     * @return JSONResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function module(string $module, IRequest $request): JSONResponse
    {
        $method = $request->getParam('method', 'index');

        return $this->handleModuleRequest($module, $method, $request);
    }

    /**
     * Get available modules
     *
     * @return JSONResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getAvailableModules(): JSONResponse
    {
        $modules = [];

        foreach (BaseModuleService::MODULES as $module => $controller) {
            if (class_exists(BaseModuleService::MODULES[$module])) {
                $modules[] = $module;
            }
        }

        $demo = [];
        $demoHidden = false;
        [$roles, $isNcAdmin] = $this->currentUserRolesAndAdmin();

        if (DemoModuleService::isDemoVisibleForUser($roles, $isNcAdmin)) {
            $demo = DemoModuleService::activeDemoModules(DemoModuleService::simulatedDemoModules($this->request));
            $modules = array_values(array_unique(array_merge($modules, $demo)));
            $demoHidden = DemoModuleService::isDemoHiddenForCurrentUser();
        }

        return new JSONResponse(
            ['modules' => $modules, 'demo' => $demo, 'demoHidden' => $demoHidden, 'isAdmin' => $isNcAdmin],
            Http::STATUS_OK
        );
    }
}
