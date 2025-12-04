<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Controller;

use OC\Group\Manager;
use OCA\Done\Modules\Projects\Service\ProjectService;
use OCA\Done\Service\AppearanceService;
use OCA\Done\Service\BaseService;
use OCA\Done\Service\EntitiesService;
use OCA\Done\Service\ExcelExportService;
use OCA\Done\Service\FieldCommentsService;
use OCA\Done\Service\FileService;
use OCA\Done\Service\TableService;
use OCA\Done\Service\TranslateService;
use OCA\Done\Service\UserService;
use OCP\App\IAppManager;
use OCP\AppFramework\OCSController;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;

abstract class BaseController extends OCSController
{
    protected IUserSession $userSession;
    protected BaseService $baseService;
    protected TableService $tableService;
    protected UserService $userService;
    protected IAppManager $appManager;
    protected ProjectService $projectService;
    protected EntitiesService $entitiesService;
    protected IL10N $l10n;
    protected TranslateService $translateService;
    protected FileService $fileService;
    protected AppearanceService $appearanceService;
    protected ExcelExportService $excelExportService;
    protected IGroupManager | Manager $groupManager;
    protected IManager $notificationManager;
    protected IURLGenerator $url;
    protected IFactory $l10nFactory;
    protected IConfig $config;
    protected FieldCommentsService $fieldCommentsService;

    public function __construct(
        $appName,
        IRequest $request,
        IUserSession $userSession,
        IAppManager $appManager,
        IGroupManager $groupManager,
        IManager $notificationManager,
        IURLGenerator $urlGenerator,
        IConfig $config,
        IFactory $l10nFactory,
    ) {
        parent::__construct($appName, $request);
        $this->userSession = $userSession;
        $this->appManager = $appManager;
        $this->groupManager = $groupManager;
        $this->notificationManager = $notificationManager;
        $this->url = $urlGenerator;
        $this->config = $config;
        $this->l10nFactory = $l10nFactory;
        $this->baseService = BaseService::getInstance();
        $this->tableService = TableService::getInstance();
        $this->userService = UserService::getInstance();
        $this->projectService = ProjectService::getInstance();
        $this->fileService = FileService::getInstance();
        $this->appearanceService = AppearanceService::getInstance();
        $this->excelExportService = ExcelExportService::getInstance();
        $this->translateService = TranslateService::getInstance();
        $this->entitiesService = EntitiesService::getInstance();
        $this->fieldCommentsService = FieldCommentsService::getInstance();
    }
}
