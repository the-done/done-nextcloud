<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Demo;

use OCA\Done\Service\TranslateService;
use OCP\IRequest;

abstract class AbstractDemoProvider implements DemoProviderInterface
{
    /**
     * Map of allowed read method name => handler(IRequest): array.
     *
     * @return array<string, callable>
     */
    abstract protected function readMethods(): array;

    /**
     * Translate a UI label with the user's locale, exactly as the real
     * TableService does for column titles, so demo table headers are localized
     * (the demo DATA stays English; only chrome like column titles is translated).
     */
    protected function tr(string $key): string
    {
        try {
            return TranslateService::getInstance()->getTranslate($key);
        } catch (\Throwable $e) {
            // No DI container (e.g. unit tests) - fall back to the English key.
            return $key;
        }
    }

    public function handle(string $method, IRequest $request): array
    {
        $handlers = $this->readMethods();

        if (isset($handlers[$method])) {
            return ($handlers[$method])($request);
        }

        return [
            'demo'     => true,
            'readOnly' => true,
            'message'  => 'This is demo data. Saving and editing are available in the full version.',
        ];
    }
}
