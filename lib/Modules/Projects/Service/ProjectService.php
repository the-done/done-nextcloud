<?php

declare(strict_types=1);

namespace OCA\Done\Modules\Projects\Service;

use OCA\Done\Service\BaseService;
use OCA\Done\Service\EntitiesService;
use OCA\Done\Service\TranslateService;
use OCP\Server;

class ProjectService extends EntitiesService
{
    protected BaseService $baseService;
    protected TranslateService $translateService;
    /** @var ProjectService */
    private static ProjectService $instance;

    public function __construct(
        TranslateService $translateService,
        BaseService $baseService
    )
    {
        $this->translateService = $translateService;
        $this->baseService = $baseService;
    }

    public static function getInstance(): self {
        if (!isset(self::$instance)) {
            self::$instance = Server::get(ProjectService::class);
        }
        return self::$instance;
    }
}
