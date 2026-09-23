<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Demo;

use OCP\IRequest;

interface DemoProviderInterface
{
    /**
     * Return fake demo data for a read method, or a read-only response
     * for anything else. Never throws for unknown methods.
     *
     * @return array<mixed>
     */
    public function handle(string $method, IRequest $request): array;
}
