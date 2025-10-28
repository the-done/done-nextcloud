<?php

declare(strict_types=1);

namespace OCA\Done\Modules;

use OCA\Done\Models\User_Model;
use OCA\Done\Service\TranslateService;
use OCA\Done\Service\UserService;
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
    protected array $allowedRoles = [];
    public string $moduleName = '';
    /**
     * @var mixed|null
     */
    protected mixed $currentUserId = null;

    public function __construct(
        $appName,
        IRequest $request,
    ) {
        parent::__construct($appName, $request);
        $this->userSession      = Server::get(IUserSession::class);
        $this->translateService = TranslateService::getInstance();
        $this->userService      = UserService::getInstance();
        $this->setCurrentUserId();
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
        $userRoles    = $this->userService->getUserGlobalRoles($currentUserId);

        if (empty(array_intersect($allowedRoles, $userRoles))) {
            return false;
        }

        return true;
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
            $currentUserUid        = $currentUserObj->getUID();
            $currentUser           = (new User_Model())->getUserByUuid($currentUserUid);
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
     * Get current user ID
     */
    public function getCurrentUserId(): ?string
    {
        return $this->currentUserId;
    }

    /**
     * Common method for formatting module response
     */
    protected function formatModuleResponse($data, int $status = 200): JSONResponse
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
        ], 500);
    }

    /**
     * Get module name
     */
    protected function getModuleName(): string
    {
        return $this->moduleName;
    }
}
