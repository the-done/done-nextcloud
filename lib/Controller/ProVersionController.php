<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Controller;

use OCA\Done\Pro\LicenseException;
use OCA\Done\Pro\ProLicenseService;
use OCA\Done\Service\TranslateService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Pro version screen: activation and updates. Admin-only (no NoAdminRequired attribute).
 */
class ProVersionController extends Controller
{
    private TranslateService $translateService;

    public function __construct(
        string $appName,
        IRequest $request,
        private readonly ProLicenseService $proService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($appName, $request);
        $this->translateService = TranslateService::getInstance();
    }

    public function status(): JSONResponse
    {
        // Force a live heartbeat when the Pro screen opens so the reachability
        // indicator reflects the current state (soft-degrades if unreachable).
        return $this->handle(fn () => $this->proService->refresh(true));
    }

    /**
     * Activate (or re-activate) the license on the server. Does NOT install
     * modules — that is a separate step. An empty key re-activates with the
     * stored key.
     */
    public function activate(IRequest $request): JSONResponse
    {
        $key = trim((string)$request->getParam('key', ''));

        return $this->handle(fn () => $this->proService->activate($key));
    }

    /**
     * Install or reinstall the licensed package. `force` reinstalls in place.
     */
    public function install(IRequest $request): JSONResponse
    {
        $force = filter_var($request->getParam('force', false), FILTER_VALIDATE_BOOLEAN);

        return $this->handle(fn () => $this->proService->install($force));
    }

    public function update(): JSONResponse
    {
        return $this->handle(fn () => $this->proService->installUpdate());
    }

    private function handle(callable $fn): JSONResponse
    {
        try {
            return new JSONResponse($fn(), Http::STATUS_OK);
        } catch (LicenseException $e) {
            $this->logger->warning('pro license error: ' . $e->getErrorCode(), ['exception' => $e]);
            $status = $e->getHttpStatus() >= 400 ? $e->getHttpStatus() : Http::STATUS_BAD_REQUEST;

            return new JSONResponse(
                ['message' => $this->messageFor($e), 'code' => $e->getErrorCode()],
                $status
            );
        } catch (\Throwable $e) {
            $this->logger->error('pro install failed', ['exception' => $e]);

            return new JSONResponse(
                ['message' => $this->translateService->getTranslate('Installation failed')],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function messageFor(LicenseException $e): string
    {
        $map = [
            'key_required'             => 'License key is required',
            'not_activated'            => 'Activate the license before installing the modules',
            'not_found'                => 'License key not found or revoked',
            'already_activated'        => 'This key is already activated on another host. Contact support to transfer it.',
            'trial_used'               => 'Trial already used on this host. A yearly plan is available.',
            'checksum_mismatch'        => 'Downloaded package failed the integrity check',
            'download_failed'          => 'Could not download the package',
            'no_package'               => 'No package is available for this license',
            'no_update'                => 'No update is available',
            'modules_dir_not_writable' => 'Cannot write to the modules directory',
            'package_unsafe_entry'     => 'The package contains an unsafe entry',
            'package_symlink'          => 'The package contains a symlink',
            'package_unknown_module'   => 'The package contains an unexpected module',
        ];
        $code = $e->getErrorCode();

        if (isset($map[$code])) {
            return $this->translateService->getTranslate($map[$code]);
        }
        $server = $e->getMessage();

        return $server !== '' && $server !== $code
            ? $server
            : $this->translateService->getTranslate('License request failed');
    }
}
