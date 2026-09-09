<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Pro;

use OCP\App\IAppManager;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Talks to the License Manager API (/api/v1): activation, heartbeat, package download.
 */
class LicenseClient
{
    private const APP = 'done';
    private const TIMEOUT = 8;
    // Activation can trigger a per-license build on the license server (the
    // release builder), which takes longer than an ordinary API call.
    private const ACTIVATE_TIMEOUT = 120;
    private const DOWNLOAD_TIMEOUT = 120;

    /**
     * Single source of truth for the License Manager URL: the API base, and
     * the default for the human-facing site link shown on the Pro screen.
     * Change this one constant to point the app at a different License Manager.
     */
    public const DEFAULT_BASE_URL = 'https://licenses.the-done.app';

    public function __construct(
        private readonly IClientService $clientService,
        private readonly IConfig $config,
        private readonly IAppManager $appManager,
        private readonly IRequest $request,
        private readonly LoggerInterface $logger,
    ) {}

    public function activate(string $key): array
    {
        $payload = [
            'key'               => $key,
            'instance_id'       => $this->instanceId(),
            'host'              => $this->host(),
            'nextcloud_version' => $this->config->getSystemValueString('version', ''),
            'app_version'       => $this->reportedVersion(),
            'php_version'       => PHP_VERSION,
        ];
        $response = $this->clientService->newClient()->post($this->baseUrl() . '/api/v1/activate', [
            'json'        => $payload,
            'timeout'     => self::ACTIVATE_TIMEOUT,
            'http_errors' => false,
        ]);

        return $this->decode($response);
    }

    public function heartbeat(string $token): array
    {
        $response = $this->clientService->newClient()->post($this->baseUrl() . '/api/v1/heartbeat', [
            'json' => [
                'app_version'       => $this->reportedVersion(),
                'nextcloud_version' => $this->config->getSystemValueString('version', ''),
            ],
            'headers'     => ['Authorization' => 'Bearer ' . $token],
            'timeout'     => self::TIMEOUT,
            'http_errors' => false,
        ]);

        return $this->decode($response);
    }

    /**
     * Download a package to a temp file, verifying its sha256.
     *
     * @return string path to the downloaded temp file
     *
     * @throws LicenseException
     */
    public function downloadPackage(string $urlPath, string $key, string $expectedSha256): string
    {
        $url = str_starts_with($urlPath, 'http') ? $urlPath : $this->baseUrl() . $urlPath;
        $tmp = tempnam(sys_get_temp_dir(), 'done-pkg-');

        if ($tmp === false) {
            throw new LicenseException('tmp_failed');
        }
        $response = $this->clientService->newClient()->get($url, [
            'headers'     => ['X-License-Key' => $key],
            'sink'        => $tmp,
            'timeout'     => self::DOWNLOAD_TIMEOUT,
            'http_errors' => false,
        ]);

        if ($response->getStatusCode() >= 400) {
            @unlink($tmp);
            throw new LicenseException('download_failed', '', $response->getStatusCode());
        }
        $actual = (string)hash_file('sha256', $tmp);

        if (!hash_equals(strtolower($expectedSha256), strtolower($actual))) {
            @unlink($tmp);
            throw new LicenseException('checksum_mismatch');
        }

        return $tmp;
    }

    private function decode(IResponse $response): array
    {
        $status = $response->getStatusCode();
        $data = json_decode((string)$response->getBody(), true);
        // A genuine API answer carries the {error:{code,message}} envelope.
        // Anything else (HTML 404 from a bare/undeployed host, gateway pages)
        // is not the license service, even if it is a valid HTTP response.
        $isEnvelope = \is_array($data) && isset($data['error']['code']);

        if ($status >= 400) {
            $code = $isEnvelope ? (string)$data['error']['code'] : 'server_error';
            $message = $isEnvelope ? (string)($data['error']['message'] ?? '') : '';
            throw new LicenseException($code, $message, $status, $isEnvelope);
        }

        if (!\is_array($data)) {
            throw new LicenseException('bad_response', '', $status, false);
        }

        return $data;
    }

    private function baseUrl(): string
    {
        return rtrim($this->config->getAppValue(self::APP, 'license.base_url', self::DEFAULT_BASE_URL), '/');
    }

    /**
     * Version reported to the license service for update comparison. This is the
     * installed PRO PACKAGE version (releases are versioned by package, e.g.
     * 1.0.0 -> 1.1.0), NOT the Nextcloud "done" app version. Falls back to the
     * app version before the pro package is installed.
     */
    private function reportedVersion(): string
    {
        $pro = $this->config->getAppValue(self::APP, 'pro.installed_version', '');

        return $pro !== '' ? $pro : $this->appManager->getAppVersion(self::APP);
    }

    private function instanceId(): string
    {
        return hash('sha256', $this->config->getSystemValueString('instanceid', ''));
    }

    private function host(): string
    {
        $overwrite = $this->config->getSystemValueString('overwrite.cli.url', '');

        return $overwrite !== '' ? $overwrite : $this->request->getServerHost();
    }
}
