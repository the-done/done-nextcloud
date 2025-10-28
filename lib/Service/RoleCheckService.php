<?php

declare(strict_types=1);

namespace OCA\Done\Service;

use OCA\Done\Attribute\RequireRole;
use ReflectionClass;

/**
 * Service for checking user roles
 */
class RoleCheckService
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = UserService::getInstance();
    }

    /**
     * Check if user has access to method based on RequireRole attribute
     */
    public function checkMethodAccess(string $controllerClass, string $methodName): bool
    {
        try {
            $reflectionClass  = new ReflectionClass($controllerClass);
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

            // If attribute not found, allow access
            return true;
        } catch (\ReflectionException $e) {
            // In case of reflection error, deny access
            return false;
        }
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

        return !empty(array_intersect($requiredRoles, $userRoles));
    }
}
