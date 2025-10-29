<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


namespace OCA\Done\Modules;

use OCA\Done\Modules\Finances\Controller\FinancesController;
use OCA\Done\Modules\Projects\Controller\ProjectsController;
use OCA\Done\Modules\Reports\Controller\ReportsController;
use OCA\Done\Modules\Teams\Controller\TeamsController;

class BaseModuleService
{
    /**
     * Map of modules and their controllers
     */
    public const MODULES = [
        'reports'  => ReportsController::class,
        'teams'    => TeamsController::class,
        'projects' => ProjectsController::class,
        'finances' => FinancesController::class,
    ];

    public static function moduleExists(string $module = ''): bool
    {
        return class_exists(BaseModuleService::MODULES[$module]) ?? false;
    }
}