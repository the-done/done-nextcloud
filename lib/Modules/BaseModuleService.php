<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules;

use OCA\Done\Modules\Agreement\Controller\AgreementController;
use OCA\Done\Modules\DoneAI\Controller\DoneAIController;
use OCA\Done\Modules\Finances\Controller\FinancesController;
use OCA\Done\Modules\IntegrationWithExtApps\Controller\IntegrationWithExtAppsController;
use OCA\Done\Modules\Projects\Controller\ProjectsController;
use OCA\Done\Modules\Reports\Controller\ReportsController;
use OCA\Done\Modules\Teams\Controller\TeamsController;
use OCA\Done\Modules\Vacations\Controller\VacationsController;

class BaseModuleService
{
    /** Map of modules and their controllers */
    public const MODULES = [
        'agreement'              => AgreementController::class,
        'reports'                => ReportsController::class,
        'teams'                  => TeamsController::class,
        'projects'               => ProjectsController::class,
        'finances'               => FinancesController::class,
        'doneai'                 => DoneAIController::class,
        'vacations'              => VacationsController::class,
        'integrationwithextapps' => IntegrationWithExtAppsController::class,
    ];

    public static function moduleExists(string $module = ''): bool
    {
        return class_exists(self::MODULES[$module]);
    }
}
