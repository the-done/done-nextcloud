<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Vacations\Service;

use OCA\Done\AppInfo\Application;
use OCA\Done\Models\Dictionaries\ContractsModel;
use OCA\Done\Models\Dictionaries\PositionsModel;
use OCA\Done\Models\UserModel;
use OCA\Done\Models\UsersRolesInProjectsModel;
use OCA\Done\Modules\Agreement\Model\AgreementRequestHistoryModel;
use OCA\Done\Modules\Agreement\Model\AgreementRequestsModel;
use OCA\Done\Modules\Agreement\Model\AgreementSchemeLinesModel;
use OCA\Done\Modules\Agreement\Model\AgreementSchemesModel;
use OCA\Done\Modules\Agreement\Service\AgreementService;
use OCA\Done\Modules\Projects\Models\ProjectModel;
use OCA\Done\Modules\Vacations\Model\VacationsBalanceModel;
use OCA\Done\Modules\Vacations\Model\VacationsCoefficientsModel;
use OCA\Done\Modules\Vacations\Model\VacationsModel;
use OCA\Done\Modules\Vacations\Model\VacationsTypeSettingsModel;
use OCA\Done\Modules\Vacations\Model\VacationsTypesModel;
use OCA\Done\Service\BaseService;
use OCA\Done\Service\TranslateService;
use OCA\Done\Service\UserService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IAppConfig;
use OCP\Server;

class VacationsService
{
    /** App-config key holding the report usage separator (used vs limit). */
    private const REPORT_SEPARATOR_KEY = 'vacations_report_separator';

    /** Default separator when none has been configured. */
    private const REPORT_SEPARATOR_DEFAULT = '/';

    /** App-config key holding the minimum length (days) of a mandatory leave. */
    private const MANDATORY_LEAVE_MIN_DAYS_KEY = 'vacations_mandatory_min_days';

    /** Default mandatory-leave minimum (RF Labor Code: one part must be >=14 days). */
    private const MANDATORY_LEAVE_MIN_DAYS_DEFAULT = 14;

    private UserService $userService;
    private BaseService $baseService;
    private TranslateService $translateService;
    private IAppConfig $appConfig;
    private static VacationsService $instance;

    public function __construct()
    {
        $this->userService = UserService::getInstance();
        $this->baseService = BaseService::getInstance();
        $this->translateService = TranslateService::getInstance();
        $this->appConfig = Server::get(IAppConfig::class);
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = Server::get(self::class);
        }

        return self::$instance;
    }

    /**
     * Get remaining vacation days for a user by vacation type.
     *
     * Priority for determining limit:
     * 1. Individual balance from VacationsBalanceModel (if exists)
     * 2. Default limit from VacationsTypeSettingsModel
     *
     * For vacations spanning across years (e.g., Dec 25 - Jan 10), only the days
     * falling within the requested year are counted.
     *
     * @param null|int    $year   The year to calculate remaining days for. Defaults to current year.
     * @param null|string $userId The user ID to calculate for. Defaults to current user.
     *
     * @return array<string, array{
     *     type_id: string,
     *     type_name: string,
     *     type_color: string,
     *     limit: int,
     *     used: int,
     *     planned: int,
     *     remaining: int,
     *     display_warning: bool,
     *     is_custom_limit: bool
     * }> Indexed by type_id. Returns empty array if no vacation types configured.
     */
    public function getRemainingForEmployee(?int $year = null, ?string $userId = null): array
    {
        if (!isset($year)) {
            $year = (int)date('Y');
        }

        if ($userId === null) {
            $userId = $this->userService->getCurrentUserId();
        }

        $yearStart = new \DateTimeImmutable($year . '-01-01');
        $yearEnd = new \DateTimeImmutable($year . '-12-31');

        $vacationsModel = new VacationsModel();
        $vacationsTypesModel = new VacationsTypesModel();
        $vacationsTypeSettingsModel = new VacationsTypeSettingsModel();
        $vacationsBalanceModel = new VacationsBalanceModel();

        $vacationsTypes = $vacationsTypesModel->getIndexedListByFilter();
        $vacationsTypeSettings = $vacationsTypeSettingsModel->getIndexedListByFilter('type_id');
        $individualBalances = $vacationsBalanceModel->getBalancesForEmployee($userId, $year);

        if (empty($vacationsTypes)) {
            return [];
        }

        // Get approved vacations
        $approvedFilter = [
            'user_id'    => $userId,
            'date_start' => ['<=', $yearEnd],
            'date_end'   => ['>=', $yearStart],
            'status_id'  => VacationsModel::STATUS_APPROVED,
        ];
        $approvedVacations = $vacationsModel->getListByFilter($approvedFilter);

        // Get planned (pending) vacations separately
        $plannedFilter = [
            'user_id'    => $userId,
            'date_start' => ['<=', $yearEnd],
            'date_end'   => ['>=', $yearStart],
            'status_id'  => VacationsModel::STATUS_PENDING,
        ];
        $plannedVacations = $vacationsModel->getListByFilter($plannedFilter);

        $usedDays = [];
        $plannedDays = [];

        // Calculate used days (approved)
        foreach ($approvedVacations as $vacation) {
            $typeId = $vacation['type_id'];
            $usedDays[$typeId] = ($usedDays[$typeId] ?? 0)
                + $this->calculateDaysInYear($vacation, $yearStart, $yearEnd);
        }

        // Calculate planned days (submitted but not yet approved)
        foreach ($plannedVacations as $vacation) {
            $typeId = $vacation['type_id'];
            $plannedDays[$typeId] = ($plannedDays[$typeId] ?? 0)
                + $this->calculateDaysInYear($vacation, $yearStart, $yearEnd);
        }

        $result = [];

        foreach ($vacationsTypes as $typeId => $type) {
            $baseLimit = (int)($vacationsTypeSettings[$typeId]['value'] ?? 0);
            $isCustomLimit = isset($individualBalances[$typeId]);

            if ($isCustomLimit) {
                $annualLimit = (float)$individualBalances[$typeId]['days_total'];
            } else {
                $annualLimit = $this->calculateAnnualLimit($userId, $typeId, $year, $baseLimit);
            }

            $carryover = $this->calculateCarryover($userId, $typeId, $year);
            $limit = $annualLimit + $carryover;

            $used = $usedDays[$typeId] ?? 0;
            $planned = $plannedDays[$typeId] ?? 0;
            $remaining = $limit - $used;

            $result[$typeId] = [
                'type_id'         => $typeId,
                'type_name'       => $type['name'],
                'type_color'      => $type['color'] ?? '#000000',
                'limit'           => round($limit, 2),
                'annual_limit'    => round($annualLimit, 2),
                'carryover'       => round($carryover, 2),
                'used'            => $used,
                'planned'         => $planned,
                'remaining'       => round($remaining, 2),
                'display_warning' => (bool)($vacationsTypeSettings[$typeId]['display_warning'] ?? false),
                'is_custom_limit' => $isCustomLimit,
            ];
        }

        return $result;
    }

    /**
     * Calculate the annual day allocation for (user, type, year) applying monthly coefficient proration.
     *
     * For each month of the year, takes the coefficient active on the 1st of that month
     * (1.0 if no record covers that date) and adds (baseLimit / 12) * coefficient.
     *
     * @param string $userId    Employee user ID
     * @param string $typeId    Vacation type ID
     * @param int    $year      Calendar year
     * @param int    $baseLimit Base annual limit for the type
     */
    public function calculateAnnualLimit(string $userId, string $typeId, int $year, int $baseLimit): float
    {
        if ($baseLimit <= 0) {
            return 0.0;
        }

        $coefficientsModel = new VacationsCoefficientsModel();
        $monthly = $baseLimit / 12;
        $total = 0.0;

        for ($month = 1; $month <= 12; $month++) {
            $monthStart = new \DateTimeImmutable(\sprintf('%04d-%02d-01', $year, $month));
            $record = $coefficientsModel->getActive($userId, $typeId, $monthStart);
            $coefficient = $record !== null ? (float)$record['value'] : 1.0;
            $total += $monthly * $coefficient;
        }

        return $total;
    }

    /**
     * Calculate cumulative unused days carried over from previous years for (user, type, year).
     *
     * Walks backwards from $year - 1 until the earliest year with any vacation or
     * coefficient record. For each prior year, accumulates max(0, allocation + prevCarryover - used).
     */
    public function calculateCarryover(string $userId, string $typeId, int $year): float
    {
        $startYear = $this->getEarliestRelevantYear($userId, $typeId);

        if ($startYear === null || $startYear >= $year) {
            return 0.0;
        }

        $vacationsTypeSettingsModel = new VacationsTypeSettingsModel();
        $vacationsBalanceModel = new VacationsBalanceModel();
        $vacationsModel = new VacationsModel();
        $typeSettings = $vacationsTypeSettingsModel->getIndexedListByFilter('type_id');
        $baseLimit = (int)($typeSettings[$typeId]['value'] ?? 0);

        $carryover = 0.0;

        for ($y = $startYear; $y < $year; $y++) {
            $individual = $vacationsBalanceModel->getBalanceForEmployee($userId, $typeId, $y);
            $allocation = $individual !== null
                ? (float)$individual['days_total']
                : $this->calculateAnnualLimit($userId, $typeId, $y, $baseLimit);

            $yearStart = new \DateTimeImmutable($y . '-01-01');
            $yearEnd = new \DateTimeImmutable($y . '-12-31');

            $approved = $vacationsModel->getListByFilter([
                'user_id'    => $userId,
                'type_id'    => $typeId,
                'date_start' => ['<=', $yearEnd],
                'date_end'   => ['>=', $yearStart],
                'status_id'  => VacationsModel::STATUS_APPROVED,
            ]);

            $used = 0.0;

            foreach ($approved as $vacation) {
                $used += $this->calculateDaysInYear($vacation, $yearStart, $yearEnd);
            }

            $carryover = max(0.0, $carryover + $allocation - $used);
        }

        return $carryover;
    }

    /**
     * Find the earliest year that has any vacation or coefficient record for (user, type).
     * Returns null if no records exist.
     */
    private function getEarliestRelevantYear(string $userId, string $typeId): ?int
    {
        $vacationsModel = new VacationsModel();
        $coefficientsModel = new VacationsCoefficientsModel();

        $vacations = $vacationsModel->getListByFilter(
            ['user_id' => $userId, 'type_id' => $typeId],
            ['date_start'],
            ['date_start', 'ASC']
        );

        $coefficients = $coefficientsModel->getListByFilter(
            ['user_id' => $userId, 'type_id' => $typeId],
            ['effective_from'],
            ['effective_from', 'ASC']
        );

        $candidates = [];

        if (!empty($vacations)) {
            $dateStart = $vacations[0]['date_start'];
            $dateObj = $dateStart instanceof \DateTimeInterface
                ? $dateStart
                : new \DateTimeImmutable((string)$dateStart);
            $candidates[] = (int)$dateObj->format('Y');
        }

        if (!empty($coefficients)) {
            $effFrom = $coefficients[0]['effective_from'];
            $dateObj = $effFrom instanceof \DateTimeInterface
                ? $effFrom
                : new \DateTimeImmutable((string)$effFrom);
            $candidates[] = (int)$dateObj->format('Y');
        }

        return $candidates !== [] ? min($candidates) : null;
    }

    /**
     * Get remaining vacation days for multiple users by vacation type.
     *
     * @param null|int $year     The year to calculate remaining days for. Defaults to current year.
     * @param array    $usersIds the users IDs to calculate for
     *
     * @return array<string, array<string, array{
     *     type_id: string,
     *     type_name: string,
     *     type_color: string,
     *     limit: int,
     *     used: int,
     *     planned: int,
     *     remaining: int,
     *     display_warning: bool,
     *     is_custom_limit: bool
     * }>> Indexed by user_id, then by type_id
     */
    public function getRemainingForEmployees(?int $year = null, array $usersIds = []): array
    {
        if (empty($usersIds)) {
            return [];
        }

        if (!isset($year)) {
            $year = (int)date('Y');
        }

        $yearStart = new \DateTimeImmutable($year . '-01-01');
        $yearEnd = new \DateTimeImmutable($year . '-12-31');

        $vacationsModel = new VacationsModel();
        $vacationsTypesModel = new VacationsTypesModel();
        $vacationsTypeSettingsModel = new VacationsTypeSettingsModel();
        $vacationsBalanceModel = new VacationsBalanceModel();

        $vacationsTypes = $vacationsTypesModel->getIndexedListByFilter();
        $vacationsTypeSettings = $vacationsTypeSettingsModel->getIndexedListByFilter('type_id');

        if (empty($vacationsTypes)) {
            return [];
        }

        // Get approved vacations for all users
        $approvedFilter = [
            'user_id'    => ['IN', $usersIds, IQueryBuilder::PARAM_STR_ARRAY],
            'date_start' => ['<=', $yearEnd],
            'date_end'   => ['>=', $yearStart],
            'status_id'  => VacationsModel::STATUS_APPROVED,
        ];
        $approvedVacations = $vacationsModel->getListByFilter($approvedFilter);

        // Get planned (pending) vacations for all users
        $plannedFilter = [
            'user_id'    => ['IN', $usersIds, IQueryBuilder::PARAM_STR_ARRAY],
            'date_start' => ['<=', $yearEnd],
            'date_end'   => ['>=', $yearStart],
            'status_id'  => VacationsModel::STATUS_PENDING,
        ];
        $plannedVacations = $vacationsModel->getListByFilter($plannedFilter);

        $usedDays = [];
        $plannedDays = [];

        foreach ($approvedVacations as $vacation) {
            $userId = $vacation['user_id'];
            $typeId = $vacation['type_id'];
            $usedDays[$userId][$typeId] = ($usedDays[$userId][$typeId] ?? 0)
                + $this->calculateDaysInYear($vacation, $yearStart, $yearEnd);
        }

        foreach ($plannedVacations as $vacation) {
            $userId = $vacation['user_id'];
            $typeId = $vacation['type_id'];
            $plannedDays[$userId][$typeId] = ($plannedDays[$userId][$typeId] ?? 0)
                + $this->calculateDaysInYear($vacation, $yearStart, $yearEnd);
        }

        $result = [];

        foreach ($usersIds as $userId) {
            $individualBalances = $vacationsBalanceModel->getBalancesForEmployee($userId, $year);

            foreach ($vacationsTypes as $typeId => $type) {
                $baseLimit = (int)($vacationsTypeSettings[$typeId]['value'] ?? 0);
                $isCustomLimit = isset($individualBalances[$typeId]);

                if ($isCustomLimit) {
                    $annualLimit = (float)$individualBalances[$typeId]['days_total'];
                } else {
                    $annualLimit = $this->calculateAnnualLimit($userId, $typeId, $year, $baseLimit);
                }

                $carryover = $this->calculateCarryover($userId, $typeId, $year);
                $limit = $annualLimit + $carryover;

                $used = $usedDays[$userId][$typeId] ?? 0;
                $planned = $plannedDays[$userId][$typeId] ?? 0;
                $remaining = $limit - $used;

                $result[$userId][$typeId] = [
                    'type_id'         => $typeId,
                    'type_name'       => $type['name'],
                    'type_color'      => $type['color'] ?? '#000000',
                    'limit'           => round($limit, 2),
                    'annual_limit'    => round($annualLimit, 2),
                    'carryover'       => round($carryover, 2),
                    'used'            => $used,
                    'planned'         => $planned,
                    'remaining'       => round($remaining, 2),
                    'display_warning' => (bool)($vacationsTypeSettings[$typeId]['display_warning'] ?? false),
                    'is_custom_limit' => $isCustomLimit,
                ];
            }
        }

        return $result;
    }

    /**
     * Calculate days of a vacation that fall within a specific year.
     *
     * Returns a float because a half-day absence shortens the last day by 0.5.
     * The half-day reduction is applied only when the absence actually ends
     * within this year (the half is on date_end).
     */
    private function calculateDaysInYear(array $vacation, \DateTimeImmutable $yearStart, \DateTimeImmutable $yearEnd): float
    {
        $vacationStart = new \DateTimeImmutable($vacation['date_start']);
        $vacationEnd = new \DateTimeImmutable($vacation['date_end']);

        $effectiveStart = max($vacationStart, $yearStart);
        $effectiveEnd = min($vacationEnd, $yearEnd);

        $days = $effectiveStart->diff($effectiveEnd)->days + 1;

        if (!empty($vacation['half_day']) && $vacationEnd >= $yearStart && $vacationEnd <= $yearEnd) {
            $days -= 0.5;
        }

        return (float)$days;
    }

    /**
     * Total length of an absence in days, accounting for the half-day flag.
     *
     * @param string $dateStart Y-m-d
     * @param string $dateEnd   Y-m-d
     * @param bool   $halfDay   when true, the last day counts as half
     */
    private function vacationDaysCount(string $dateStart, string $dateEnd, bool $halfDay): float
    {
        $start = new \DateTimeImmutable($dateStart);
        $end = new \DateTimeImmutable($dateEnd);

        $days = $start->diff($end)->days + 1;

        return $halfDay ? $days - 0.5 : (float)$days;
    }

    /**
     * Get vacations data for the table view, grouped by month and employee.
     *
     * @param string        $dateFrom         start date filter (Y-m-d format)
     * @param string        $dateTo           end date filter (Y-m-d format)
     * @param null|string[] $employeeIds      filter by employee IDs
     * @param null|string[] $projectIds       filter by project IDs
     * @param null|string[] $vacationTypeIds  filter by vacation type IDs
     * @param null|string   $vacationStatusId filter by vacation status
     *
     * @return array grouped by month (01-12), then by employee ID
     */
    public function getVacationsTableData(
        string $dateFrom,
        string $dateTo,
        ?array $employeeIds = null,
        ?array $projectIds = null,
        ?array $vacationTypeIds = null,
        ?string $vacationStatusId = null,
    ): array {
        $dateFromObj = new \DateTimeImmutable($dateFrom);
        $dateToObj = new \DateTimeImmutable($dateTo);
        $year = (int)$dateFromObj->format('Y');

        $vacationsModel = new VacationsModel();
        $vacationsTypesModel = new VacationsTypesModel();
        $userModel = new UserModel();
        $projectModel = new ProjectModel();

        $currentUserId = $this->userService->getCurrentUserId();
        $isHeadInfo = $this->userService->isHead();
        $isOfficer = $this->userService->isOfficer();
        $isAdmin = $this->userService->isAdmin();

        $filter = $this->prepareFilterForVacations(
            $dateFromObj,
            $dateToObj,
            $currentUserId,
            $isHeadInfo,
            $isOfficer,
            $isAdmin,
            $employeeIds,
            $projectIds,
            $vacationTypeIds,
            $vacationStatusId
        );

        $vacations = $vacationsModel->getListByFilter($filter, ['*'], ['date_start', 'ASC']);

        if (empty($vacations)) {
            return [];
        }

        // Get vacation types for colors
        $vacationTypes = $vacationsTypesModel->getIndexedListByFilter();

        // Get unique employee IDs and projects IDs from vacations
        [$allEmployeeIds, $projectsIds] = $this->getDataFromVacationsRecords($vacations);

        $employees = $userModel->getListForLink(
            ['id' => ['IN', $allEmployeeIds, IQueryBuilder::PARAM_STR_ARRAY]],
            true,
            true,
        );

        $projects = $projectModel->getIndexedListByFilter(
            'id',
            ['id' => ['IN', $projectsIds, IQueryBuilder::PARAM_STR_ARRAY]],
            ['*'],
            [],
            [],
            true
        );

        $remainingByEmployee = $this->getRemainingForEmployees($year, $allEmployeeIds);

        // Get agreement requests for all vacations
        $agreementRequestsModel = new AgreementRequestsModel();
        $vacationIds = array_column($vacations, 'id');
        $agreementRequests = [];

        if (!empty($vacationIds)) {
            $requestsList = $agreementRequestsModel->getListByFilter([
                'entity_type' => AgreementSchemesModel::ENTITY_TYPE_VACATION,
                'entity_id'   => ['IN', $vacationIds, IQueryBuilder::PARAM_STR_ARRAY],
            ]);

            foreach ($requestsList as $req) {
                $agreementRequests[$req['entity_id']] = $req;
            }
        }

        // Group vacations by month and employee
        $result = [];

        foreach ($vacations as $vacation) {
            $vacationStart = new \DateTimeImmutable($vacation['date_start']);
            $vacationEnd = new \DateTimeImmutable($vacation['date_end']);
            $month = $vacationStart->format('m');
            $employeeId = $vacation['user_id'];
            $vacationTypeId = $vacation['type_id'];

            $result[$month] ??= [];
            $result[$month][$employeeId] ??= [
                'name'              => $employees[$employeeId]['name'] ?? 'Unknown',
                'remaining_by_type' => $remainingByEmployee[$employeeId] ?? [],
                'list'              => [],
            ];

            $daysCount = $this->vacationDaysCount(
                $vacation['date_start'],
                $vacation['date_end'],
                !empty($vacation['half_day'])
            );
            $typeData = $vacationTypes[$vacationTypeId] ?? [];

            // Get agreement request info
            $agreementRequest = $agreementRequests[$vacation['id']] ?? null;

            $result[$month][$employeeId]['list'][] = [
                'id'                     => $vacation['id'],
                'type_id'                => $vacationTypeId,
                'user_id'                => $employeeId,
                'type_name'              => $typeData['name'] ?? '',
                'type_color'             => $typeData['color'] ?? '#000000',
                'date_start'             => $vacation['date_start'],
                'date_end'               => $vacation['date_end'],
                'half_day'               => (bool)($vacation['half_day'] ?? false),
                'status_id'              => $vacation['status_id'],
                'days_count'             => $daysCount,
                'comment'                => $vacation['comment'] ?? '',
                'project_id'             => $vacation['project_id'],
                'created_by'             => $vacation['created_by'],
                'agreement_request_id'   => $agreementRequest['id'] ?? null,
                'agreement_status'       => $agreementRequest['status'] ?? null,
                'agreement_current_line' => $agreementRequest['current_line_id'] ?? null,
            ];
        }

        // Sort by month
        ksort($result);

        return $result;
    }

    /**
     * Create a new vacation request and record initial history.
     *
     * @param array       $data      vacation data
     * @param null|string $createdBy user creating the request (defaults to current user)
     *
     * @return string the new vacation ID
     */
    public function createVacation(array $data, ?string $createdBy = null): string
    {
        $vacationsModel = new VacationsModel();
        $createdBy = $createdBy ?? $this->userService->getCurrentUserId();

        // Set default values
        $data['created_by'] = $createdBy;
        $data['status_id'] = $data['status_id'] ?? VacationsModel::STATUS_PENDING;

        $vacationId = $vacationsModel->addData($data);

        if (!empty($vacationId) && $data['status_id'] === VacationsModel::STATUS_PENDING) {
            // Create agreement request if vacation is submitted for approval
            $req = $this->createAgreementRequestForVacation($vacationId, $data);
        }

        return $vacationId ?? '';
    }

    /**
     * Create an agreement request for a vacation.
     */
    public function createAgreementRequestForVacation(string $vacationId, array $vacationData): ?array
    {
        $agreementService = AgreementService::getInstance();
        [$cachedTitle, $cachedData] = $this->buildVacationCache($vacationData);

        return $agreementService->createRequest(
            AgreementSchemesModel::ENTITY_TYPE_VACATION,
            $vacationId,
            $cachedTitle,
            $cachedData,
            $vacationData['user_id'],
            $vacationData['project_id'] ?? null
        );
    }

    /**
     * Refresh the cached title/data on the agreement request for a vacation,
     * and if the request is currently returned (sent back for revision) or
     * already approved (edited afterwards), restart the approval flow.
     */
    public function refreshAgreementRequestForVacation(string $vacationId, array $vacationData): void
    {
        $agreementRequestsModel = new AgreementRequestsModel();
        $request = $agreementRequestsModel->getRequestForEntity(
            AgreementSchemesModel::ENTITY_TYPE_VACATION,
            $vacationId
        );

        if (empty($request)) {
            return;
        }

        [$cachedTitle, $cachedData] = $this->buildVacationCache($vacationData);

        $agreementRequestsModel->update([
            'cached_title' => $cachedTitle,
            'cached_data'  => json_encode($cachedData),
        ], $request['id']);

        if (\in_array($request['status'] ?? '', [
            AgreementRequestsModel::STATUS_RETURNED,
            AgreementRequestsModel::STATUS_APPROVED,
        ], true)) {
            AgreementService::getInstance()->resubmitRequest($request['id']);
        }
    }

    /**
     * Build cached title and data array for an agreement request from vacation data.
     *
     * @return array{0: string, 1: array}
     */
    private function buildVacationCache(array $vacationData): array
    {
        $vacationsTypesModel = new VacationsTypesModel();
        $userModel = new UserModel();

        $vacationType = $vacationsTypesModel->getItem($vacationData['type_id']);
        $users = $userModel->getListForLink(
            ['id' => $vacationData['user_id']],
            true
        );
        $employeeName = $users[$vacationData['user_id']]['name'] ?? 'Unknown';

        $dateStart = $vacationData['date_start'] instanceof \DateTimeImmutable
            ? $vacationData['date_start']->format('Y-m-d')
            : (new \DateTimeImmutable($vacationData['date_start']))->format('Y-m-d');

        $dateEnd = $vacationData['date_end'] instanceof \DateTimeImmutable
            ? $vacationData['date_end']->format('Y-m-d')
            : (new \DateTimeImmutable($vacationData['date_end']))->format('Y-m-d');

        $cachedTitle = \sprintf(
            '%s: %s (%s - %s)',
            !empty($vacationType['name'])
                ? $this->translateService->getTranslate($vacationType['name'])
                : $this->translateService->getTranslate('Vacation'),
            $employeeName,
            $dateStart,
            $dateEnd
        );

        $cachedData = [
            'employee'   => $employeeName,
            'type'       => $vacationType['name'] ?? 'Unknown',
            'date_start' => $dateStart,
            'date_end'   => $dateEnd,
            'days'       => (new VacationsModel())->calculateDays($dateStart, $dateEnd),
        ];

        if (!empty($vacationData['comment'])) {
            $cachedData['comment'] = $vacationData['comment'];
        }

        return [$cachedTitle, $cachedData];
    }

    /**
     * Get upcoming and currently active approved paid leave vacations for a user.
     *
     * Returns approved vacations whose type is "Paid Leave", ordered by date_start.
     * Each entry includes an `is_active` flag indicating whether the vacation is ongoing today.
     *
     * @param null|string $userId defaults to current user
     *
     * @return array<int, array{id: string, date_start: string, date_end: string, days_count: int, type_name: string, is_active: bool}>
     */
    public function getUpcomingPaidVacations(?string $userId = null): array
    {
        if ($userId === null) {
            $userId = $this->userService->getCurrentUserId();
        }

        $today = new \DateTimeImmutable('today');

        $vacationsTypesModel = new VacationsTypesModel();
        $vacationsModel = new VacationsModel();

        $paidTypes = $vacationsTypesModel->getListByFilter(['name' => 'Paid Leave']);

        if (empty($paidTypes)) {
            return [];
        }

        $paidTypeIds = array_column($paidTypes, 'id');
        $paidTypeNames = array_column($paidTypes, 'name', 'id');

        $filter = [
            'user_id'   => $userId,
            'status_id' => VacationsModel::STATUS_APPROVED,
            'type_id'   => ['IN', $paidTypeIds, IQueryBuilder::PARAM_STR_ARRAY],
            'date_end'  => ['>=', $today],
        ];

        $vacations = $vacationsModel->getListByFilter($filter, ['*'], ['date_start', 'ASC']);

        $result = [];

        foreach ($vacations as $vacation) {
            $start = new \DateTimeImmutable($vacation['date_start']);
            $end = new \DateTimeImmutable($vacation['date_end']);

            $result[] = [
                'id'         => $vacation['id'],
                'date_start' => $vacation['date_start'],
                'date_end'   => $vacation['date_end'],
                'days_count' => $this->vacationDaysCount($vacation['date_start'], $vacation['date_end'], !empty($vacation['half_day'])),
                'type_name'  => $paidTypeNames[$vacation['type_id']] ?? '',
                'is_active'  => $start <= $today && $today <= $end,
            ];
        }

        return $result;
    }

    /**
     * Get all vacations in a date range formatted for Gantt display, grouped by employee.
     *
     * Returns approved and pending vacations that overlap [dateFrom, dateTo] by default.
     * If $vacationStatusId is provided, that single status is used instead.
     * Scope: HEAD role users see their subordinates; other approvers see all employees,
     * unless an explicit $employeeIds filter is provided.
     *
     * @param string        $dateFrom         period start (Y-m-d)
     * @param string        $dateTo           period end (Y-m-d)
     * @param null|string[] $employeeIds      filter by employee user IDs
     * @param null|string[] $projectIds       filter by project IDs
     * @param null|string[] $vacationTypeIds  filter by vacation type IDs
     * @param null|string   $vacationStatusId filter by single vacation status
     *
     * @return array<int, array{user_id: string, user_name: string, vacations: array}>
     */
    public function getVacationsForGantt(
        string $dateFrom,
        string $dateTo,
        ?array $employeeIds = null,
        ?array $projectIds = null,
        ?array $vacationTypeIds = null,
        ?string $vacationStatusId = null,
    ): array {
        $dateFromObj = new \DateTimeImmutable($dateFrom);
        $dateToObj = new \DateTimeImmutable($dateTo . ' 23:59:59');

        $vacationsModel = new VacationsModel();
        $vacationsTypesModel = new VacationsTypesModel();
        $userModel = new UserModel();

        $currentUserId = $this->userService->getCurrentUserId();
        $isHeadInfo = $this->userService->isHead();
        $isOfficer = $this->userService->isOfficer();
        $isAdmin = $this->userService->isAdmin();

        $filter = $this->prepareFilterForGantt(
            $currentUserId,
            $isHeadInfo,
            $isOfficer,
            $isAdmin,
            $employeeIds,
            $projectIds,
            $vacationTypeIds,
            $vacationStatusId,
        );

        $vacations = $vacationsModel->getListByFilter($filter, ['*'], ['date_start', 'ASC']);

        // Keep only vacations that overlap the requested period
        $vacations = array_values(array_filter($vacations, static function (array $v) use ($dateFromObj, $dateToObj): bool {
            $start = new \DateTimeImmutable($v['date_start']);
            $end = new \DateTimeImmutable($v['date_end']);

            return $start <= $dateToObj && $end >= $dateFromObj;
        }));

        if (empty($vacations)) {
            return [];
        }

        $vacationTypes = $vacationsTypesModel->getIndexedListByFilter();

        $userIds = array_unique(array_column($vacations, 'user_id'));
        $employees = $userModel->getListForLink(
            ['id' => ['IN', $userIds, IQueryBuilder::PARAM_STR_ARRAY]],
            true,
            true,
        );

        $vacationProjectIds = BaseService::getField($vacations, 'project_id', true);
        $projects = [];

        if (!empty($vacationProjectIds)) {
            $projectModel = new ProjectModel();
            $projects = $projectModel->getIndexedListByFilter(
                'id',
                ['id' => ['IN', $vacationProjectIds, IQueryBuilder::PARAM_STR_ARRAY]],
                ['*'],
                [],
                [],
                true,
            );
        }

        $byUser = [];

        foreach ($vacations as $vacation) {
            $userId = $vacation['user_id'];
            $typeId = $vacation['type_id'];
            $typeData = $vacationTypes[$typeId] ?? [];

            if (!isset($byUser[$userId])) {
                $byUser[$userId] = [
                    'user_id'   => $userId,
                    'user_name' => $employees[$userId]['name'] ?? $userId,
                    'vacations' => [],
                ];
            }

            $projectId = $vacation['project_id'] ?? null;

            $byUser[$userId]['vacations'][] = [
                'id'           => $vacation['id'],
                'date_start'   => $vacation['date_start'],
                'date_end'     => $vacation['date_end'],
                'type_id'      => $typeId,
                'type_name'    => $typeData['name'] ?? '',
                'type_color'   => $typeData['color'] ?? '#9E9E9E',
                'status_id'    => $vacation['status_id'],
                'half_day'     => (bool)($vacation['half_day'] ?? false),
                'days_count'   => $this->vacationDaysCount($vacation['date_start'], $vacation['date_end'], !empty($vacation['half_day'])),
                'comment'      => $vacation['comment'] ?? '',
                'project_id'   => $projectId,
                'project_name' => $projectId !== null ? ($projects[$projectId]['name'] ?? '') : '',
            ];
        }

        $result = array_values($byUser);
        usort($result, static fn ($a, $b) => strcmp($a['user_name'], $b['user_name']));

        return $result;
    }

    /**
     * Get agreement request for a vacation.
     */
    public function getAgreementRequestForVacation(string $vacationId): ?array
    {
        $agreementService = AgreementService::getInstance();

        return $agreementService->getRequestForEntity(
            AgreementSchemesModel::ENTITY_TYPE_VACATION,
            $vacationId
        );
    }

    /**
     * Update vacation status based on agreement status.
     * Status is synchronized directly from Agreement module.
     *
     * @param string $vacationId      Vacation ID
     * @param string $agreementStatus Agreement status (pending, approved, rejected, returned)
     * @param string $actorUserId     User who made the action
     */
    public function updateVacationFromAgreement(
        string $vacationId,
        string $agreementStatus,
        string $actorUserId
    ): void {
        $vacationsModel = new VacationsModel();
        $vacation = $vacationsModel->getItem($vacationId);

        if (empty($vacation)) {
            return;
        }

        $oldStatus = $vacation['status_id'] ?? '';

        // Status comes directly from Agreement module
        if ($oldStatus !== $agreementStatus) {
            $vacationsModel->update(['status_id' => $agreementStatus], $vacationId);
        }
    }

    /**
     * Cancel a vacation request initiated by the owner. Soft-cancel: the record
     * stays in the table with status_id = canceled; the related agreement request
     * is also marked canceled so approvers no longer see it as pending.
     *
     * @param string      $vacationId Vacation ID
     * @param null|string $userId     Defaults to current user
     *
     * @return array{success: bool, error?: string}
     */
    public function cancelVacation(string $vacationId, ?string $userId = null): array
    {
        $vacationsModel = new VacationsModel();
        $vacation = $vacationsModel->getItem($vacationId);

        if (empty($vacation)) {
            return [
                'success' => false,
                'error'   => 'Vacation not found',
            ];
        }

        $userId = $userId ?? $this->userService->getCurrentUserId();

        $isOwner = $userId !== null
            && (
                ($vacation['created_by'] ?? null) === $userId
                || ($vacation['user_id'] ?? null) === $userId
            );

        if (!$isOwner) {
            return [
                'success' => false,
                'error'   => 'Only the request owner can cancel this vacation',
            ];
        }

        $status = $vacation['status_id'] ?? '';

        if ($status === VacationsModel::STATUS_CANCELED) {
            return [
                'success' => false,
                'error'   => 'Vacation is already canceled',
            ];
        }

        // Update vacation status — keep the row for history.
        $vacationsModel->update(['status_id' => VacationsModel::STATUS_CANCELED], $vacationId);

        // Cancel the related agreement request, if any.
        $agreementRequestsModel = new AgreementRequestsModel();
        $request = $agreementRequestsModel->getRequestForEntity(
            AgreementSchemesModel::ENTITY_TYPE_VACATION,
            $vacationId
        );

        if (!empty($request)) {
            AgreementService::getInstance()->cancelRequest($request['id'], $userId);
        }

        return ['success' => true];
    }

    /**
     * Get a single vacation enriched for the request card view.
     *
     * @param string $vacationId the vacation ID
     *
     * @return null|array enriched vacation, or null if not found
     */
    public function getVacationCard(string $vacationId): ?array
    {
        $vacationsModel = new VacationsModel();
        $vacation = $vacationsModel->getItem($vacationId);

        if (empty($vacation)) {
            return null;
        }

        $typesModel = new VacationsTypesModel();
        $type = $typesModel->getItem($vacation['type_id']);

        $userModel = new UserModel();
        $users = $userModel->getListForLink(['id' => $vacation['user_id']], true, true);
        $userName = $users[$vacation['user_id']]['name'] ?? $vacation['user_id'];

        $projectName = '';
        $projectId = $vacation['project_id'] ?? null;

        if (!empty($projectId)) {
            $projectModel = new ProjectModel();
            $projects = $projectModel->getIndexedListByFilter(
                'id',
                ['id' => $projectId],
                ['*'],
                [],
                [],
                true,
            );
            $projectName = $projects[$projectId]['name'] ?? '';
        }

        return [
            'id'           => $vacation['id'],
            'user_id'      => $vacation['user_id'],
            'user_name'    => $userName,
            'type_id'      => $vacation['type_id'],
            'type_name'    => $type['name'] ?? '',
            'type_color'   => $type['color'] ?? '#9E9E9E',
            'date_start'   => $vacation['date_start'],
            'date_end'     => $vacation['date_end'],
            'half_day'     => (bool)($vacation['half_day'] ?? false),
            'status_id'    => $vacation['status_id'],
            'days_count'   => $this->vacationDaysCount($vacation['date_start'], $vacation['date_end'], !empty($vacation['half_day'])),
            'comment'      => $vacation['comment'] ?? '',
            'project_id'   => $projectId,
            'project_name' => $projectName,
            'created_by'   => $vacation['created_by'] ?? null,
        ];
    }

    /**
     * Get history for a vacation request.
     *
     * @param string $vacationId the vacation ID
     *
     * @return array history records with linked user data
     */
    public function getVacationHistory(string $vacationId): array
    {
        $agreementRequestsModel = new AgreementRequestsModel();

        // Find agreement request for this vacation
        $request = $agreementRequestsModel->getRequestForEntity(
            AgreementSchemesModel::ENTITY_TYPE_VACATION,
            $vacationId
        );

        if (empty($request)) {
            return [];
        }

        // Get history from agreement module
        $historyModel = new AgreementRequestHistoryModel();
        $history = $historyModel->getHistoryForRequest($request['id']);

        if (empty($history)) {
            return [];
        }

        // Collect unique user IDs and line IDs for enrichment
        $userIds = [];
        $lineIds = [];

        foreach ($history as $record) {
            if (!empty($record['changed_by'])) {
                $userIds[$record['changed_by']] = $record['changed_by'];
            }

            if (!empty($record['line_id_before'])) {
                $lineIds[$record['line_id_before']] = $record['line_id_before'];
            }

            if (!empty($record['line_id_after'])) {
                $lineIds[$record['line_id_after']] = $record['line_id_after'];
            }
        }

        // Load user names
        $users = [];

        if (!empty($userIds)) {
            $userModel = new UserModel();
            $users = $userModel->getListForLink(
                ['id' => ['IN', array_values($userIds), IQueryBuilder::PARAM_STR_ARRAY]],
                true
            );
        }

        // Load line names
        $lines = [];

        if (!empty($lineIds)) {
            $linesModel = new AgreementSchemeLinesModel();
            $linesList = $linesModel->getListByFilter(
                ['id' => ['IN', array_values($lineIds), IQueryBuilder::PARAM_STR_ARRAY]]
            );

            foreach ($linesList as $line) {
                $lines[$line['id']] = $line;
            }
        }

        // Enrich history records
        foreach ($history as &$record) {
            $record['changed_by_name'] = $users[$record['changed_by']]['name'] ?? null;
            $record['line_before_name'] = $lines[$record['line_id_before']]['name'] ?? null;
            $record['line_after_name'] = $lines[$record['line_id_after']]['name'] ?? null;
        }
        unset($record);

        return $history;
    }

    /**
     * Get all vacation types.
     *
     * @return array
     */
    public function getVacationTypes(): array
    {
        $typesModel = new VacationsTypesModel();

        return $typesModel->getActiveTypes();
    }

    /**
     * Get vacation statuses.
     *
     * @return array<int, string>
     */
    public function getVacationStatuses(): array
    {
        $vacationsModel = new VacationsModel();

        return $vacationsModel->getVacationStatuses();
    }

    /**
     * Set individual balance for an employee.
     *
     * @param string      $userId     employee ID
     * @param string      $typeId     vacation type ID
     * @param int         $year       year
     * @param int         $daysTotal  total days
     * @param null|string $comment    comment
     * @param null|string $adjustedBy user making the adjustment (defaults to current user)
     *
     * @return string the balance record ID
     */
    public function setEmployeeBalance(
        string $userId,
        string $typeId,
        int $year,
        int $daysTotal,
        ?string $comment = null,
        ?string $adjustedBy = null
    ): string {
        $balanceModel = new VacationsBalanceModel();
        $adjustedBy = $adjustedBy ?? $this->userService->getCurrentUserId();

        return $balanceModel->setBalance($userId, $typeId, $year, $daysTotal, $adjustedBy, $comment);
    }

    /**
     * Get all balances for an employee.
     *
     * @param string   $userId employee ID
     * @param null|int $year   year (defaults to current year)
     *
     * @return array
     */
    public function getEmployeeBalances(string $userId, ?int $year = null): array
    {
        $year = $year ?? (int)date('Y');

        return $this->getRemainingForEmployee($year, $userId);
    }

    /**
     * List all coefficient records, optionally filtered by user and/or type.
     *
     * @param null|string $userId Employee ID filter
     * @param null|string $typeId Vacation type ID filter
     *
     * @return array<int, array>
     */
    public function listCoefficients(?string $userId = null, ?string $typeId = null): array
    {
        $coefficientsModel = new VacationsCoefficientsModel();
        $filter = [];

        if ($userId !== null) {
            $filter['user_id'] = $userId;
        }

        if ($typeId !== null) {
            $filter['type_id'] = $typeId;
        }

        $rows = $coefficientsModel->getListByFilter($filter, ['*'], ['effective_from', 'DESC']);

        // Enrich with employee and type names for the UI
        $userIds = array_values(array_unique(array_column($rows, 'user_id')));
        $typeIds = array_values(array_unique(array_column($rows, 'type_id')));

        $userNames = [];

        if (!empty($userIds)) {
            $userModel = new UserModel();
            $userNames = $userModel->getListForLink(
                ['id' => ['IN', $userIds, IQueryBuilder::PARAM_STR_ARRAY]],
                true,
                true,
            );
        }

        $typeNames = [];

        if (!empty($typeIds)) {
            $typesModel = new VacationsTypesModel();
            $typeNames = $typesModel->getIndexedListByFilter(
                'id',
                ['id' => ['IN', $typeIds, IQueryBuilder::PARAM_STR_ARRAY]],
            );
        }

        return array_map(static function (array $row) use ($userNames, $typeNames): array {
            $userId = $row['user_id'];
            $typeId = $row['type_id'];
            $row['user_name'] = $userNames[$userId]['name'] ?? $userId;
            $row['type_name'] = $typeNames[$typeId]['name'] ?? '';
            $row['type_color'] = $typeNames[$typeId]['color'] ?? '#9E9E9E';

            return $row;
        }, $rows);
    }

    /**
     * Create a new coefficient record. If an existing open-ended (no effective_to) record exists
     * for the same (user, type), it is closed one day before the new record's effective_from.
     *
     * @param string      $userId        Employee ID
     * @param string      $typeId        Vacation type ID
     * @param float       $value         Coefficient value (e.g., 1.2)
     * @param string      $effectiveFrom Date in Y-m-d format
     * @param null|string $effectiveTo   Date in Y-m-d or null for open-ended
     * @param null|string $comment       Optional comment
     * @param null|string $adjustedBy    Defaults to current user
     *
     * @return string The new record ID
     */
    public function createCoefficient(
        string $userId,
        string $typeId,
        float $value,
        string $effectiveFrom,
        ?string $effectiveTo = null,
        ?string $comment = null,
        ?string $adjustedBy = null
    ): string {
        $coefficientsModel = new VacationsCoefficientsModel();
        $adjustedBy = $adjustedBy ?? $this->userService->getCurrentUserId();

        $effectiveFromObj = new \DateTimeImmutable($effectiveFrom);
        $effectiveToObj = $effectiveTo !== null ? new \DateTimeImmutable($effectiveTo) : null;

        // Close any open-ended record for the same (user, type) that starts on or before
        // the new effective_from. effective_to becomes (new effective_from - 1 day).
        $existing = $coefficientsModel->getListByFilter([
            'user_id'        => $userId,
            'type_id'        => $typeId,
            'effective_from' => ['<', $effectiveFromObj],
            'effective_to'   => ['IS NULL'],
        ]);

        $closeAt = $effectiveFromObj->modify('-1 day');

        foreach ($existing as $row) {
            $coefficientsModel->update([
                'effective_to' => $closeAt,
            ], $row['id']);
        }

        $newId = $coefficientsModel->addData([
            'user_id'        => $userId,
            'type_id'        => $typeId,
            'value'          => (string)$value,
            'effective_from' => $effectiveFromObj,
            'effective_to'   => $effectiveToObj,
            'comment'        => $comment,
            'adjusted_by'    => $adjustedBy,
        ]);

        return $newId ?? '';
    }

    /**
     * Update fields on an existing coefficient record. Only provided keys are touched.
     */
    public function updateCoefficient(string $id, array $data): bool
    {
        $coefficientsModel = new VacationsCoefficientsModel();
        $allowed = ['value', 'effective_from', 'effective_to', 'comment'];
        $payload = [];

        foreach ($allowed as $key) {
            if (!\array_key_exists($key, $data)) {
                continue;
            }

            $val = $data[$key];

            if ($key === 'effective_from' && $val !== null) {
                $val = new \DateTimeImmutable((string)$val);
            } elseif ($key === 'effective_to') {
                $val = $val !== null && $val !== '' ? new \DateTimeImmutable((string)$val) : null;
            } elseif ($key === 'value' && $val !== null) {
                $val = (string)(float)$val;
            }

            $payload[$key] = $val;
        }

        if (empty($payload)) {
            return false;
        }

        $payload['adjusted_by'] = $this->userService->getCurrentUserId();

        $coefficientsModel->update($payload, $id);

        return true;
    }

    /**
     * Delete a coefficient record by ID.
     */
    public function deleteCoefficient(string $id): bool
    {
        $coefficientsModel = new VacationsCoefficientsModel();
        $coefficientsModel->delete($id);

        return true;
    }

    /**
     * Prepare a vacation filter
     *
     * @param \DateTimeImmutable $dateFromObj
     * @param \DateTimeImmutable $dateToObj
     * @param string             $currentUserId
     * @param array              $isHeadInfo
     * @param bool               $isOfficer
     * @param bool               $isAdmin
     * @param null|array         $employeeIds
     * @param null|array         $projectIds
     * @param null|array         $vacationTypeIds
     * @param null|string        $vacationStatusId
     *
     * @return array
     */
    public function prepareFilterForVacations(
        \DateTimeImmutable $dateFromObj,
        \DateTimeImmutable $dateToObj,
        string $currentUserId,
        array $isHeadInfo,
        bool $isOfficer,
        bool $isAdmin,
        ?array $employeeIds = null,
        ?array $projectIds = null,
        ?array $vacationTypeIds = null,
        ?string $vacationStatusId = null,
    ): array {
        $filter = [
            'date_start' => ['>=', $dateFromObj],
            'date_end'   => ['<=', $dateToObj],
        ];

        if (!empty($employeeIds)) {
            $filter['user_id'] = ['IN', $employeeIds, IQueryBuilder::PARAM_STR_ARRAY];
        } elseif ($isHeadInfo['isHead'] && !$isOfficer && !$isAdmin) {
            $filter['user_id'] = ['IN', $isHeadInfo['employeesIds'], IQueryBuilder::PARAM_STR_ARRAY];
        } else {
            $filter['user_id'] = ['=', $currentUserId];
        }

        if (!empty($employeeIds) && \in_array('all', $employeeIds)) {
            unset($filter['user_id']);
        }

        if (!empty($projectIds)) {
            $filter['project_id'] = ['IN', $projectIds, IQueryBuilder::PARAM_STR_ARRAY];
        }

        if (!empty($vacationTypeIds)) {
            $filter['type_id'] = ['IN', $vacationTypeIds, IQueryBuilder::PARAM_STR_ARRAY];
        }

        if (!empty($vacationStatusId)) {
            $filter['status_id'] = ['=', $vacationStatusId];
        }

        return $filter;
    }

    /**
     * Prepare a vacation filter for the Gantt view.
     *
     * Differs from {@see prepareFilterForVacations}: dates are filtered later in PHP
     * (by overlap, not strict containment), and the default status scope is approved + pending.
     *
     * @param array         $currentUserId
     * @param array         $isHeadInfo       result of UserService::isHead()
     * @param bool          $isOfficer        result of UserService::isOfficer()
     * @param bool          $isAdmin          result of UserService::isAdmin()
     * @param null|string[] $employeeIds      filter by employee user IDs (overrides HEAD scope)
     * @param null|string[] $projectIds       filter by project IDs
     * @param null|string[] $vacationTypeIds  filter by vacation type IDs
     * @param null|string   $vacationStatusId single status; falls back to approved+pending if null
     *
     * @return array
     */
    public function prepareFilterForGantt(
        string $currentUserId,
        array $isHeadInfo,
        bool $isOfficer,
        bool $isAdmin,
        ?array $employeeIds = null,
        ?array $projectIds = null,
        ?array $vacationTypeIds = null,
        ?string $vacationStatusId = null,
    ): array {
        $filter = [];

        if (!empty($vacationStatusId)) {
            $filter['status_id'] = ['=', $vacationStatusId];
        } else {
            $filter['status_id'] = ['IN', [VacationsModel::STATUS_APPROVED, VacationsModel::STATUS_PENDING], IQueryBuilder::PARAM_STR_ARRAY];
        }

        if (!empty($employeeIds)) {
            $filter['user_id'] = ['IN', $employeeIds, IQueryBuilder::PARAM_STR_ARRAY];
        } elseif ($isHeadInfo['isHead'] && !$isOfficer && !$isAdmin) {
            $filter['user_id'] = ['IN', $isHeadInfo['employeesIds'], IQueryBuilder::PARAM_STR_ARRAY];
        } else {
            $filter['user_id'] = ['=', $currentUserId];
        }

        if (!empty($employeeIds) && \in_array('all', $employeeIds)) {
            unset($filter['user_id']);
        }

        if (!empty($projectIds)) {
            $filter['project_id'] = ['IN', $projectIds, IQueryBuilder::PARAM_STR_ARRAY];
        }

        if (!empty($vacationTypeIds)) {
            $filter['type_id'] = ['IN', $vacationTypeIds, IQueryBuilder::PARAM_STR_ARRAY];
        }

        return $filter;
    }

    /**
     * Obtain auxiliary data from vacation records
     *
     * @param array $vacations
     *
     * @return array
     */
    public function getDataFromVacationsRecords(array $vacations = []): array
    {
        $absentUsers = $projectsIds = $approvedByUsers = [];

        foreach ($vacations as $vacation) {
            $this->processVacationRecord(
                $vacation,
                $absentUsers,
                $projectsIds,
                $approvedByUsers
            );
        }

        $allEmployeeIds = array_unique(
            array_merge($absentUsers, $approvedByUsers)
        );

        return [$allEmployeeIds, $projectsIds];
    }

    private function processVacationRecord(
        array $vacation,
        array &$absentUsers,
        array &$projectsIds,
        array &$approvedByUsers
    ): void {
        $fields = [
            'user_id'     => &$absentUsers,
            'project_id'  => &$projectsIds,
            'approved_by' => &$approvedByUsers,
        ];

        foreach ($fields as $key => &$targetArray) {
            $value = $vacation[$key] ?? null;

            if (!empty($value)) {
                $targetArray[$value] ??= $value;
            }
        }
    }

    /**
     * Get the configured separator used between "used" and "limit" in the
     * report's usage columns. Falls back to "/" when not yet configured.
     */
    public function getReportSeparator(): string
    {
        $value = $this->appConfig->getValueString(
            Application::APP_ID,
            self::REPORT_SEPARATOR_KEY,
            self::REPORT_SEPARATOR_DEFAULT
        );

        // Guard against an empty stored value collapsing the column formatting.
        return $value !== '' ? $value : self::REPORT_SEPARATOR_DEFAULT;
    }

    /**
     * Persist the report usage separator. An empty string resets it to default.
     */
    public function setReportSeparator(string $separator): void
    {
        $this->appConfig->setValueString(
            Application::APP_ID,
            self::REPORT_SEPARATOR_KEY,
            $separator !== '' ? $separator : self::REPORT_SEPARATOR_DEFAULT
        );
    }

    /**
     * Get the minimum length (in days) a single paid-leave record must reach to
     * count as a mandatory leave. Falls back to 14 when not configured.
     */
    public function getMandatoryLeaveMinDays(): int
    {
        $value = $this->appConfig->getValueInt(
            Application::APP_ID,
            self::MANDATORY_LEAVE_MIN_DAYS_KEY,
            self::MANDATORY_LEAVE_MIN_DAYS_DEFAULT
        );

        // A non-positive threshold makes the status meaningless; fall back.
        return $value > 0 ? $value : self::MANDATORY_LEAVE_MIN_DAYS_DEFAULT;
    }

    /**
     * Persist the mandatory-leave minimum length. Non-positive values reset it
     * to the default.
     */
    public function setMandatoryLeaveMinDays(int $days): void
    {
        $this->appConfig->setValueInt(
            Application::APP_ID,
            self::MANDATORY_LEAVE_MIN_DAYS_KEY,
            $days > 0 ? $days : self::MANDATORY_LEAVE_MIN_DAYS_DEFAULT
        );
    }

    /**
     * Build per-employee rows for the CEO vacations report.
     *
     * One row per active employee. All "X of Y"-style columns are pre-formatted
     * strings so the temp-table pipeline (VARCHAR/LONGTEXT) can hold them.
     * Vacation-type classification follows the existing convention of matching
     * type names (see Version1060 default seeds).
     *
     * @return array<int, array{
     *     id: string,
     *     slug: string,
     *     slug_type: int,
     *     user_name: string,
     *     contract_type: string,
     *     projects: string,
     *     vacation_summary: string,
     *     mandatory_status: string,
     *     dayoff_summary: string,
     *     unpaid_used: int,
     *     sick_used: int,
     *     nearest_vacation: string,
     * }>
     */
    public function getVacationsReportRows(int $year): array
    {
        $userModel = new UserModel();
        $positionsModel = new PositionsModel();
        $contractsModel = new ContractsModel();
        $projectModel = new ProjectModel();
        $usersRolesInProjects = new UsersRolesInProjectsModel();
        $vacationsTypesModel = new VacationsTypesModel();
        $vacationsModel = new VacationsModel();

        // Active employees.
        $users = $userModel->getListByFilter([], ['id', 'name', 'lastname', 'middle_name', 'user_display_name', 'position_id', 'contract_type_id']);

        if (empty($users)) {
            return [];
        }

        $userIds = array_column($users, 'id');

        // Dictionaries: positions, contracts, vacation types.
        $positions = $positionsModel->getIndexedListByFilter('id');
        $contracts = $contractsModel->getIndexedListByFilter('id');
        $vacationTypes = $vacationsTypesModel->getIndexedListByFilter('id');

        // Map type name → id for the four well-known categories.
        $typeIdByName = [];

        foreach ($vacationTypes as $typeId => $type) {
            $typeIdByName[$type['original_name']] = $typeId;
        }

        $paidLeaveTypeId = $typeIdByName['Paid Leave'] ?? null;
        $dayOffTypeId = $typeIdByName['Day-off'] ?? null;
        $unpaidTypeId = $typeIdByName['Unpaid Leave'] ?? null;
        $sickTypeId = $typeIdByName['Sick Leave'] ?? null;

        // Projects per employee.
        $projectsByUser = $this->getActiveProjectsByUser($userIds, $usersRolesInProjects, $projectModel);

        // Balance per employee/type (used, limit, etc.).
        $balances = $this->getRemainingForEmployees($year, $userIds);

        // All approved+pending vacations in or overlapping the target year (for mandatory status + nearest).
        $yearStart = new \DateTimeImmutable($year . '-01-01');
        $yearEnd = new \DateTimeImmutable($year . '-12-31');
        $today = new \DateTimeImmutable('today');

        $vacationsInYear = $vacationsModel->getListByFilter(
            [
                'user_id'    => ['IN', $userIds, IQueryBuilder::PARAM_STR_ARRAY],
                'date_start' => ['<=', $yearEnd],
                'date_end'   => ['>=', $yearStart],
                'status_id'  => ['IN', [VacationsModel::STATUS_APPROVED, VacationsModel::STATUS_PENDING], IQueryBuilder::PARAM_STR_ARRAY],
            ],
            ['id', 'user_id', 'type_id', 'status_id', 'date_start', 'date_end']
        );

        $vacationsByUser = [];

        foreach ($vacationsInYear as $vac) {
            $vacationsByUser[$vac['user_id']][] = $vac;
        }

        $rows = [];
        $separator = $this->getReportSeparator();
        $mandatoryMinDays = $this->getMandatoryLeaveMinDays();

        foreach ($users as $user) {
            $userId = $user['id'];
            $userVacations = $vacationsByUser[$userId] ?? [];

            $row = [
                'id'               => $userId,
                'slug'             => $userId,
                'slug_type'        => 1,
                'user_name'        => $this->formatUserNameWithPosition($user, $positions),
                'contract_type'    => $this->formatContractType($user, $contracts),
                'projects'         => $projectsByUser[$userId] ?? '',
                'mandatory_status' => $this->mandatoryStatusLabel(
                    $this->computeMandatoryStatus($userVacations, $paidLeaveTypeId, $today, $mandatoryMinDays)
                ),
                'nearest_vacation' => $this->formatNearestVacation($userVacations, $vacationTypes, $today),
            ];

            foreach ($vacationTypes as $typeId => $type) {
                $row[$typeId] = $this->formatUsageSummary($balances[$userId][$typeId] ?? null, $separator);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Build a "Full name\nPosition" string for the report's user_name column.
     * Falls back to display_name when first/last/middle are all empty.
     */
    private function formatUserNameWithPosition(array $user, array $positions): string
    {
        $fullNameParts = array_filter([$user['lastname'] ?? '', $user['name'] ?? '', $user['middle_name'] ?? '']);
        $fullName = trim(implode(' ', $fullNameParts));

        if ($fullName === '') {
            $fullName = (string)($user['user_display_name'] ?? '');
        }

        $positionId = $user['position_id'] ?? null;
        $position = ($positionId && isset($positions[$positionId])) ? (string)$positions[$positionId]['name'] : '';

        return $position !== '' ? $fullName . "\n" . $position : $fullName;
    }

    private function formatContractType(array $user, array $contracts): string
    {
        $contractTypeId = $user['contract_type_id'] ?? null;

        if (!$contractTypeId || !isset($contracts[$contractTypeId])) {
            return '';
        }

        return (string)$contracts[$contractTypeId]['name'];
    }

    /**
     * Format the active projects of each user as "\n"-joined names.
     */
    private function getActiveProjectsByUser(
        array $userIds,
        UsersRolesInProjectsModel $usersRolesInProjects,
        ProjectModel $projectModel
    ): array {
        if (empty($userIds)) {
            return [];
        }

        $roles = $usersRolesInProjects->getListByFilter(
            ['user_id' => ['IN', $userIds, IQueryBuilder::PARAM_STR_ARRAY]],
            ['user_id', 'project_id']
        );

        if (empty($roles)) {
            return [];
        }

        $projectIds = array_values(array_unique(array_column($roles, 'project_id')));
        $projects = $projectModel->getIndexedListByFilter(
            'id',
            ['id' => ['IN', $projectIds, IQueryBuilder::PARAM_STR_ARRAY]]
        );

        $namesByUser = [];

        foreach ($roles as $role) {
            $projectId = $role['project_id'];

            if (!isset($projects[$projectId])) {
                continue;
            }

            $userId = $role['user_id'];
            $name = (string)$projects[$projectId]['name'];

            $namesByUser[$userId][$name] = true;
        }

        $result = [];

        foreach ($namesByUser as $userId => $names) {
            $result[$userId] = implode("\n", array_keys($names));
        }

        return $result;
    }

    /**
     * Format a usage cell for the report.
     *
     *  - no balance at all                  → ''
     *  - limit is 0 (no cap for this type)  → just the used count ("5"), or '' when nothing used
     *  - otherwise                          → "used <sep> limit" (e.g. "5 / 38")
     *
     * @param null|array $balance   balance row for one user+type
     * @param string     $separator configurable separator (defaults to "/")
     */
    private function formatUsageSummary(?array $balance, string $separator = self::REPORT_SEPARATOR_DEFAULT): string
    {
        if (!$balance) {
            return '';
        }

        $used = (float)($balance['used'] ?? 0);
        $limit = (float)($balance['limit'] ?? 0);

        // No limit configured for this type: show only the used count.
        if ($limit == 0.0) {
            return $used == 0.0 ? '' : $this->formatDays($used);
        }

        return $this->formatDays($used) . ' ' . $separator . ' ' . $this->formatDays($limit);
    }

    /**
     * Format a day count, keeping a ".5" half-day but dropping trailing ".0".
     * E.g. 3.0 → "3", 2.5 → "2.5".
     */
    private function formatDays(float $days): string
    {
        return rtrim(rtrim(number_format($days, 1, '.', ''), '0'), '.');
    }

    /**
     * Compute the mandatory-leave status for the year.
     *
     * Rule: any paid leave (single record) >= $minDays calendar days.
     *   - 'completed'    — an approved record fully in the past.
     *   - 'planned'      — an approved or pending record that hasn't ended yet.
     *   - 'not_planned'  — neither.
     *
     * @param int $minDays minimum length to qualify as a mandatory leave (from settings)
     */
    private function computeMandatoryStatus(
        array $userVacations,
        ?string $paidLeaveTypeId,
        \DateTimeImmutable $today,
        int $minDays
    ): string {
        if (!$paidLeaveTypeId) {
            return 'not_planned';
        }

        $hasCompleted = false;
        $hasPlanned = false;

        foreach ($userVacations as $vac) {
            if ($vac['type_id'] !== $paidLeaveTypeId) {
                continue;
            }

            $days = $this->vacationDaysCount($vac['date_start'], $vac['date_end'], !empty($vac['half_day']));

            if ($days < $minDays) {
                continue;
            }

            $isApproved = ($vac['status_id'] === VacationsModel::STATUS_APPROVED);

            if ($isApproved && $end < $today) {
                $hasCompleted = true;
            } else {
                $hasPlanned = true;
            }
        }

        if ($hasCompleted) {
            return 'completed';
        }

        if ($hasPlanned) {
            return 'planned';
        }

        return 'not_planned';
    }

    /**
     * Translate a mandatory-status code into a localized label. This label is
     * stored as the cell value so the table and the Excel/CSV export both show
     * human text; the frontend derives the badge color back from the label.
     */
    private function mandatoryStatusLabel(string $code): string
    {
        $keys = [
            'completed'   => 'Mandatory leave taken',
            'planned'     => 'Mandatory leave planned',
            'not_planned' => 'Mandatory leave not planned',
        ];

        return $this->translateService->getTranslate($keys[$code] ?? $keys['not_planned']);
    }

    /**
     * Format the soonest upcoming approved vacation as three lines:
     *   1) dd.MM.yyyy - dd.MM.yyyy
     *   2) <type name>
     *   3) "in N days" (N days until the vacation starts)
     *
     * The frontend renders this cell with white-space: pre-line so each
     * fragment shows on its own row.
     */
    private function formatNearestVacation(array $userVacations, array $vacationTypes, \DateTimeImmutable $today): string
    {
        $candidates = [];

        foreach ($userVacations as $vac) {
            if ($vac['status_id'] !== VacationsModel::STATUS_APPROVED) {
                continue;
            }

            $end = new \DateTimeImmutable($vac['date_end']);

            if ($end < $today) {
                continue;
            }

            $candidates[] = $vac;
        }

        if (empty($candidates)) {
            return '';
        }

        usort($candidates, static function ($a, $b) {
            return strcmp($a['date_start'], $b['date_start']);
        });

        $next = $candidates[0];
        $start = new \DateTimeImmutable($next['date_start']);
        $end = new \DateTimeImmutable($next['date_end']);
        $typeName = $vacationTypes[$next['type_id']]['name'] ?? '';

        $lines = [$start->format('d.m.Y') . ' - ' . $end->format('d.m.Y')];

        if ($typeName !== '') {
            $lines[] = $typeName;
        }

        if ($start > $today) {
            $daysUntil = (int)$today->diff($start)->days;
            $lines[] = $this->translateService->getTranslate('in %s days', [(string)$daysUntil]);
        }

        return implode("\n", $lines);
    }

    /**
     * Get all vacations of a single employee, newest-first, for the report's
     * "Details" side panel. Returns enriched entries with type/status names.
     *
     * @return array<int, array{
     *     id: string,
     *     date_start: string,
     *     date_end: string,
     *     days_count: int,
     *     type_name: string,
     *     status_id: string,
     *     project_name: ?string,
     *     comment: ?string,
     * }>
     */
    public function getEmployeeVacationsList(string $userId): array
    {
        $vacationsModel = new VacationsModel();
        $vacationsTypesModel = new VacationsTypesModel();
        $projectModel = new ProjectModel();

        $vacations = $vacationsModel->getListByFilter(
            ['user_id' => $userId],
            ['*'],
            ['date_start', 'DESC']
        );

        if (empty($vacations)) {
            return [];
        }

        $types = $vacationsTypesModel->getIndexedListByFilter('id');
        $projectIds = array_filter(array_unique(array_column($vacations, 'project_id')));

        $projects = [];

        if (!empty($projectIds)) {
            $projects = $projectModel->getIndexedListByFilter(
                'id',
                ['id' => ['IN', $projectIds, IQueryBuilder::PARAM_STR_ARRAY]]
            );
        }

        $result = [];

        foreach ($vacations as $vac) {
            $result[] = [
                'id'           => $vac['id'],
                'date_start'   => $vac['date_start'],
                'date_end'     => $vac['date_end'],
                'half_day'     => (bool)($vac['half_day'] ?? false),
                'days_count'   => $this->vacationDaysCount($vac['date_start'], $vac['date_end'], !empty($vac['half_day'])),
                'type_name'    => $types[$vac['type_id']]['name'] ?? '',
                'status_id'    => $vac['status_id'],
                'project_name' => isset($projects[$vac['project_id'] ?? null]) ? $projects[$vac['project_id']]['name'] : null,
                'comment'      => $vac['comment'] ?? null,
            ];
        }

        return $result;
    }
}
