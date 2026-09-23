<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Pro;

use OCA\Done\Modules\BaseModuleService;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Orchestrates activation, package installation, heartbeat and updates.
 * Persists license state in appconfig; never exposes key/token to callers.
 */
class ProLicenseService
{
    private const APP = 'done';

    /** Heartbeat is considered fresh for this many seconds. */
    private const HEARTBEAT_TTL = 3600;

    private ProModuleInstaller $installer;

    public function __construct(
        private readonly IConfig $config,
        private readonly LicenseClient $client,
        private readonly LoggerInterface $logger,
        ?ProModuleInstaller $installer = null,
    ) {
        // Staging dir MUST be on the same filesystem as lib/Modules, otherwise
        // the installer's rename(staging -> target) fails with EXDEV (e.g. when
        // sys_get_temp_dir() is a tmpfs mount). dirname(__DIR__) == lib/, the
        // parent of lib/Modules, so staging under it is always same-filesystem.
        $this->installer = $installer ?? new ProModuleInstaller(
            \dirname(__DIR__) . '/Modules',
            \dirname(__DIR__),
        );
    }

    /**
     * Validate and activate the license on the server. This does NOT install
     * any modules: activation and installation are deliberately separate steps
     * so a failed download never leaves a false "activated" state, and so the
     * admin can (re)install on demand. The package the server offers is stored
     * for the later install() call.
     *
     * @param string $key the license key, or '' to re-activate with the stored key
     */
    public function activate(string $key = ''): array
    {
        if ($key === '') {
            $key = $this->config->getAppValue(self::APP, 'license.key', '');
        }

        if ($key === '') {
            throw new LicenseException('key_required');
        }
        $this->config->setAppValue(self::APP, 'license.key', $key);
        $result = $this->client->activate($key);

        $license = \is_array($result['license'] ?? null) ? $result['license'] : [];
        $this->config->setAppValue(self::APP, 'license.activation_token', (string)($result['activation_token'] ?? ''));
        $this->config->setAppValue(self::APP, 'license.updates_until', (string)($license['updates_until'] ?? ''));
        $this->config->setAppValue(self::APP, 'license.status', (string)($license['status'] ?? ''));
        $this->config->setAppValue(self::APP, 'license.plan', (string)($license['plan'] ?? ''));
        $this->config->setAppValue(self::APP, 'license.activated_at', gmdate('c'));
        $this->config->setAppValue(self::APP, 'license.last_heartbeat_at', gmdate('c'));
        $this->config->setAppValue(self::APP, 'license.reachable', '1');

        // Remember the package the server offered so a later, separate install
        // step can fetch it without re-activating.
        $package = $result['package'] ?? null;

        if (\is_array($package) && !empty($package['url']) && !empty($package['sha256'])) {
            $this->config->setAppValue(self::APP, 'pro.available_package', (string)json_encode([
                'url'     => (string)$package['url'],
                'sha256'  => (string)$package['sha256'],
                'version' => (string)($package['version'] ?? ''),
            ]));
        } else {
            $this->config->deleteAppValue(self::APP, 'pro.available_package');
        }

        return $this->status();
    }

    /**
     * Download and install the licensed package remembered at activation time.
     * Idempotent unless $force: if the same version is already installed and its
     * modules are present, it is a no-op.
     *
     * @throws LicenseException not_activated|no_package and install/download codes
     */
    public function install(bool $force = false): array
    {
        if ($this->config->getAppValue(self::APP, 'license.activation_token', '') === '') {
            throw new LicenseException('not_activated');
        }
        $raw = $this->config->getAppValue(self::APP, 'pro.available_package', '');
        $package = $raw !== '' ? json_decode($raw, true) : null;

        if (!\is_array($package) || empty($package['url']) || empty($package['sha256'])) {
            throw new LicenseException('no_package');
        }
        $version = (string)($package['version'] ?? '');

        if (!$force
            && $version !== ''
            && $this->config->getAppValue(self::APP, 'pro.installed_version', '') === $version
            && $this->installedModules() !== []) {
            return $this->status();
        }
        $this->downloadAndInstall((string)$package['url'], (string)$package['sha256']);
        $this->config->setAppValue(self::APP, 'pro.installed_version', $version);
        $this->config->deleteAppValue(self::APP, 'pro.update');

        return $this->status();
    }

    /**
     * Backwards-compatible one-shot: activate then install. Prefer the separate
     * activate()/install() steps; kept so existing callers keep working.
     */
    public function activateAndInstall(string $key): array
    {
        $this->activate($key);

        return $this->install(true);
    }

    public function refresh(bool $force = true): array
    {
        $token = $this->config->getAppValue(self::APP, 'license.activation_token', '');

        if ($token === '') {
            return $this->status();
        }

        // Only-if-stale callers (the status endpoint) must not force a synchronous
        // heartbeat when a recent one already succeeded.
        if (!$force && !$this->heartbeatStale()) {
            return $this->status();
        }

        try {
            $result = $this->client->heartbeat($token);
        } catch (LicenseException $e) {
            // Reachable only if the license API itself answered (proper JSON
            // envelope) - a revoked/rate-limited/server-error response still means
            // the service is there. A bare HTTP response from an undeployed host
            // (e.g. a 404 HTML page) is NOT the service, so it counts as
            // unreachable. Product decision: nothing degrades either way.
            $this->config->setAppValue(self::APP, 'license.reachable', $e->respondedAsApi() ? '1' : '0');
            $this->logger->warning('pro heartbeat rejected: ' . $e->getErrorCode(), ['exception' => $e]);

            return $this->status();
        } catch (\Throwable $e) {
            // Transport/connection failure: the service could not be reached. Never
            // clears anything, degrade softly; the UI shows an unavailability notice.
            $this->logger->warning('pro heartbeat transport failure', ['exception' => $e]);
            $this->config->setAppValue(self::APP, 'license.reachable', '0');

            return $this->status();
        }
        $this->config->setAppValue(self::APP, 'license.reachable', '1');
        $this->config->setAppValue(self::APP, 'license.updates_until', (string)($result['updates_until'] ?? ''));
        $this->config->setAppValue(self::APP, 'license.last_heartbeat_at', gmdate('c'));

        $update = $result['update'] ?? null;

        if (\is_array($update) && ($update['available'] ?? false)) {
            $this->config->setAppValue(self::APP, 'pro.update', (string)json_encode($update));
        } else {
            $this->config->deleteAppValue(self::APP, 'pro.update');
        }

        return $this->status();
    }

    /**
     * True when the last heartbeat is missing, unparseable, or older than the TTL.
     */
    private function heartbeatStale(): bool
    {
        $last = $this->config->getAppValue(self::APP, 'license.last_heartbeat_at', '');

        if ($last === '') {
            return true;
        }
        $ts = strtotime($last);

        if ($ts === false) {
            return true;
        }

        return (time() - $ts) >= self::HEARTBEAT_TTL;
    }

    public function installUpdate(): array
    {
        $raw = $this->config->getAppValue(self::APP, 'pro.update', '');
        $update = $raw !== '' ? json_decode($raw, true) : null;

        if (!\is_array($update) || empty($update['auto_url'])) {
            throw new LicenseException('no_update');
        }
        $key = $this->config->getAppValue(self::APP, 'license.key', '');
        $this->downloadAndInstall((string)$update['auto_url'], (string)($update['sha256']['auto'] ?? ''), $key);
        $this->config->setAppValue(self::APP, 'pro.installed_version', (string)($update['version'] ?? ''));
        $this->config->deleteAppValue(self::APP, 'pro.update');

        try {
            $this->refresh();
        } catch (\Throwable $e) {
            $this->logger->warning('pro heartbeat after update failed', ['exception' => $e]);
        }

        return $this->status();
    }

    public function status(): array
    {
        $installedModules = $this->installedModules();
        $activated = $this->config->getAppValue(self::APP, 'license.activation_token', '') !== '';
        $availableRaw = $this->config->getAppValue(self::APP, 'pro.available_package', '');
        $available = $availableRaw !== '' ? json_decode($availableRaw, true) : null;

        $updateRaw = $this->config->getAppValue(self::APP, 'pro.update', '');
        $update = $updateRaw !== '' ? json_decode($updateRaw, true) : null;

        return [
            'activated'         => $activated,
            'license_status'    => $this->config->getAppValue(self::APP, 'license.status', ''),
            'plan'              => $this->config->getAppValue(self::APP, 'license.plan', ''),
            'key_masked'        => self::maskKey($this->config->getAppValue(self::APP, 'license.key', '')),
            'activated_at'      => $this->config->getAppValue(self::APP, 'license.activated_at', ''),
            'installed_version' => $this->config->getAppValue(self::APP, 'pro.installed_version', ''),
            'installed_modules' => $installedModules,
            // True only when the license is active AND at least one paid module is
            // physically present. Lets the UI warn on "active but not installed".
            'modules_installed' => $activated && $installedModules !== [],
            // A package is available to (re)install without re-activating.
            'can_install' => $activated && \is_array($available)
                && !empty($available['url']) && !empty($available['sha256']),
            'available_version' => \is_array($available) ? (string)($available['version'] ?? '') : '',
            'updates_until'     => $this->config->getAppValue(self::APP, 'license.updates_until', ''),
            'last_heartbeat_at' => $this->config->getAppValue(self::APP, 'license.last_heartbeat_at', ''),
            // Whether the last contact attempt reached the license service. Optimistic
            // default so a never-contacted instance does not show a false alarm.
            'reachable' => $this->config->getAppValue(self::APP, 'license.reachable', '1') === '1',
            // Human-facing site to link to. Kept separate from the API base_url
            // (which may be overridden to an internal endpoint in dev/test).
            'service_url' => $this->config->getAppValue(self::APP, 'license.site_url', LicenseClient::DEFAULT_BASE_URL),
            'update'      => \is_array($update) ? [
                'available' => true,
                'version'   => (string)($update['version'] ?? ''),
                'changelog' => $update['changelog'] ?? '',
            ] : null,
        ];
    }

    /**
     * Lowercased names of the paid modules physically present on disk.
     *
     * @return list<string>
     */
    private function installedModules(): array
    {
        $installed = [];

        foreach (ProModuleInstaller::ALLOWED_MODULES as $module) {
            if (BaseModuleService::moduleExists(strtolower($module))) {
                $installed[] = strtolower($module);
            }
        }

        return $installed;
    }

    /**
     * Show only the last 4 characters of the key; never expose the whole key.
     */
    private static function maskKey(string $key): string
    {
        if ($key === '') {
            return '';
        }

        return '****' . substr($key, -4);
    }

    private function downloadAndInstall(string $url, string $sha256, string $key = ''): void
    {
        if ($key === '') {
            $key = $this->config->getAppValue(self::APP, 'license.key', '');
        }
        $file = $this->client->downloadPackage($url, $key, $sha256);

        try {
            $this->installer->install($file);
        } catch (\RuntimeException $e) {
            // Normalize installer error codes into LicenseException for the controller.
            throw new LicenseException($e->getMessage());
        } finally {
            @unlink($file);
        }
    }
}
