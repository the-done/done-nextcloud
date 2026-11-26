<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\AppInfo;

use OCA\Done\Middleware\PermissionMiddleware;
use OCA\Done\Notification\Notifier;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IL10N;
use OCP\L10N\IFactory as L10NFactory;

class Application extends App implements IBootstrap
{
    public const APP_ID = 'done';

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
    ) {
        parent::__construct(self::APP_ID);

        $container = $this->getContainer();
        $container->registerService(IL10N::class, static function ($c) {
            /** @var L10NFactory $l10nFactory */
            $l10nFactory = $c->query(L10NFactory::class);

            return $l10nFactory->get(self::APP_ID);
        });
    }

    public function register(IRegistrationContext $context): void
    {
        $context->registerMiddleware(PermissionMiddleware::class);
        $context->registerNotifierService(Notifier::class);
    }

    public function boot(IBootContext $context): void {}
}
