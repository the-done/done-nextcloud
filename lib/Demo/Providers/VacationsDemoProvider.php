<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Demo\Providers;

use OCA\Done\Demo\AbstractDemoProvider;
use OCP\IRequest;

/**
 * Fake, read-only demo data for the Vacations module. English only.
 * Does not reference any OCA\Done\Modules\Vacations class - every shape below
 * is hardcoded to mirror the real controller/service responses.
 */
class VacationsDemoProvider extends AbstractDemoProvider
{
    /** @var string Paid Leave vacation type id used across the fake dataset. */
    private const TYPE_PAID = 'demo-type-paid';

    /** @var string Day-off vacation type id. */
    private const TYPE_DAY_OFF = 'demo-type-day-off';

    /** @var string Unpaid Leave vacation type id. */
    private const TYPE_UNPAID = 'demo-type-unpaid';

    /** @var string Sick Leave vacation type id. */
    private const TYPE_SICK = 'demo-type-sick';

    protected function readMethods(): array
    {
        return [
            'getVacationsForGantt'                 => fn (IRequest $r): array => $this->gantt(),
            'getVacationsTableData'                => fn (IRequest $r): array => $this->requestsTable(),
            'getRemainingDays'                     => fn (IRequest $r): array => $this->remainingDays(),
            'getEmployeeBalances'                  => fn (IRequest $r): array => $this->employeeBalances(),
            'getUpcomingPaidVacations'             => fn (IRequest $r): array => $this->upcoming(),
            'getVacation'                          => fn (IRequest $r): array => $this->vacationCard(),
            'getVacationHistory'                   => fn (IRequest $r): array => $this->history(),
            'getVacationTypes'                     => fn (IRequest $r): array => $this->types(),
            'getVacationTypeSettings'              => fn (IRequest $r): array => $this->typeSettings(),
            'getCoefficients'                      => fn (IRequest $r): array => $this->coefficients(),
            'getVacationStatuses'                  => fn (IRequest $r): array => $this->statuses(),
            'getVacationsReportTableData'          => fn (IRequest $r): array => $this->reportTable(),
            'getEmployeeVacationsList'             => fn (IRequest $r): array => $this->employeeVacationsList(),
            'getReportSeparator'                   => static fn (IRequest $r): array => ['separator' => '/'],
            'getMandatoryLeaveMinDays'             => static fn (IRequest $r): array => ['min_days' => 14],
            'getProjectsOptionsForVacations'       => fn (IRequest $r): array => $this->projects(),
            'getProjectsOptionsForVacationsReport' => fn (IRequest $r): array => $this->projects(),
            'getEmployeesOptionsForVacations'      => fn (IRequest $r): array => $this->employees(),
        ];
    }

    /**
     * Mirror VacationsService::getVacationsForGantt: a flat list of employees,
     * each with a vacations[] array of enriched entries.
     */
    private function gantt(): array
    {
        return [
            [
                'user_id'   => 'demo-user-1',
                'user_name' => 'John Carter, Backend Developer',
                'vacations' => [
                    [
                        'id'           => 'demo-vac-1',
                        'date_start'   => '2026-07-06',
                        'date_end'     => '2026-07-17',
                        'type_id'      => self::TYPE_PAID,
                        'type_name'    => 'Paid Leave',
                        'type_color'   => '#4CAF50',
                        'status_id'    => 'approved',
                        'half_day'     => false,
                        'days_count'   => 10,
                        'comment'      => 'Summer vacation',
                        'project_id'   => 'demo-proj-1',
                        'project_name' => 'Apollo',
                    ],
                ],
            ],
            [
                'user_id'   => 'demo-user-2',
                'user_name' => 'Emma Reid, Product Manager',
                'vacations' => [
                    [
                        'id'           => 'demo-vac-2',
                        'date_start'   => '2026-07-20',
                        'date_end'     => '2026-07-24',
                        'type_id'      => self::TYPE_DAY_OFF,
                        'type_name'    => 'Day-off',
                        'type_color'   => '#2196F3',
                        'status_id'    => 'pending',
                        'half_day'     => false,
                        'days_count'   => 5,
                        'comment'      => 'Personal days',
                        'project_id'   => 'demo-proj-2',
                        'project_name' => 'Gemini',
                    ],
                ],
            ],
            [
                'user_id'   => 'demo-user-3',
                'user_name' => 'Sophie Lang, Team Lead',
                'vacations' => [
                    [
                        'id'           => 'demo-vac-4',
                        'date_start'   => '2026-08-03',
                        'date_end'     => '2026-08-14',
                        'type_id'      => self::TYPE_PAID,
                        'type_name'    => 'Paid Leave',
                        'type_color'   => '#4CAF50',
                        'status_id'    => 'approved',
                        'half_day'     => false,
                        'days_count'   => 10,
                        'comment'      => 'Family trip',
                        'project_id'   => 'demo-proj-3',
                        'project_name' => 'Orion',
                    ],
                ],
            ],
            [
                'user_id'   => 'demo-user-4',
                'user_name' => 'Michael Chen, Frontend Developer',
                'vacations' => [
                    [
                        'id'           => 'demo-vac-5',
                        'date_start'   => '2026-07-13',
                        'date_end'     => '2026-07-24',
                        'type_id'      => self::TYPE_PAID,
                        'type_name'    => 'Paid Leave',
                        'type_color'   => '#4CAF50',
                        'status_id'    => 'approved',
                        'half_day'     => false,
                        'days_count'   => 10,
                        'comment'      => 'Trip abroad',
                        'project_id'   => 'demo-proj-2',
                        'project_name' => 'Gemini',
                    ],
                    [
                        'id'           => 'demo-vac-6',
                        'date_start'   => '2026-09-07',
                        'date_end'     => '2026-09-08',
                        'type_id'      => self::TYPE_DAY_OFF,
                        'type_name'    => 'Day-off',
                        'type_color'   => '#2196F3',
                        'status_id'    => 'pending',
                        'half_day'     => false,
                        'days_count'   => 2,
                        'comment'      => 'Moving apartment',
                        'project_id'   => 'demo-proj-2',
                        'project_name' => 'Gemini',
                    ],
                ],
            ],
            [
                'user_id'   => 'demo-user-5',
                'user_name' => 'Olivia Bennett, UX Designer',
                'vacations' => [
                    [
                        'id'           => 'demo-vac-7',
                        'date_start'   => '2026-08-17',
                        'date_end'     => '2026-08-28',
                        'type_id'      => self::TYPE_PAID,
                        'type_name'    => 'Paid Leave',
                        'type_color'   => '#4CAF50',
                        'status_id'    => 'approved',
                        'half_day'     => false,
                        'days_count'   => 10,
                        'comment'      => 'Wedding and honeymoon',
                        'project_id'   => 'demo-proj-4',
                        'project_name' => 'Mercury',
                    ],
                ],
            ],
            [
                'user_id'   => 'demo-user-6',
                'user_name' => 'Daniel Foster, DevOps Engineer',
                'vacations' => [
                    [
                        'id'           => 'demo-vac-8',
                        'date_start'   => '2026-07-27',
                        'date_end'     => '2026-07-31',
                        'type_id'      => self::TYPE_SICK,
                        'type_name'    => 'Sick Leave',
                        'type_color'   => '#FF9800',
                        'status_id'    => 'approved',
                        'half_day'     => false,
                        'days_count'   => 5,
                        'comment'      => 'Medical leave',
                        'project_id'   => 'demo-proj-1',
                        'project_name' => 'Apollo',
                    ],
                ],
            ],
            [
                'user_id'   => 'demo-user-7',
                'user_name' => 'Ava Morgan, QA Engineer',
                'vacations' => [
                    [
                        'id'           => 'demo-vac-9',
                        'date_start'   => '2026-09-14',
                        'date_end'     => '2026-09-25',
                        'type_id'      => self::TYPE_PAID,
                        'type_name'    => 'Paid Leave',
                        'type_color'   => '#4CAF50',
                        'status_id'    => 'pending',
                        'half_day'     => false,
                        'days_count'   => 10,
                        'comment'      => 'Autumn holiday',
                        'project_id'   => 'demo-proj-5',
                        'project_name' => 'Titan',
                    ],
                ],
            ],
            [
                'user_id'   => 'demo-user-8',
                'user_name' => 'Liam Walsh, Data Analyst',
                'vacations' => [
                    [
                        'id'           => 'demo-vac-10',
                        'date_start'   => '2026-08-24',
                        'date_end'     => '2026-08-28',
                        'type_id'      => self::TYPE_UNPAID,
                        'type_name'    => 'Unpaid Leave',
                        'type_color'   => '#9E9E9E',
                        'status_id'    => 'approved',
                        'half_day'     => false,
                        'days_count'   => 5,
                        'comment'      => 'Personal matters',
                        'project_id'   => 'demo-proj-3',
                        'project_name' => 'Orion',
                    ],
                ],
            ],
            [
                'user_id'   => 'demo-user-9',
                'user_name' => 'Grace Nolan, HR Manager',
                'vacations' => [
                    [
                        'id'           => 'demo-vac-11',
                        'date_start'   => '2026-07-06',
                        'date_end'     => '2026-07-10',
                        'type_id'      => self::TYPE_PAID,
                        'type_name'    => 'Paid Leave',
                        'type_color'   => '#4CAF50',
                        'status_id'    => 'approved',
                        'half_day'     => false,
                        'days_count'   => 5,
                        'comment'      => 'Short break',
                        'project_id'   => 'demo-proj-4',
                        'project_name' => 'Mercury',
                    ],
                ],
            ],
            [
                'user_id'   => 'demo-user-10',
                'user_name' => 'Noah Pierce, Backend Developer',
                'vacations' => [
                    [
                        'id'           => 'demo-vac-12',
                        'date_start'   => '2026-09-01',
                        'date_end'     => '2026-09-12',
                        'type_id'      => self::TYPE_PAID,
                        'type_name'    => 'Paid Leave',
                        'type_color'   => '#4CAF50',
                        'status_id'    => 'approved',
                        'half_day'     => false,
                        'days_count'   => 10,
                        'comment'      => 'Diving trip',
                        'project_id'   => 'demo-proj-1',
                        'project_name' => 'Apollo',
                    ],
                ],
            ],
            [
                'user_id'   => 'demo-user-11',
                'user_name' => 'Isabella Ross, Marketing Specialist',
                'vacations' => [
                    [
                        'id'           => 'demo-vac-13',
                        'date_start'   => '2026-08-11',
                        'date_end'     => '2026-08-11',
                        'type_id'      => self::TYPE_DAY_OFF,
                        'type_name'    => 'Day-off',
                        'type_color'   => '#2196F3',
                        'status_id'    => 'approved',
                        'half_day'     => false,
                        'days_count'   => 1,
                        'comment'      => 'Doctor appointment',
                        'project_id'   => 'demo-proj-5',
                        'project_name' => 'Titan',
                    ],
                ],
            ],
            [
                'user_id'   => 'demo-user-12',
                'user_name' => 'Ethan Brooks, Solutions Architect',
                'vacations' => [
                    [
                        'id'           => 'demo-vac-14',
                        'date_start'   => '2026-08-31',
                        'date_end'     => '2026-09-11',
                        'type_id'      => self::TYPE_PAID,
                        'type_name'    => 'Paid Leave',
                        'type_color'   => '#4CAF50',
                        'status_id'    => 'approved',
                        'half_day'     => false,
                        'days_count'   => 10,
                        'comment'      => 'Sabbatical prep',
                        'project_id'   => 'demo-proj-2',
                        'project_name' => 'Gemini',
                    ],
                ],
            ],
        ];
    }

    /**
     * Mirror VacationsService::getVacationsTableData: grouped by month (01-12),
     * then by employee id => { name, remaining_by_type, list[] }.
     */
    private function requestsTable(): array
    {
        return [
            '07' => [
                'demo-user-1' => [
                    'name'              => 'John Carter, Backend Developer',
                    'remaining_by_type' => $this->remainingDays(),
                    'list'              => [
                        [
                            'id'                     => 'demo-vac-1',
                            'type_id'                => self::TYPE_PAID,
                            'user_id'                => 'demo-user-1',
                            'type_name'              => 'Paid Leave',
                            'type_color'             => '#4CAF50',
                            'date_start'             => '2026-07-06',
                            'date_end'               => '2026-07-17',
                            'half_day'               => false,
                            'status_id'              => 'approved',
                            'days_count'             => 10,
                            'comment'                => 'Summer vacation',
                            'project_id'             => 'demo-proj-1',
                            'created_by'             => 'demo-user-1',
                            'agreement_request_id'   => 'demo-req-1',
                            'agreement_status'       => 'approved',
                            'agreement_current_line' => null,
                        ],
                    ],
                ],
                'demo-user-2' => [
                    'name'              => 'Emma Reid, Product Manager',
                    'remaining_by_type' => $this->remainingDays(),
                    'list'              => [
                        [
                            'id'                     => 'demo-vac-2',
                            'type_id'                => self::TYPE_DAY_OFF,
                            'user_id'                => 'demo-user-2',
                            'type_name'              => 'Day-off',
                            'type_color'             => '#2196F3',
                            'date_start'             => '2026-07-20',
                            'date_end'               => '2026-07-24',
                            'half_day'               => false,
                            'status_id'              => 'pending',
                            'days_count'             => 5,
                            'comment'                => 'Personal days',
                            'project_id'             => 'demo-proj-2',
                            'created_by'             => 'demo-user-2',
                            'agreement_request_id'   => 'demo-req-2',
                            'agreement_status'       => 'pending',
                            'agreement_current_line' => 'demo-line-1',
                        ],
                    ],
                ],
                'demo-user-4' => [
                    'name'              => 'Michael Chen, Frontend Developer',
                    'remaining_by_type' => $this->remainingDays(),
                    'list'              => [
                        [
                            'id'                     => 'demo-vac-5',
                            'type_id'                => self::TYPE_PAID,
                            'user_id'                => 'demo-user-4',
                            'type_name'              => 'Paid Leave',
                            'type_color'             => '#4CAF50',
                            'date_start'             => '2026-07-13',
                            'date_end'               => '2026-07-24',
                            'half_day'               => false,
                            'status_id'              => 'approved',
                            'days_count'             => 10,
                            'comment'                => 'Trip abroad',
                            'project_id'             => 'demo-proj-2',
                            'created_by'             => 'demo-user-4',
                            'agreement_request_id'   => 'demo-req-3',
                            'agreement_status'       => 'approved',
                            'agreement_current_line' => null,
                        ],
                    ],
                ],
                'demo-user-6' => [
                    'name'              => 'Daniel Foster, DevOps Engineer',
                    'remaining_by_type' => $this->remainingDays(),
                    'list'              => [
                        [
                            'id'                     => 'demo-vac-8',
                            'type_id'                => self::TYPE_SICK,
                            'user_id'                => 'demo-user-6',
                            'type_name'              => 'Sick Leave',
                            'type_color'             => '#FF9800',
                            'date_start'             => '2026-07-27',
                            'date_end'               => '2026-07-31',
                            'half_day'               => false,
                            'status_id'              => 'approved',
                            'days_count'             => 5,
                            'comment'                => 'Medical leave',
                            'project_id'             => 'demo-proj-1',
                            'created_by'             => 'demo-user-6',
                            'agreement_request_id'   => 'demo-req-4',
                            'agreement_status'       => 'approved',
                            'agreement_current_line' => null,
                        ],
                    ],
                ],
                'demo-user-9' => [
                    'name'              => 'Grace Nolan, HR Manager',
                    'remaining_by_type' => $this->remainingDays(),
                    'list'              => [
                        [
                            'id'                     => 'demo-vac-11',
                            'type_id'                => self::TYPE_PAID,
                            'user_id'                => 'demo-user-9',
                            'type_name'              => 'Paid Leave',
                            'type_color'             => '#4CAF50',
                            'date_start'             => '2026-07-06',
                            'date_end'               => '2026-07-10',
                            'half_day'               => false,
                            'status_id'              => 'approved',
                            'days_count'             => 5,
                            'comment'                => 'Short break',
                            'project_id'             => 'demo-proj-4',
                            'created_by'             => 'demo-user-9',
                            'agreement_request_id'   => 'demo-req-5',
                            'agreement_status'       => 'approved',
                            'agreement_current_line' => null,
                        ],
                    ],
                ],
            ],
            '08' => [
                'demo-user-3' => [
                    'name'              => 'Sophie Lang, Team Lead',
                    'remaining_by_type' => $this->remainingDays(),
                    'list'              => [
                        [
                            'id'                     => 'demo-vac-4',
                            'type_id'                => self::TYPE_PAID,
                            'user_id'                => 'demo-user-3',
                            'type_name'              => 'Paid Leave',
                            'type_color'             => '#4CAF50',
                            'date_start'             => '2026-08-03',
                            'date_end'               => '2026-08-14',
                            'half_day'               => false,
                            'status_id'              => 'approved',
                            'days_count'             => 10,
                            'comment'                => 'Family trip',
                            'project_id'             => 'demo-proj-3',
                            'created_by'             => 'demo-user-3',
                            'agreement_request_id'   => 'demo-req-6',
                            'agreement_status'       => 'approved',
                            'agreement_current_line' => null,
                        ],
                    ],
                ],
                'demo-user-5' => [
                    'name'              => 'Olivia Bennett, UX Designer',
                    'remaining_by_type' => $this->remainingDays(),
                    'list'              => [
                        [
                            'id'                     => 'demo-vac-7',
                            'type_id'                => self::TYPE_PAID,
                            'user_id'                => 'demo-user-5',
                            'type_name'              => 'Paid Leave',
                            'type_color'             => '#4CAF50',
                            'date_start'             => '2026-08-17',
                            'date_end'               => '2026-08-28',
                            'half_day'               => false,
                            'status_id'              => 'approved',
                            'days_count'             => 10,
                            'comment'                => 'Wedding and honeymoon',
                            'project_id'             => 'demo-proj-4',
                            'created_by'             => 'demo-user-5',
                            'agreement_request_id'   => 'demo-req-7',
                            'agreement_status'       => 'approved',
                            'agreement_current_line' => null,
                        ],
                    ],
                ],
                'demo-user-8' => [
                    'name'              => 'Liam Walsh, Data Analyst',
                    'remaining_by_type' => $this->remainingDays(),
                    'list'              => [
                        [
                            'id'                     => 'demo-vac-10',
                            'type_id'                => self::TYPE_UNPAID,
                            'user_id'                => 'demo-user-8',
                            'type_name'              => 'Unpaid Leave',
                            'type_color'             => '#9E9E9E',
                            'date_start'             => '2026-08-24',
                            'date_end'               => '2026-08-28',
                            'half_day'               => false,
                            'status_id'              => 'approved',
                            'days_count'             => 5,
                            'comment'                => 'Personal matters',
                            'project_id'             => 'demo-proj-3',
                            'created_by'             => 'demo-user-8',
                            'agreement_request_id'   => 'demo-req-8',
                            'agreement_status'       => 'approved',
                            'agreement_current_line' => null,
                        ],
                    ],
                ],
                'demo-user-11' => [
                    'name'              => 'Isabella Ross, Marketing Specialist',
                    'remaining_by_type' => $this->remainingDays(),
                    'list'              => [
                        [
                            'id'                     => 'demo-vac-13',
                            'type_id'                => self::TYPE_DAY_OFF,
                            'user_id'                => 'demo-user-11',
                            'type_name'              => 'Day-off',
                            'type_color'             => '#2196F3',
                            'date_start'             => '2026-08-11',
                            'date_end'               => '2026-08-11',
                            'half_day'               => false,
                            'status_id'              => 'approved',
                            'days_count'             => 1,
                            'comment'                => 'Doctor appointment',
                            'project_id'             => 'demo-proj-5',
                            'created_by'             => 'demo-user-11',
                            'agreement_request_id'   => 'demo-req-9',
                            'agreement_status'       => 'approved',
                            'agreement_current_line' => null,
                        ],
                    ],
                ],
                'demo-user-12' => [
                    'name'              => 'Ethan Brooks, Solutions Architect',
                    'remaining_by_type' => $this->remainingDays(),
                    'list'              => [
                        [
                            'id'                     => 'demo-vac-14',
                            'type_id'                => self::TYPE_PAID,
                            'user_id'                => 'demo-user-12',
                            'type_name'              => 'Paid Leave',
                            'type_color'             => '#4CAF50',
                            'date_start'             => '2026-08-31',
                            'date_end'               => '2026-09-11',
                            'half_day'               => false,
                            'status_id'              => 'approved',
                            'days_count'             => 10,
                            'comment'                => 'Sabbatical prep',
                            'project_id'             => 'demo-proj-2',
                            'created_by'             => 'demo-user-12',
                            'agreement_request_id'   => 'demo-req-10',
                            'agreement_status'       => 'approved',
                            'agreement_current_line' => null,
                        ],
                    ],
                ],
            ],
            '09' => [
                'demo-user-4' => [
                    'name'              => 'Michael Chen, Frontend Developer',
                    'remaining_by_type' => $this->remainingDays(),
                    'list'              => [
                        [
                            'id'                     => 'demo-vac-6',
                            'type_id'                => self::TYPE_DAY_OFF,
                            'user_id'                => 'demo-user-4',
                            'type_name'              => 'Day-off',
                            'type_color'             => '#2196F3',
                            'date_start'             => '2026-09-07',
                            'date_end'               => '2026-09-08',
                            'half_day'               => false,
                            'status_id'              => 'pending',
                            'days_count'             => 2,
                            'comment'                => 'Moving apartment',
                            'project_id'             => 'demo-proj-2',
                            'created_by'             => 'demo-user-4',
                            'agreement_request_id'   => 'demo-req-11',
                            'agreement_status'       => 'pending',
                            'agreement_current_line' => 'demo-line-1',
                        ],
                    ],
                ],
                'demo-user-7' => [
                    'name'              => 'Ava Morgan, QA Engineer',
                    'remaining_by_type' => $this->remainingDays(),
                    'list'              => [
                        [
                            'id'                     => 'demo-vac-9',
                            'type_id'                => self::TYPE_PAID,
                            'user_id'                => 'demo-user-7',
                            'type_name'              => 'Paid Leave',
                            'type_color'             => '#4CAF50',
                            'date_start'             => '2026-09-14',
                            'date_end'               => '2026-09-25',
                            'half_day'               => false,
                            'status_id'              => 'pending',
                            'days_count'             => 10,
                            'comment'                => 'Autumn holiday',
                            'project_id'             => 'demo-proj-5',
                            'created_by'             => 'demo-user-7',
                            'agreement_request_id'   => 'demo-req-12',
                            'agreement_status'       => 'pending',
                            'agreement_current_line' => 'demo-line-1',
                        ],
                    ],
                ],
                'demo-user-10' => [
                    'name'              => 'Noah Pierce, Backend Developer',
                    'remaining_by_type' => $this->remainingDays(),
                    'list'              => [
                        [
                            'id'                     => 'demo-vac-12',
                            'type_id'                => self::TYPE_PAID,
                            'user_id'                => 'demo-user-10',
                            'type_name'              => 'Paid Leave',
                            'type_color'             => '#4CAF50',
                            'date_start'             => '2026-09-01',
                            'date_end'               => '2026-09-12',
                            'half_day'               => false,
                            'status_id'              => 'approved',
                            'days_count'             => 10,
                            'comment'                => 'Diving trip',
                            'project_id'             => 'demo-proj-1',
                            'created_by'             => 'demo-user-10',
                            'agreement_request_id'   => 'demo-req-13',
                            'agreement_status'       => 'approved',
                            'agreement_current_line' => null,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Mirror VacationsService::getRemainingForEmployee: a map indexed by
     * type_id, each entry carrying limit/used/planned/remaining figures.
     */
    private function remainingDays(): array
    {
        return [
            self::TYPE_PAID => [
                'type_id'         => self::TYPE_PAID,
                'type_name'       => 'Paid Leave',
                'type_color'      => '#4CAF50',
                'limit'           => 28.0,
                'annual_limit'    => 28.0,
                'carryover'       => 0.0,
                'used'            => 10,
                'planned'         => 0,
                'remaining'       => 18.0,
                'display_warning' => false,
                'is_custom_limit' => false,
            ],
            self::TYPE_DAY_OFF => [
                'type_id'         => self::TYPE_DAY_OFF,
                'type_name'       => 'Day-off',
                'type_color'      => '#2196F3',
                'limit'           => 0.0,
                'annual_limit'    => 0.0,
                'carryover'       => 0.0,
                'used'            => 3,
                'planned'         => 2,
                'remaining'       => 0.0,
                'display_warning' => false,
                'is_custom_limit' => false,
            ],
            self::TYPE_UNPAID => [
                'type_id'         => self::TYPE_UNPAID,
                'type_name'       => 'Unpaid Leave',
                'type_color'      => '#9E9E9E',
                'limit'           => 0.0,
                'annual_limit'    => 0.0,
                'carryover'       => 0.0,
                'used'            => 0,
                'planned'         => 0,
                'remaining'       => 0.0,
                'display_warning' => false,
                'is_custom_limit' => false,
            ],
            self::TYPE_SICK => [
                'type_id'         => self::TYPE_SICK,
                'type_name'       => 'Sick Leave',
                'type_color'      => '#FF9800',
                'limit'           => 0.0,
                'annual_limit'    => 0.0,
                'carryover'       => 0.0,
                'used'            => 4,
                'planned'         => 0,
                'remaining'       => 0.0,
                'display_warning' => false,
                'is_custom_limit' => false,
            ],
        ];
    }

    /**
     * Mirror VacationsService::getRemainingForEmployees: indexed by user_id,
     * then by type_id.
     */
    private function employeeBalances(): array
    {
        return [
            'demo-user-1'  => $this->remainingDays(),
            'demo-user-2'  => $this->remainingDays(),
            'demo-user-3'  => $this->remainingDays(),
            'demo-user-4'  => $this->remainingDays(),
            'demo-user-5'  => $this->remainingDays(),
            'demo-user-6'  => $this->remainingDays(),
            'demo-user-7'  => $this->remainingDays(),
            'demo-user-8'  => $this->remainingDays(),
            'demo-user-9'  => $this->remainingDays(),
            'demo-user-10' => $this->remainingDays(),
            'demo-user-11' => $this->remainingDays(),
            'demo-user-12' => $this->remainingDays(),
        ];
    }

    /**
     * Mirror VacationsService::getUpcomingPaidVacations.
     */
    private function upcoming(): array
    {
        return [
            [
                'id'         => 'demo-vac-3',
                'date_start' => '2026-08-10',
                'date_end'   => '2026-08-21',
                'days_count' => 10,
                'type_name'  => 'Paid Leave',
                'is_active'  => false,
            ],
            [
                'id'         => 'demo-vac-4',
                'date_start' => '2026-08-03',
                'date_end'   => '2026-08-14',
                'days_count' => 10,
                'type_name'  => 'Paid Leave',
                'is_active'  => true,
            ],
            [
                'id'         => 'demo-vac-7',
                'date_start' => '2026-08-17',
                'date_end'   => '2026-08-28',
                'days_count' => 10,
                'type_name'  => 'Paid Leave',
                'is_active'  => false,
            ],
            [
                'id'         => 'demo-vac-14',
                'date_start' => '2026-08-31',
                'date_end'   => '2026-09-11',
                'days_count' => 10,
                'type_name'  => 'Paid Leave',
                'is_active'  => false,
            ],
            [
                'id'         => 'demo-vac-12',
                'date_start' => '2026-09-01',
                'date_end'   => '2026-09-12',
                'days_count' => 10,
                'type_name'  => 'Paid Leave',
                'is_active'  => false,
            ],
            [
                'id'         => 'demo-vac-9',
                'date_start' => '2026-09-14',
                'date_end'   => '2026-09-25',
                'days_count' => 10,
                'type_name'  => 'Paid Leave',
                'is_active'  => false,
            ],
            [
                'id'         => 'demo-vac-15',
                'date_start' => '2026-09-28',
                'date_end'   => '2026-10-09',
                'days_count' => 10,
                'type_name'  => 'Paid Leave',
                'is_active'  => false,
            ],
            [
                'id'         => 'demo-vac-16',
                'date_start' => '2026-10-05',
                'date_end'   => '2026-10-16',
                'days_count' => 10,
                'type_name'  => 'Paid Leave',
                'is_active'  => false,
            ],
            [
                'id'         => 'demo-vac-17',
                'date_start' => '2026-11-02',
                'date_end'   => '2026-11-13',
                'days_count' => 10,
                'type_name'  => 'Paid Leave',
                'is_active'  => false,
            ],
            [
                'id'         => 'demo-vac-18',
                'date_start' => '2026-12-21',
                'date_end'   => '2026-12-31',
                'days_count' => 8,
                'type_name'  => 'Paid Leave',
                'is_active'  => false,
            ],
        ];
    }

    /**
     * Mirror VacationsService::getVacationCard: a single enriched vacation.
     */
    private function vacationCard(): array
    {
        return [
            'id'           => 'demo-vac-1',
            'user_id'      => 'demo-user-1',
            'user_name'    => 'John Carter, Backend Developer',
            'type_id'      => self::TYPE_PAID,
            'type_name'    => 'Paid Leave',
            'type_color'   => '#4CAF50',
            'date_start'   => '2026-07-06',
            'date_end'     => '2026-07-17',
            'half_day'     => false,
            'status_id'    => 'approved',
            'days_count'   => 10,
            'comment'      => 'Summer vacation',
            'project_id'   => 'demo-proj-1',
            'project_name' => 'Apollo',
            'created_by'   => 'demo-user-1',
        ];
    }

    /**
     * Mirror VacationsService::getEmployeeVacationsList: a flat, newest-first
     * list of an employee's vacations for the report "Details" panel.
     */
    private function employeeVacationsList(): array
    {
        return [
            [
                'id'           => 'demo-vac-3',
                'date_start'   => '2026-08-10',
                'date_end'     => '2026-08-21',
                'half_day'     => false,
                'days_count'   => 10,
                'type_name'    => 'Paid Leave',
                'status_id'    => 'approved',
                'project_name' => 'Apollo',
                'comment'      => 'Summer vacation',
            ],
            [
                'id'           => 'demo-vac-1',
                'date_start'   => '2026-07-06',
                'date_end'     => '2026-07-17',
                'half_day'     => false,
                'days_count'   => 10,
                'type_name'    => 'Paid Leave',
                'status_id'    => 'approved',
                'project_name' => 'Apollo',
                'comment'      => 'Spring leave',
            ],
            [
                'id'           => 'demo-vac-h1',
                'date_start'   => '2026-05-04',
                'date_end'     => '2026-05-08',
                'half_day'     => false,
                'days_count'   => 5,
                'type_name'    => 'Day-off',
                'status_id'    => 'approved',
                'project_name' => 'Apollo',
                'comment'      => 'May break',
            ],
            [
                'id'           => 'demo-vac-h2',
                'date_start'   => '2026-03-16',
                'date_end'     => '2026-03-20',
                'half_day'     => false,
                'days_count'   => 5,
                'type_name'    => 'Paid Leave',
                'status_id'    => 'approved',
                'project_name' => 'Gemini',
                'comment'      => 'Ski trip',
            ],
            [
                'id'           => 'demo-vac-h3',
                'date_start'   => '2026-02-09',
                'date_end'     => '2026-02-13',
                'half_day'     => false,
                'days_count'   => 5,
                'type_name'    => 'Sick Leave',
                'status_id'    => 'approved',
                'project_name' => 'Apollo',
                'comment'      => 'Flu recovery',
            ],
            [
                'id'           => 'demo-vac-h4',
                'date_start'   => '2025-12-22',
                'date_end'     => '2025-12-31',
                'half_day'     => false,
                'days_count'   => 8,
                'type_name'    => 'Paid Leave',
                'status_id'    => 'approved',
                'project_name' => 'Apollo',
                'comment'      => 'Winter holidays',
            ],
            [
                'id'           => 'demo-vac-h5',
                'date_start'   => '2025-10-06',
                'date_end'     => '2025-10-10',
                'half_day'     => false,
                'days_count'   => 5,
                'type_name'    => 'Day-off',
                'status_id'    => 'approved',
                'project_name' => 'Orion',
                'comment'      => 'Long weekend',
            ],
            [
                'id'           => 'demo-vac-h6',
                'date_start'   => '2025-08-11',
                'date_end'     => '2025-08-22',
                'half_day'     => false,
                'days_count'   => 10,
                'type_name'    => 'Paid Leave',
                'status_id'    => 'approved',
                'project_name' => 'Mercury',
                'comment'      => 'Coastal vacation',
            ],
            [
                'id'           => 'demo-vac-h7',
                'date_start'   => '2025-06-02',
                'date_end'     => '2025-06-06',
                'half_day'     => false,
                'days_count'   => 5,
                'type_name'    => 'Unpaid Leave',
                'status_id'    => 'approved',
                'project_name' => 'Apollo',
                'comment'      => 'Personal leave',
            ],
            [
                'id'           => 'demo-vac-h8',
                'date_start'   => '2025-04-14',
                'date_end'     => '2025-04-18',
                'half_day'     => false,
                'days_count'   => 5,
                'type_name'    => 'Day-off',
                'status_id'    => 'rejected',
                'project_name' => 'Apollo',
                'comment'      => 'Requested but declined',
            ],
        ];
    }

    /**
     * Mirror VacationsTypesModel::getActiveTypes: full type rows.
     */
    private function types(): array
    {
        return [
            ['id' => self::TYPE_PAID, 'name' => 'Paid Leave', 'color' => '#4CAF50', 'sort' => 1],
            ['id' => self::TYPE_DAY_OFF, 'name' => 'Day-off', 'color' => '#2196F3', 'sort' => 2],
            ['id' => self::TYPE_UNPAID, 'name' => 'Unpaid Leave', 'color' => '#9E9E9E', 'sort' => 3],
            ['id' => self::TYPE_SICK, 'name' => 'Sick Leave', 'color' => '#FF9800', 'sort' => 4],
        ];
    }

    /**
     * Mirror VacationsController::getVacationTypeSettings, i.e.
     * VacationsTypeSettingsModel::getListByFilter: raw rows of the
     * done_vac_type_settings table, one per configured vacation type.
     */
    private function typeSettings(): array
    {
        return [
            [
                'id'              => 'demo-type-setting-1',
                'type_id'         => self::TYPE_PAID,
                'value'           => 28,
                'display_warning' => false,
                'created_at'      => '2026-01-01 00:00:00',
                'updated_at'      => '2026-01-01 00:00:00',
            ],
            [
                'id'              => 'demo-type-setting-2',
                'type_id'         => self::TYPE_DAY_OFF,
                'value'           => 0,
                'display_warning' => true,
                'created_at'      => '2026-01-01 00:00:00',
                'updated_at'      => '2026-01-01 00:00:00',
            ],
            [
                'id'              => 'demo-type-setting-3',
                'type_id'         => self::TYPE_UNPAID,
                'value'           => 0,
                'display_warning' => false,
                'created_at'      => '2026-01-01 00:00:00',
                'updated_at'      => '2026-01-01 00:00:00',
            ],
            [
                'id'              => 'demo-type-setting-4',
                'type_id'         => self::TYPE_SICK,
                'value'           => 0,
                'display_warning' => false,
                'created_at'      => '2026-01-01 00:00:00',
                'updated_at'      => '2026-01-01 00:00:00',
            ],
        ];
    }

    /**
     * Mirror VacationsService::listCoefficients: raw done_vac_coefficients
     * rows (value is a string) enriched with user_name/type_name/type_color,
     * ordered by effective_from DESC.
     */
    private function coefficients(): array
    {
        return [
            [
                'id'             => 'demo-coef-6',
                'user_id'        => 'demo-user-7',
                'type_id'        => self::TYPE_PAID,
                'value'          => '1.00',
                'effective_from' => '2026-02-01',
                'effective_to'   => null,
                'comment'        => 'Standard accrual',
                'adjusted_by'    => 'demo-user-3',
                'created_at'     => '2026-02-01 09:00:00',
                'updated_at'     => '2026-02-01 09:00:00',
                'user_name'      => 'Ava Morgan, QA Engineer',
                'type_name'      => 'Paid Leave',
                'type_color'     => '#4CAF50',
            ],
            [
                'id'             => 'demo-coef-1',
                'user_id'        => 'demo-user-1',
                'type_id'        => self::TYPE_PAID,
                'value'          => '1.20',
                'effective_from' => '2026-01-01',
                'effective_to'   => null,
                'comment'        => 'Northern region multiplier',
                'adjusted_by'    => 'demo-user-3',
                'created_at'     => '2026-01-01 09:00:00',
                'updated_at'     => '2026-01-01 09:00:00',
                'user_name'      => 'John Carter, Backend Developer',
                'type_name'      => 'Paid Leave',
                'type_color'     => '#4CAF50',
            ],
            [
                'id'             => 'demo-coef-3',
                'user_id'        => 'demo-user-4',
                'type_id'        => self::TYPE_PAID,
                'value'          => '1.15',
                'effective_from' => '2026-01-01',
                'effective_to'   => null,
                'comment'        => 'Regional adjustment',
                'adjusted_by'    => 'demo-user-9',
                'created_at'     => '2026-01-01 09:00:00',
                'updated_at'     => '2026-01-01 09:00:00',
                'user_name'      => 'Michael Chen, Frontend Developer',
                'type_name'      => 'Paid Leave',
                'type_color'     => '#4CAF50',
            ],
            [
                'id'             => 'demo-coef-4',
                'user_id'        => 'demo-user-5',
                'type_id'        => self::TYPE_PAID,
                'value'          => '1.00',
                'effective_from' => '2026-01-01',
                'effective_to'   => null,
                'comment'        => 'Standard accrual',
                'adjusted_by'    => 'demo-user-9',
                'created_at'     => '2026-01-01 09:00:00',
                'updated_at'     => '2026-01-01 09:00:00',
                'user_name'      => 'Olivia Bennett, UX Designer',
                'type_name'      => 'Paid Leave',
                'type_color'     => '#4CAF50',
            ],
            [
                'id'             => 'demo-coef-9',
                'user_id'        => 'demo-user-10',
                'type_id'        => self::TYPE_PAID,
                'value'          => '1.20',
                'effective_from' => '2026-01-01',
                'effective_to'   => null,
                'comment'        => 'Northern region multiplier',
                'adjusted_by'    => 'demo-user-9',
                'created_at'     => '2026-01-01 09:00:00',
                'updated_at'     => '2026-01-01 09:00:00',
                'user_name'      => 'Noah Pierce, Backend Developer',
                'type_name'      => 'Paid Leave',
                'type_color'     => '#4CAF50',
            ],
            [
                'id'             => 'demo-coef-7',
                'user_id'        => 'demo-user-8',
                'type_id'        => self::TYPE_PAID,
                'value'          => '1.10',
                'effective_from' => '2025-09-01',
                'effective_to'   => null,
                'comment'        => 'Seniority bonus',
                'adjusted_by'    => 'demo-user-9',
                'created_at'     => '2025-09-01 09:00:00',
                'updated_at'     => '2025-09-01 09:00:00',
                'user_name'      => 'Liam Walsh, Data Analyst',
                'type_name'      => 'Paid Leave',
                'type_color'     => '#4CAF50',
            ],
            [
                'id'             => 'demo-coef-10',
                'user_id'        => 'demo-user-12',
                'type_id'        => self::TYPE_PAID,
                'value'          => '1.25',
                'effective_from' => '2025-07-01',
                'effective_to'   => null,
                'comment'        => 'Lead architect adjustment',
                'adjusted_by'    => 'demo-user-3',
                'created_at'     => '2025-07-01 09:00:00',
                'updated_at'     => '2025-07-01 09:00:00',
                'user_name'      => 'Ethan Brooks, Solutions Architect',
                'type_name'      => 'Paid Leave',
                'type_color'     => '#4CAF50',
            ],
            [
                'id'             => 'demo-coef-2',
                'user_id'        => 'demo-user-2',
                'type_id'        => self::TYPE_PAID,
                'value'          => '1.00',
                'effective_from' => '2025-06-01',
                'effective_to'   => '2025-12-31',
                'comment'        => 'Standard accrual',
                'adjusted_by'    => 'demo-user-3',
                'created_at'     => '2025-06-01 09:00:00',
                'updated_at'     => '2025-06-01 09:00:00',
                'user_name'      => 'Emma Reid, Product Manager',
                'type_name'      => 'Paid Leave',
                'type_color'     => '#4CAF50',
            ],
            [
                'id'             => 'demo-coef-5',
                'user_id'        => 'demo-user-6',
                'type_id'        => self::TYPE_PAID,
                'value'          => '1.30',
                'effective_from' => '2025-03-01',
                'effective_to'   => null,
                'comment'        => 'Hazard multiplier',
                'adjusted_by'    => 'demo-user-9',
                'created_at'     => '2025-03-01 09:00:00',
                'updated_at'     => '2025-03-01 09:00:00',
                'user_name'      => 'Daniel Foster, DevOps Engineer',
                'type_name'      => 'Paid Leave',
                'type_color'     => '#4CAF50',
            ],
            [
                'id'             => 'demo-coef-8',
                'user_id'        => 'demo-user-9',
                'type_id'        => self::TYPE_PAID,
                'value'          => '1.00',
                'effective_from' => '2024-01-01',
                'effective_to'   => '2025-12-31',
                'comment'        => 'Legacy rate',
                'adjusted_by'    => 'demo-user-3',
                'created_at'     => '2024-01-01 09:00:00',
                'updated_at'     => '2024-01-01 09:00:00',
                'user_name'      => 'Grace Nolan, HR Manager',
                'type_name'      => 'Paid Leave',
                'type_color'     => '#4CAF50',
            ],
        ];
    }

    /**
     * Mirror AgreementRequestsModel::getStatusOptions (used by
     * VacationsModel::getVacationStatuses).
     */
    private function statuses(): array
    {
        return [
            ['id' => 'pending', 'name' => 'Pending', 'color' => '#FFC107'],
            ['id' => 'approved', 'name' => 'Approved', 'color' => '#4CAF50'],
            ['id' => 'rejected', 'name' => 'Rejected', 'color' => '#F44336'],
            ['id' => 'returned', 'name' => 'Returned', 'color' => '#2196F3'],
            ['id' => 'canceled', 'name' => 'Canceled', 'color' => '#9E9E9E'],
        ];
    }

    /**
     * Mirror AgreementRequestHistoryModel records enriched by
     * VacationsService::getVacationHistory (changed_by_name/line_*_name added).
     */
    private function history(): array
    {
        return [
            [
                'id'               => 'demo-hist-1',
                'request_id'       => 'demo-req-1',
                'event_type'       => 'created',
                'status_before'    => null,
                'status_after'     => 'pending',
                'line_id_before'   => null,
                'line_id_after'    => 'demo-line-1',
                'changed_by'       => 'demo-user-1',
                'comment'          => null,
                'created_at'       => '2026-07-01 09:00:00',
                'changed_by_name'  => 'John Carter, Backend Developer',
                'line_before_name' => null,
                'line_after_name'  => 'Team Lead approval',
            ],
            [
                'id'               => 'demo-hist-2',
                'request_id'       => 'demo-req-1',
                'event_type'       => 'status_changed',
                'status_before'    => 'pending',
                'status_after'     => 'approved',
                'line_id_before'   => 'demo-line-1',
                'line_id_after'    => null,
                'changed_by'       => 'demo-user-3',
                'comment'          => 'Approved',
                'created_at'       => '2026-07-02 14:30:00',
                'changed_by_name'  => 'Sophie Lang, Team Lead',
                'line_before_name' => 'Team Lead approval',
                'line_after_name'  => null,
            ],
            [
                'id'               => 'demo-hist-3',
                'request_id'       => 'demo-req-2',
                'event_type'       => 'created',
                'status_before'    => null,
                'status_after'     => 'pending',
                'line_id_before'   => null,
                'line_id_after'    => 'demo-line-1',
                'changed_by'       => 'demo-user-2',
                'comment'          => null,
                'created_at'       => '2026-07-15 08:30:00',
                'changed_by_name'  => 'Emma Reid, Product Manager',
                'line_before_name' => null,
                'line_after_name'  => 'Team Lead approval',
            ],
            [
                'id'               => 'demo-hist-4',
                'request_id'       => 'demo-req-3',
                'event_type'       => 'created',
                'status_before'    => null,
                'status_after'     => 'pending',
                'line_id_before'   => null,
                'line_id_after'    => 'demo-line-1',
                'changed_by'       => 'demo-user-4',
                'comment'          => null,
                'created_at'       => '2026-07-08 09:10:00',
                'changed_by_name'  => 'Michael Chen, Frontend Developer',
                'line_before_name' => null,
                'line_after_name'  => 'Team Lead approval',
            ],
            [
                'id'               => 'demo-hist-5',
                'request_id'       => 'demo-req-3',
                'event_type'       => 'status_changed',
                'status_before'    => 'pending',
                'status_after'     => 'approved',
                'line_id_before'   => 'demo-line-1',
                'line_id_after'    => null,
                'changed_by'       => 'demo-user-3',
                'comment'          => 'Looks good',
                'created_at'       => '2026-07-09 16:00:00',
                'changed_by_name'  => 'Sophie Lang, Team Lead',
                'line_before_name' => 'Team Lead approval',
                'line_after_name'  => null,
            ],
            [
                'id'               => 'demo-hist-6',
                'request_id'       => 'demo-req-4',
                'event_type'       => 'created',
                'status_before'    => null,
                'status_after'     => 'pending',
                'line_id_before'   => null,
                'line_id_after'    => 'demo-line-2',
                'changed_by'       => 'demo-user-6',
                'comment'          => null,
                'created_at'       => '2026-07-25 07:45:00',
                'changed_by_name'  => 'Daniel Foster, DevOps Engineer',
                'line_before_name' => null,
                'line_after_name'  => 'HR approval',
            ],
            [
                'id'               => 'demo-hist-7',
                'request_id'       => 'demo-req-4',
                'event_type'       => 'status_changed',
                'status_before'    => 'pending',
                'status_after'     => 'approved',
                'line_id_before'   => 'demo-line-2',
                'line_id_after'    => null,
                'changed_by'       => 'demo-user-9',
                'comment'          => 'Medical certificate received',
                'created_at'       => '2026-07-26 10:20:00',
                'changed_by_name'  => 'Grace Nolan, HR Manager',
                'line_before_name' => 'HR approval',
                'line_after_name'  => null,
            ],
            [
                'id'               => 'demo-hist-8',
                'request_id'       => 'demo-req-6',
                'event_type'       => 'created',
                'status_before'    => null,
                'status_after'     => 'pending',
                'line_id_before'   => null,
                'line_id_after'    => 'demo-line-2',
                'changed_by'       => 'demo-user-3',
                'comment'          => null,
                'created_at'       => '2026-07-28 12:00:00',
                'changed_by_name'  => 'Sophie Lang, Team Lead',
                'line_before_name' => null,
                'line_after_name'  => 'HR approval',
            ],
            [
                'id'               => 'demo-hist-9',
                'request_id'       => 'demo-req-6',
                'event_type'       => 'status_changed',
                'status_before'    => 'pending',
                'status_after'     => 'approved',
                'line_id_before'   => 'demo-line-2',
                'line_id_after'    => null,
                'changed_by'       => 'demo-user-9',
                'comment'          => 'Approved',
                'created_at'       => '2026-07-29 09:30:00',
                'changed_by_name'  => 'Grace Nolan, HR Manager',
                'line_before_name' => 'HR approval',
                'line_after_name'  => null,
            ],
            [
                'id'               => 'demo-hist-10',
                'request_id'       => 'demo-req-12',
                'event_type'       => 'created',
                'status_before'    => null,
                'status_after'     => 'pending',
                'line_id_before'   => null,
                'line_id_after'    => 'demo-line-1',
                'changed_by'       => 'demo-user-7',
                'comment'          => null,
                'created_at'       => '2026-09-01 10:00:00',
                'changed_by_name'  => 'Ava Morgan, QA Engineer',
                'line_before_name' => null,
                'line_after_name'  => 'Team Lead approval',
            ],
            [
                'id'               => 'demo-hist-11',
                'request_id'       => 'demo-req-12',
                'event_type'       => 'status_changed',
                'status_before'    => 'pending',
                'status_after'     => 'returned',
                'line_id_before'   => 'demo-line-1',
                'line_id_after'    => 'demo-line-1',
                'changed_by'       => 'demo-user-3',
                'comment'          => 'Please adjust dates',
                'created_at'       => '2026-09-02 13:15:00',
                'changed_by_name'  => 'Sophie Lang, Team Lead',
                'line_before_name' => 'Team Lead approval',
                'line_after_name'  => 'Team Lead approval',
            ],
            [
                'id'               => 'demo-hist-12',
                'request_id'       => 'demo-req-12',
                'event_type'       => 'status_changed',
                'status_before'    => 'returned',
                'status_after'     => 'pending',
                'line_id_before'   => 'demo-line-1',
                'line_id_after'    => 'demo-line-1',
                'changed_by'       => 'demo-user-7',
                'comment'          => 'Dates updated',
                'created_at'       => '2026-09-03 09:00:00',
                'changed_by_name'  => 'Ava Morgan, QA Engineer',
                'line_before_name' => 'Team Lead approval',
                'line_after_name'  => 'Team Lead approval',
            ],
        ];
    }

    /**
     * Mirror ProjectModel::getListByFilter([...], ['id', 'name']): option list.
     */
    private function projects(): array
    {
        return [
            ['id' => 'demo-proj-1', 'name' => 'Apollo'],
            ['id' => 'demo-proj-2', 'name' => 'Gemini'],
            ['id' => 'demo-proj-3', 'name' => 'Orion'],
            ['id' => 'demo-proj-4', 'name' => 'Mercury'],
            ['id' => 'demo-proj-5', 'name' => 'Titan'],
        ];
    }

    /**
     * Mirror the employees option list from getEmployeesOptionsForVacations:
     * an "All employees" sentinel followed by "Full name, position" entries.
     */
    private function employees(): array
    {
        return [
            ['id' => 'all', 'name' => 'All employees'],
            ['id' => 'demo-user-1', 'name' => 'John Carter, Backend Developer'],
            ['id' => 'demo-user-2', 'name' => 'Emma Reid, Product Manager'],
            ['id' => 'demo-user-3', 'name' => 'Sophie Lang, Team Lead'],
            ['id' => 'demo-user-4', 'name' => 'Michael Chen, Frontend Developer'],
            ['id' => 'demo-user-5', 'name' => 'Olivia Bennett, UX Designer'],
            ['id' => 'demo-user-6', 'name' => 'Daniel Foster, DevOps Engineer'],
            ['id' => 'demo-user-7', 'name' => 'Ava Morgan, QA Engineer'],
            ['id' => 'demo-user-8', 'name' => 'Liam Walsh, Data Analyst'],
            ['id' => 'demo-user-9', 'name' => 'Grace Nolan, HR Manager'],
            ['id' => 'demo-user-10', 'name' => 'Noah Pierce, Backend Developer'],
            ['id' => 'demo-user-11', 'name' => 'Isabella Ross, Marketing Specialist'],
            ['id' => 'demo-user-12', 'name' => 'Ethan Brooks, Solutions Architect'],
        ];
    }

    /**
     * Mirror TableService::getTableDataForEntity (lib/Service/TableService.php):
     * returns exactly { allColumnsOrdering, data, settings }.
     *
     * allColumnsOrdering is a sequential list of column descriptors
     * ({ key, hidden, title, rules, info }) as produced by sortFields();
     * the rows live under `data` (NOT `rows`), each keyed by field name.
     */
    private function reportTable(): array
    {
        return [
            'allColumnsOrdering' => [
                ['key' => 'user_name', 'hidden' => false, 'title' => $this->tr('Employee'), 'rules' => null, 'info' => null],
                ['key' => 'contract_type', 'hidden' => false, 'title' => $this->tr('Contract type'), 'rules' => null, 'info' => null],
                ['key' => 'projects', 'hidden' => false, 'title' => $this->tr('Projects'), 'rules' => null, 'info' => null],
                ['key' => 'mandatory_status', 'hidden' => false, 'title' => $this->tr('Mandatory vacation'), 'rules' => null, 'info' => null],
                ['key' => 'nearest_vacation', 'hidden' => false, 'title' => $this->tr('Nearest vacation'), 'rules' => null, 'info' => null],
            ],
            'data' => [
                [
                    'id'               => 'demo-user-1',
                    'slug'             => 'demo-user-1',
                    'slug_type'        => 1,
                    'user_name'        => "John Carter\nBackend Developer",
                    'contract_type'    => 'Full-time',
                    'projects'         => "Apollo\nGemini",
                    'mandatory_status' => 'Mandatory leave taken',
                    'nearest_vacation' => "10.08.2026 - 21.08.2026\nPaid Leave\nin 18 days",
                ],
                [
                    'id'               => 'demo-user-2',
                    'slug'             => 'demo-user-2',
                    'slug_type'        => 1,
                    'user_name'        => "Emma Reid\nProduct Manager",
                    'contract_type'    => 'Full-time',
                    'projects'         => 'Gemini',
                    'mandatory_status' => 'Mandatory leave planned',
                    'nearest_vacation' => "01.09.2026 - 12.09.2026\nPaid Leave\nin 40 days",
                ],
                [
                    'id'               => 'demo-user-3',
                    'slug'             => 'demo-user-3',
                    'slug_type'        => 1,
                    'user_name'        => "Sophie Lang\nTeam Lead",
                    'contract_type'    => 'Full-time',
                    'projects'         => 'Orion',
                    'mandatory_status' => 'Mandatory leave taken',
                    'nearest_vacation' => "03.08.2026 - 14.08.2026\nPaid Leave\nin 3 days",
                ],
                [
                    'id'               => 'demo-user-4',
                    'slug'             => 'demo-user-4',
                    'slug_type'        => 1,
                    'user_name'        => "Michael Chen\nFrontend Developer",
                    'contract_type'    => 'Full-time',
                    'projects'         => 'Gemini',
                    'mandatory_status' => 'Mandatory leave taken',
                    'nearest_vacation' => "07.09.2026 - 08.09.2026\nDay-off\nin 27 days",
                ],
                [
                    'id'               => 'demo-user-5',
                    'slug'             => 'demo-user-5',
                    'slug_type'        => 1,
                    'user_name'        => "Olivia Bennett\nUX Designer",
                    'contract_type'    => 'Part-time',
                    'projects'         => 'Mercury',
                    'mandatory_status' => 'Mandatory leave taken',
                    'nearest_vacation' => "17.08.2026 - 28.08.2026\nPaid Leave\nin 6 days",
                ],
                [
                    'id'               => 'demo-user-6',
                    'slug'             => 'demo-user-6',
                    'slug_type'        => 1,
                    'user_name'        => "Daniel Foster\nDevOps Engineer",
                    'contract_type'    => 'Full-time',
                    'projects'         => 'Apollo',
                    'mandatory_status' => 'Mandatory leave not planned',
                    'nearest_vacation' => '-',
                ],
                [
                    'id'               => 'demo-user-7',
                    'slug'             => 'demo-user-7',
                    'slug_type'        => 1,
                    'user_name'        => "Ava Morgan\nQA Engineer",
                    'contract_type'    => 'Full-time',
                    'projects'         => 'Titan',
                    'mandatory_status' => 'Mandatory leave planned',
                    'nearest_vacation' => "14.09.2026 - 25.09.2026\nPaid Leave\nin 34 days",
                ],
                [
                    'id'               => 'demo-user-8',
                    'slug'             => 'demo-user-8',
                    'slug_type'        => 1,
                    'user_name'        => "Liam Walsh\nData Analyst",
                    'contract_type'    => 'Contractor',
                    'projects'         => 'Orion',
                    'mandatory_status' => 'Mandatory leave not planned',
                    'nearest_vacation' => "24.08.2026 - 28.08.2026\nUnpaid Leave\nin 13 days",
                ],
                [
                    'id'               => 'demo-user-9',
                    'slug'             => 'demo-user-9',
                    'slug_type'        => 1,
                    'user_name'        => "Grace Nolan\nHR Manager",
                    'contract_type'    => 'Full-time',
                    'projects'         => 'Mercury',
                    'mandatory_status' => 'Mandatory leave planned',
                    'nearest_vacation' => '-',
                ],
                [
                    'id'               => 'demo-user-10',
                    'slug'             => 'demo-user-10',
                    'slug_type'        => 1,
                    'user_name'        => "Noah Pierce\nBackend Developer",
                    'contract_type'    => 'Full-time',
                    'projects'         => 'Apollo',
                    'mandatory_status' => 'Mandatory leave taken',
                    'nearest_vacation' => "01.09.2026 - 12.09.2026\nPaid Leave\nin 21 days",
                ],
                [
                    'id'               => 'demo-user-11',
                    'slug'             => 'demo-user-11',
                    'slug_type'        => 1,
                    'user_name'        => "Isabella Ross\nMarketing Specialist",
                    'contract_type'    => 'Part-time',
                    'projects'         => 'Titan',
                    'mandatory_status' => 'Mandatory leave not planned',
                    'nearest_vacation' => "11.08.2026 - 11.08.2026\nDay-off\nin 0 days",
                ],
                [
                    'id'               => 'demo-user-12',
                    'slug'             => 'demo-user-12',
                    'slug_type'        => 1,
                    'user_name'        => "Ethan Brooks\nSolutions Architect",
                    'contract_type'    => 'Contractor',
                    'projects'         => 'Gemini',
                    'mandatory_status' => 'Mandatory leave taken',
                    'nearest_vacation' => "31.08.2026 - 11.09.2026\nPaid Leave\nin 20 days",
                ],
            ],
            'settings' => [
                'tableColumnView'        => [],
                'tableSortColumns'       => [],
                'tableSortWithinColumns' => [],
                'tableFilter'            => [],
            ],
        ];
    }
}
