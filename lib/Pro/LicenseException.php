<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Pro;

class LicenseException extends \RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $serverMessage = '',
        private readonly int $httpStatus = 0,
        private readonly bool $apiResponded = true,
    ) {
        parent::__construct($serverMessage !== '' ? $serverMessage : $errorCode);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * True when the response was a genuine license-API answer (a JSON error
     * envelope). False when some other server answered (e.g. a bare 404/HTML
     * page from an undeployed host) — that means the service is not really there.
     */
    public function respondedAsApi(): bool
    {
        return $this->apiResponded;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function isRevoked(): bool
    {
        return $this->httpStatus === 401 || $this->errorCode === 'unauthorized';
    }
}
