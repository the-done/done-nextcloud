<?php

declare(strict_types=1);

namespace OCA\Done\AppInfo;

use OC\DB\Connection;
use OC\DB\MigrationService;
use OCA\Done\Middleware\PermissionMiddleware;
use OCA\Done\Service\FileService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IConfig;
use OCP\L10N\IFactory as L10NFactory;
use OCP\IL10N;
use OCP\Files\IAppData;
use OCP\Server;

class Application extends App implements IBootstrap {
	public const APP_ID = 'done';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
    ) {
		parent::__construct(self::APP_ID);

		$container = $this->getContainer();
		$container->registerService(IL10N::class, function($c) {
			/** @var L10NFactory $l10nFactory */
			$l10nFactory = $c->query(L10NFactory::class);
			return $l10nFactory->get(self::APP_ID);
		});

        if ($this->isFirstEnable()) {
			$this->runPreMigrationTasks();
		}
	}

	public function register(IRegistrationContext $context): void {
        $context->registerMiddleware(PermissionMiddleware::class);
	}

	private function isFirstEnable(): bool
	{
		$config = Server::get(IConfig::class);
		return $config->getAppValue(self::APP_ID, 'installed_version', '') === '';
	}

    private function runPreMigrationTasks(): void
	{
		$ms = new MigrationService(self::APP_ID, \OCP\Server::get(Connection::class));
		$ms->migrate('latest', false);
	}

	public function boot(IBootContext $context): void {}
}
