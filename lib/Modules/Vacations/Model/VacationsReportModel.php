<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Vacations\Model;

use OCA\Done\Models\BaseModel;
use OCA\Done\Modules\Vacations\Service\VacationsService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Virtual model that produces aggregated per-employee vacation report rows.
 *
 * Doesn't own a real DB table — overrides getListByFilter to feed computed
 * rows into the dynamic-table pipeline (TempTableModel::getDataForTable),
 * which copies them into a temp table and applies filter/sort over the result.
 *
 * The "year" of the report is passed via the systemFilter array.
 */
class VacationsReportModel extends BaseModel
{
    public string $table = 'done_vacations_report_virtual';
    public string $modelTitle = 'Vacations report';
    public string $modelName = 'vacationsReportModel';
    public string $dbTableComment = 'Virtual model for the CEO vacations report. No real DB table.';

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Synthetic identifier (uses oc_done_users_data.id of the employee).',
        ],
        'user_name' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Employee',
            'db_comment' => 'Employee full name with their position on a second line.',
            'show'       => true,
        ],
        'contract_type' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Contract type',
            'db_comment' => 'Active contract type label (e.g. employment contract, civil contract, sole proprietor).',
            'show'       => true,
        ],
        'projects' => [
            'type'       => IQueryBuilder::PARAM_LOB,
            'title'      => 'Projects',
            'db_comment' => 'Names of projects the employee is a member of, one per line.',
            'show'       => true,
        ],
        'mandatory_status' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Mandatory vacation',
            'db_comment' => 'Mandatory leave status: completed / planned / not_planned (paid leave reaching the configured minimum length).',
            'show'       => true,
        ],
        'nearest_vacation' => [
            'type'       => IQueryBuilder::PARAM_LOB,
            'title'      => 'Nearest vacation',
            'db_comment' => 'Composite multiline text (date range, type, days until start) for the nearest upcoming approved leave, or empty.',
            'show'       => true,
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->addVacationTypesToModel();
    }

    /**
     * @override
     *
     * Skip any real DB query: produce computed rows through VacationsService.
     * `$filter` typically carries the `year` from the controller's systemFilter.
     */
    public function getListByFilter(
        array $filter = [],
        array $fields = ['*'],
        array $orderBy = [],
        array $additionalOrderBy = [],
        bool $needDeleted = false,
    ): array {
        $year = isset($filter['year']) ? (int)$filter['year'] : (int)date('Y');

        $service = VacationsService::getInstance();

        return $service->getVacationsReportRows($year);
    }

    /**
     * @override — nothing to compare against, computed rows are returned verbatim.
     */
    public function compareDataWithCustomFields(array $data = []): array
    {
        return $data;
    }

    public function addVacationTypesToModel(): void
    {
        $vacationsTypesModel = new VacationsTypesModel();
        $vacationTypes = $vacationsTypesModel->getIndexedListByFilter();

        foreach ($vacationTypes as $vacationType) {
            $this->fields[$vacationType['id']] = [
                'type'  => IQueryBuilder::PARAM_STR,
                'title' => $vacationType['name'],
            ];
        }
    }
}
