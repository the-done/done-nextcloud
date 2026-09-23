<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\BackgroundJob;

use OCA\Done\Pro\ProLicenseService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

/**
 * Daily license heartbeat. Failures are swallowed (soft degradation);
 * a revoked activation is handled inside ProLicenseService::refresh().
 */
class ProHeartbeatJob extends TimedJob
{
    public function __construct(
        ITimeFactory $time,
        private readonly ProLicenseService $proService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($time);
        $this->setInterval(24 * 60 * 60);
    }

    protected function run($argument): void
    {
        try {
            $this->proService->refresh();
        } catch (\Throwable $e) {
            $this->logger->warning('pro heartbeat job failed', ['exception' => $e]);
        }
    }
}
