<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Demo;

use OCA\Done\Demo\Providers\AgreementDemoProvider;
use OCA\Done\Demo\Providers\FinancesDemoProvider;
use OCA\Done\Demo\Providers\TeamsDemoProvider;
use OCA\Done\Demo\Providers\VacationsDemoProvider;
use OCA\Done\Models\CustomSettingsDataModel;
use OCA\Done\Models\CustomSettingsModel;
use OCA\Done\Models\Dictionaries\GlobalRolesModel;
use OCA\Done\Models\PermissionsEntitiesModel;
use OCA\Done\Modules\BaseModuleService;
use OCA\Done\Service\TranslateService;
use OCA\Done\Service\UserService;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Server;

/**
 * Registry for demo mode: which absent modules get fake read-only data,
 * who may see them, and which permissions to inject so they render.
 *
 * MUST NOT reference any stripped per-module class from OCA\Done\Modules\<Module>\*;
 * BaseModuleService (the always-shipped dispatcher base) is the one intentional exception.
 */
class DemoModuleService
{
    /** Modules that get demo data when their real backend is absent. */
    public const DEMO_MODULES = ['vacations', 'agreement', 'finances', 'teams'];

    /** Provider class per demo module. */
    private const PROVIDERS = [
        'vacations' => VacationsDemoProvider::class,
        'agreement' => AgreementDemoProvider::class,
        'finances'  => FinancesDemoProvider::class,
        'teams'     => TeamsDemoProvider::class,
    ];

    /**
     * Dictionary title -> demo module. Core dictionary endpoints
     * (DictionariesController::getDictionaryData) resolve their data outside
     * the module dispatcher, so titles backed by a demo-able module are listed
     * here to get intercepted before the real lookup.
     */
    private const DICTIONARY_MODULE = [
        'rolesInTeamDictionary' => 'teams',
    ];

    /**
     * Entity source int -> demo module. Only entity sources whose backing
     * module can run in demo appear here; CORE sources (user, project) are
     * intentionally absent so their cards never get faked.
     */
    private const SOURCE_MODULE = [
        PermissionsEntitiesModel::TEAM_ENTITY                           => 'teams',
        PermissionsEntitiesModel::PAYMENTS_ENTITY                       => 'finances',
        PermissionsEntitiesModel::FYN_CONTRACTS_ENTITY                  => 'finances',
        PermissionsEntitiesModel::FYN_CONTRACTS_PARAMETERS_ENTITY       => 'finances',
        PermissionsEntitiesModel::FYN_CONTRACTS_PARAMETER_GROUPS_ENTITY => 'finances',
        PermissionsEntitiesModel::VACATION_REPORT_ENTITY                => 'vacations',
    ];

    /** Permissions/flags to inject per demo module so nav gates pass. */
    private const DEMO_PERMISSIONS = [
        'agreement' => ['canReadAgreement' => true],
        'vacations' => ['canReadVacationsReport' => true, 'isApprover' => true],
        'finances'  => ['canReadFinances' => true, 'isFinance' => true],
        'teams'     => ['canReadTeamsList' => true],
    ];

    /**
     * Modules requested via the X-Done-Demo-Modules header that are eligible
     * for demo simulation (dev/test hook to preview demo mode on installed modules).
     *
     * @return string[]
     */
    public static function simulatedDemoModules(IRequest $request): array
    {
        $header = $request->getHeader('X-Done-Demo-Modules');

        if ($header === '') {
            return [];
        }

        $requested = array_filter(array_map('trim', explode(',', $header)));

        return array_values(array_intersect($requested, self::DEMO_MODULES));
    }

    /**
     * @param string[] $simulated
     */
    public static function isDemoActive(string $module, array $simulated = []): bool
    {
        if (!\in_array($module, self::DEMO_MODULES, true)) {
            return false;
        }

        return !BaseModuleService::moduleExists($module) || \in_array($module, $simulated, true);
    }

    /**
     * @param string[] $simulated
     *
     * @return string[]
     */
    public static function activeDemoModules(array $simulated = []): array
    {
        return array_values(array_filter(
            self::DEMO_MODULES,
            static fn (string $m): bool => self::isDemoActive($m, $simulated)
        ));
    }

    public static function isDemoVisibleForUser(array $globalRoles, bool $isNcAdmin): bool
    {
        return $isNcAdmin
            || \in_array(GlobalRolesModel::ADMIN, $globalRoles, true)
            || \in_array(GlobalRolesModel::OFFICER, $globalRoles, true);
    }

    /**
     * Whether the current user may see demo mode at all (NC admin, global
     * ADMIN or OFFICER/director role).
     */
    public static function isDemoVisibleForCurrentUser(): bool
    {
        [$roles, $isNcAdmin] = self::currentUserRolesAndAdmin();

        return self::isDemoVisibleForUser($roles, $isNcAdmin);
    }

    /**
     * Active demo modules for the current request, honouring the simulation
     * header. Empty when nothing is demo-able (e.g. a full install with every
     * module present and no simulation).
     *
     * @return string[]
     */
    public static function activeDemoModulesForCurrentRequest(): array
    {
        $request = Server::get(IRequest::class);

        return self::activeDemoModules(self::simulatedDemoModules($request));
    }

    /**
     * Whether the current user has hidden demo modules in their settings.
     * Persisted as the HIDE_DEMO_MODULES custom setting (checkbox "1"/"0").
     */
    public static function isDemoHiddenForCurrentUser(): bool
    {
        $userId = UserService::getInstance()->getCurrentUserId();

        if (!$userId) {
            return false;
        }

        $rows = (new CustomSettingsDataModel())->getListByFilter([
            'user_id'    => $userId,
            'setting_id' => CustomSettingsModel::HIDE_DEMO_MODULES,
        ]);

        foreach ($rows as $row) {
            return trim((string)($row['value'] ?? ''), '"') === '1';
        }

        return false;
    }

    /**
     * @param string[] $activeDemoModules
     *
     * @return array<string,bool>
     */
    public static function demoPermissions(array $activeDemoModules): array
    {
        $perms = [];

        foreach ($activeDemoModules as $module) {
            if (isset(self::DEMO_PERMISSIONS[$module])) {
                $perms = array_merge($perms, self::DEMO_PERMISSIONS[$module]);
            }
        }

        return $perms;
    }

    public static function getProvider(string $module): ?DemoProviderInterface
    {
        if (!isset(self::PROVIDERS[$module])) {
            return null;
        }

        $class = self::PROVIDERS[$module];

        return new $class();
    }

    /**
     * Demo module that backs a given entity-card source, or null when the
     * source is CORE (user/project) or unknown.
     */
    public static function demoModuleForSource(int $source): ?string
    {
        return self::SOURCE_MODULE[$source] ?? null;
    }

    /**
     * Fake entity-card data when the card's source belongs to a demo-active
     * module and the current user may see demo mode; null otherwise (so the
     * caller falls through to the real DB lookup).
     *
     * @return null|array<string, mixed>
     */
    public static function demoCardData(IRequest $request, int $source, string $slug): ?array
    {
        if (!self::isSourceDemoActive($request, $source)) {
            return null;
        }

        return self::entityCardData($source, $slug);
    }

    /**
     * Read-only marker for a demo write to a source-backed core endpoint that
     * bypasses the module dispatcher (e.g. TableController column/sort/filter
     * settings, EntityController appearance image/color), so the axios
     * interceptor shows the centered demo banner and nothing is persisted.
     * Null when the source is not demo-backed (the real write proceeds).
     *
     * @return null|array{demo: bool, readOnly: bool, message: string}
     */
    public static function demoSourceWriteReadOnly(IRequest $request, int $source): ?array
    {
        if (!self::isSourceDemoActive($request, $source)) {
            return null;
        }

        return self::readOnlyMarker();
    }

    /**
     * Whether an entity source (as sent by table-settings endpoints and entity
     * cards) is backed by a demo-active module and the current user may see demo
     * mode. Shared gate for the source-based demo interceptors.
     */
    private static function isSourceDemoActive(IRequest $request, int $source): bool
    {
        $module = self::demoModuleForSource($source);

        if ($module === null) {
            return false;
        }

        if (!self::isDemoActive($module, self::simulatedDemoModules($request))) {
            return false;
        }

        [$roles, $isNcAdmin] = self::currentUserRolesAndAdmin();

        return self::isDemoVisibleForUser($roles, $isNcAdmin);
    }

    /**
     * Fake dictionary payload when a core dictionary endpoint is backed by a
     * demo-active module and the current user may see demo mode; null otherwise
     * (so the caller falls through to the real DictionariesService lookup).
     *
     * @return null|array{body: array<int, array<string, mixed>>, header: array<string, string>}
     */
    public static function demoDictionaryData(IRequest $request, string $dictTitle): ?array
    {
        if (!self::isDictionaryDemoActive($request, $dictTitle)) {
            return null;
        }

        if ($dictTitle === 'rolesInTeamDictionary') {
            return self::teamRolesDictionaryData();
        }

        return null;
    }

    /**
     * Read-only marker for a demo dictionary write (save/delete), so the axios
     * interceptor shows the centered demo banner and nothing is persisted.
     * Null when demo does not apply (real write proceeds).
     *
     * @return null|array{demo: bool, readOnly: bool, message: string}
     */
    public static function demoDictionaryReadOnly(IRequest $request, string $dictTitle): ?array
    {
        if (!self::isDictionaryDemoActive($request, $dictTitle)) {
            return null;
        }

        return self::readOnlyMarker();
    }

    /**
     * The read-only demo marker returned for any blocked demo write. The axios
     * response interceptor keys off { demo: true, readOnly: true } to show the
     * centered banner; the message is localized on the client.
     *
     * @return array{demo: bool, readOnly: bool, message: string}
     */
    private static function readOnlyMarker(): array
    {
        return [
            'demo'     => true,
            'readOnly' => true,
            'message'  => 'This is demo data. Saving and editing are available in the full version.',
        ];
    }

    /**
     * Whether a core dictionary title is backed by a demo-active module and the
     * current user may see demo mode. Shared gate for the dictionary read/write
     * demo interceptors.
     */
    private static function isDictionaryDemoActive(IRequest $request, string $dictTitle): bool
    {
        $module = self::DICTIONARY_MODULE[$dictTitle] ?? null;

        if ($module === null) {
            return false;
        }

        if (!self::isDemoActive($module, self::simulatedDemoModules($request))) {
            return false;
        }

        [$roles, $isNcAdmin] = self::currentUserRolesAndAdmin();

        return self::isDemoVisibleForUser($roles, $isNcAdmin);
    }

    /**
     * A single demo dictionary row by slug, for the "edit item" page
     * (DictionariesController::getDictionaryItemData). Reuses demoDictionaryData
     * (which does all the demo gating) and picks the matching body row; falls
     * back to the first row on an unknown slug so the edit form still opens
     * instead of redirecting to 404. Null when demo does not apply.
     *
     * @return null|array<string, mixed>
     */
    public static function demoDictionaryItem(IRequest $request, string $dictTitle, int | string | null $slug): ?array
    {
        $data = self::demoDictionaryData($request, $dictTitle);

        if ($data === null) {
            return null;
        }

        $body = $data['body'] ?? [];

        foreach ($body as $row) {
            if (($row['slug'] ?? null) === $slug || ($row['id'] ?? null) === $slug) {
                return $row;
            }
        }

        return $body[0] ?? [];
    }

    /**
     * Fake "Roles in Team" dictionary. Mirrors DictionariesService::getDictionary
     * over RolesInTeamModel: { body, header } where body is the row list
     * (BaseModel::getListByFilter shape: model fields plus slug/slug_type) and
     * header maps each field name to its translated title. English only.
     *
     * @return array{body: array<int, array<string, mixed>>, header: array<string, string>}
     */
    public static function teamRolesDictionaryData(): array
    {
        $roles = [
            ['id' => 'demo-role-1', 'name' => 'Team Lead', 'sort' => 0],
            ['id' => 'demo-role-2', 'name' => 'Member', 'sort' => 1],
            ['id' => 'demo-role-3', 'name' => 'Observer', 'sort' => 2],
            ['id' => 'demo-role-4', 'name' => 'Contributor', 'sort' => 3],
            ['id' => 'demo-role-5', 'name' => 'Advisor', 'sort' => 4],
        ];

        $body = [];

        foreach ($roles as $role) {
            $body[] = [
                'id'         => $role['id'],
                'name'       => $role['name'],
                'sort'       => $role['sort'],
                'deleted'    => false,
                'created_at' => '2025-01-01T00:00:00.000Z',
                'updated_at' => '2025-01-01T00:00:00.000Z',
                'slug'       => $role['id'],
                'slug_type'  => 2,
            ];
        }

        return [
            'body' => $body,
            // Column titles are UI chrome (not data), so they are localized just
            // like the real DictionariesService::getDictionaryHeaderData does.
            'header' => [
                'id'         => self::tr('ID'),
                'name'       => self::tr('Role name'),
                'sort'       => self::tr('Sort order'),
                'deleted'    => self::tr('Deleted'),
                'created_at' => self::tr('Created at'),
                'updated_at' => self::tr('Updated at'),
            ],
        ];
    }

    /**
     * Translate a UI label with the user's locale (same helper idea as
     * AbstractDemoProvider::tr): demo DATA stays English, but chrome like column
     * titles is localized. Falls back to the English key when there is no DI
     * container (e.g. unit tests).
     */
    private static function tr(string $key): string
    {
        try {
            return TranslateService::getInstance()->getTranslate($key);
        } catch (\Throwable $e) {
            return $key;
        }
    }

    /**
     * Fake EntityPreview model for a demo entity card. Mirrors the shape of
     * EntitiesService::addModelData (title/value/field/original_index). Sources
     * without a card page yet return an empty-but-valid model.
     *
     * @return array<string, mixed>
     */
    public static function entityCardData(int $source, string $slug): array
    {
        if ($source === PermissionsEntitiesModel::TEAM_ENTITY) {
            // Resolve the same demo team the title uses, so the card body matches
            // the clicked row. Unknown slug falls back to the first demo team.
            $teams = [
                'demo-team-1' => ['name' => 'Platform', 'lead' => 'John Carter, Backend Developer', 'comment' => 'Core platform team'],
                'demo-team-2' => ['name' => 'Growth', 'lead' => 'Emma Reid, Product Manager', 'comment' => 'Growth and marketing team'],
                'demo-team-3' => ['name' => 'Design', 'lead' => 'Sophia Bennett, Lead Designer', 'comment' => 'Product design and user experience'],
                'demo-team-4' => ['name' => 'Data', 'lead' => 'Liam Novak, Data Engineer', 'comment' => 'Data platform and analytics pipelines'],
                'demo-team-5' => ['name' => 'Mobile', 'lead' => 'Olivia Hayes, Mobile Lead', 'comment' => 'iOS and Android applications'],
                'demo-team-6' => ['name' => 'Security', 'lead' => 'Noah Fischer, Security Engineer', 'comment' => 'Application security and compliance'],
            ];
            $team = $teams[$slug] ?? $teams['demo-team-1'];

            return [
                'model_data' => [
                    ['title' => 'Name', 'value' => $team['name'], 'field' => 'name', 'original_index' => 0],
                    ['title' => 'Lead', 'value' => $team['lead'], 'field' => 'lead_name', 'original_index' => 1],
                    ['title' => 'Comment', 'value' => $team['comment'], 'field' => 'comment', 'original_index' => 2],
                ],
            ];
        }

        return ['model_data' => []];
    }

    /**
     * Resolve the current user's global roles and NC-admin flag. Mirrors
     * ModulesController::currentUserRolesAndAdmin; returns [[], false] when
     * there is no authenticated user.
     *
     * @return array{0: array<int>, 1: bool}
     */
    private static function currentUserRolesAndAdmin(): array
    {
        $userSession = Server::get(IUserSession::class);
        $userObj = $userSession->getUser();

        if (!$userObj instanceof IUser) {
            return [[], false];
        }

        $groupManager = Server::get(IGroupManager::class);
        $isNcAdmin = $groupManager->isAdmin($userObj->getUID());

        $userService = UserService::getInstance();
        $userId = $userService->getCurrentUserId();
        $roles = $userId ? $userService->getUserGlobalRoles($userId) : [];

        return [$roles, $isNcAdmin];
    }
}
