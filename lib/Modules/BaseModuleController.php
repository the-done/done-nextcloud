<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Modules;

use OCA\Done\Attribute\RequireRole;
use OCA\Done\Models\UserModel;
use OCA\Done\Service\TranslateService;
use OCA\Done\Service\UserService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Server;

/**
 * Base controller for all modules
 * Contains common logic and methods needed by all modules
 */
abstract class BaseModuleController extends OCSController
{
    protected TranslateService $translateService;
    protected UserService $userService;
    protected IUserSession $userSession;
    protected IAppManager $appManager;
    protected array $allowedRoles = [];
    protected bool $globalAccessPermission = false;
    protected bool $useGlobalAccessPermission = false;
    public string $moduleName = '';

    /** @var null|mixed */
    protected mixed $currentUserId = null;

    public function __construct(
        $appName,
        IRequest $request,
    ) {
        parent::__construct($appName, $request);
        $this->userSession = Server::get(IUserSession::class);
        $this->translateService = TranslateService::getInstance();
        $this->userService = UserService::getInstance();
        $this->appManager = Server::get(IAppManager::class);
        $this->setCurrentUserId();
    }

    /**
     * Check if user has access to method based on RequireRole attribute
     */
    public function checkMethodAccess(string $controllerClass, string $methodName): bool
    {
        try {
            $reflectionClass = new \ReflectionClass($controllerClass);
            $reflectionMethod = $reflectionClass->getMethod($methodName);

            // Check attribute at method level
            $methodAttributes = $reflectionMethod->getAttributes(RequireRole::class);

            if (!empty($methodAttributes)) {
                $requireRole = $methodAttributes[0]->newInstance();

                return $this->checkUserRoles($requireRole->getRequiredRoles());
            }

            // Check attribute at class level
            $classAttributes = $reflectionClass->getAttributes(RequireRole::class);

            if (!empty($classAttributes)) {
                $requireRole = $classAttributes[0]->newInstance();

                return $this->checkUserRoles($requireRole->getRequiredRoles());
            }

            if ($this->getUseGlobalAccessPermission()) {
                return $this->getGlobalAccessPermission();
            }

            // If attribute not found, allow access
            return true;
        } catch (\ReflectionException $e) {
            // In case of reflection error, deny access
            return false;
        }
    }

    /**
     * Common method for checking module access permissions
     */
    public function checkModuleAccess(): bool
    {
        $currentUserId = $this->getCurrentUserId();

        if (empty($currentUserId)) {
            return false;
        }

        $allowedRoles = $this->getAllowedRoles();
        $globalAccessPermission = $this->getGlobalAccessPermission();
        $useGlobalAccessPermission = $this->getUseGlobalAccessPermission();
        $userRoles = $this->userService->getUserGlobalRoles($currentUserId);

        if (
            !empty(array_intersect($allowedRoles, $userRoles))
            || \in_array('ALL', $allowedRoles)
            || ($globalAccessPermission && $useGlobalAccessPermission)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if user has specified roles
     */
    private function checkUserRoles(array $requiredRoles = []): bool
    {
        $userId = $this->userService->getCurrentUserId();

        if (empty($userId)) {
            return false;
        }

        $userRoles = $this->userService->getUserGlobalRoles($userId);

        return !empty(array_intersect($requiredRoles, $userRoles)) || \in_array('ALL', $requiredRoles);
    }

    /**
     * Common method for logging module actions
     */
    public function logModuleAction(string $action, array $data = []): void
    {
        // Common logic for logging actions in modules
        // Can be connected to Nextcloud logging system
    }

    /**
     * Set current user ID
     */
    public function setCurrentUserId(): void
    {
        $currentUserObj = $this->userSession->getUser();

        if ($currentUserObj instanceof IUser) {
            $currentUserUid = $currentUserObj->getUID();
            $currentUser = (new UserModel())->getUserByUuid($currentUserUid);
            $this->currentUserId = $currentUser['id'] ?? null;
        }
    }

    /**
     * Set allowed roles for module
     */
    public function setAllowedRoles(array $roles = []): void
    {
        $this->allowedRoles = $roles;
    }

    /**
     * Get allowed roles for module
     */
    public function getAllowedRoles(): array
    {
        return $this->allowedRoles;
    }

    /**
     * Set global access permission
     */
    public function setGlobalAccessPermission(bool $can = false): void
    {
        $this->globalAccessPermission = $can;
    }

    /**
     * Set "use" param for global access permission
     */
    public function setUseGlobalAccessPermission(bool $use = false): void
    {
        $this->useGlobalAccessPermission = $use;
    }

    /**
     * Get global access permission
     */
    public function getGlobalAccessPermission(): bool
    {
        return $this->globalAccessPermission;
    }

    /**
     * Get "use" param for global access permission
     */
    public function getUseGlobalAccessPermission(): bool
    {
        return $this->useGlobalAccessPermission;
    }

    /**
     * Get current user ID
     */
    public function getCurrentUserId(): ?string
    {
        return $this->currentUserId;
    }

    /**
     * Common method for formatting module response
     *
     * @param mixed          $data
     * @param Http::STATUS_* $status
     *
     * @return JSONResponse
     */
    protected function formatModuleResponse(mixed $data, int $status = Http::STATUS_OK): JSONResponse
    {
        //        $response = [
        //            'success'   => $status < 400,
        //            'data'      => $data,
        //            'timestamp' => time(),
        //            'module'    => $this->getModuleName(),
        //        ];

        return new JSONResponse($data, $status);
    }

    /**
     * Common method for handling module errors
     */
    protected function handleModuleError(\Exception $e): JSONResponse
    {
        $this->logModuleAction('error', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);

        return $this->formatModuleResponse([
            'error' => $e->getMessage(),
            'code'  => $e->getCode(),
        ], Http::STATUS_INTERNAL_SERVER_ERROR);
    }

    /**
     * Get module name
     */
    protected function getModuleName(): string
    {
        return $this->moduleName;
    }
}
