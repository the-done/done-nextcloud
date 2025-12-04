<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Controller;

use OCA\Done\Attribute\RequireRole;
use OCA\Done\Modules\BaseModuleService;
use OCA\Done\Modules\Finances\Controller\FinancesController;
use OCA\Done\Modules\Projects\Controller\ProjectsController;
use OCA\Done\Modules\Teams\Controller\TeamsController;
use OCA\Done\Service\RoleCheckService;
use OCA\Done\Service\TranslateService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * Universal controller for all modules
 *
 * Works as central dispatcher:
 * Nextcloud → ModulesController → Specific module
 *
 * Supported modules:
 * - reports: ReportsController
 * - reports: TeamsController
 * - reports: ProjectsController
 * - reports: FinancesController
 */
class ModulesController extends OCSController
{
    private RoleCheckService $roleCheckService;
    private TranslateService $translateService;

    public function __construct($appName, IRequest $request)
    {
        parent::__construct($appName, $request);
        $this->roleCheckService = new RoleCheckService();
        $this->translateService = TranslateService::getInstance();
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

            // Check access based on RequireRole attribute
            if (!$this->roleCheckService->checkMethodAccess($controllerClass, $method)) {
                return new JSONResponse([
                    'error'   => $this->translateService->getTranslate('Access denied'),
                    'message' => $this->translateService->getTranslate('Insufficient permissions to perform the operation'),
                    'module'  => $module,
                    'method'  => $method,
                ], Http::STATUS_FORBIDDEN);
            }

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
        $availableModules = [];

        foreach (BaseModuleService::MODULES as $module => $controller) {
            if (class_exists(BaseModuleService::MODULES[$module])) {
                $availableModules[] = $module;
            }
        }

        return new JSONResponse(
            $availableModules,
            Http::STATUS_OK
        );
    }
}
