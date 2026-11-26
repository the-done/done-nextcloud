<?php

/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Service;

use OCA\Done\Modules\BaseModuleService;

/** Narrow read-only surface for authenticated Nextcloud users without a Done profile. */
final class VacationViewerAccess
{
    public const METHODS = [
        'getVacationsTableData',
        'getVacationsForGantt',
        'getVacation',
        'getEmployeesOptionsForVacations',
        'getProjectsOptionsForVacations',
        'getVacationTypes',
        'getVacationStatuses',
    ];

    public static function available(): bool
    {
        return BaseModuleService::moduleExists('vacations');
    }

    public static function isEntryPage(string $path): bool
    {
        return $path === '/apps/done' || $path === '/apps/done/';
    }

    public static function allowsPage(string $path): bool
    {
        return self::isEntryPage($path)
            || (bool)preg_match('~^/apps/done/vacations/(requests|schedule)/?$~D', $path)
            || (bool)preg_match('~^/apps/done/vacations/requests/[A-Za-z0-9_-]+/?$~D', $path);
    }

    public static function allowsMethod(string $module, string $method): bool
    {
        return $module === 'vacations' && \in_array($method, self::METHODS, true);
    }
}
