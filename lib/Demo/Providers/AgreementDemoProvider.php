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
 * Fake, read-only demo data for the Agreement module.
 *
 * Shapes mirror the real AgreementController / AgreementService / models so the
 * demo pages render exactly like the full version. Everything here is hardcoded
 * English demo data; no lib/Modules/* classes are imported.
 */
class AgreementDemoProvider extends AbstractDemoProvider
{
    protected function readMethods(): array
    {
        return [
            'getMyRequests'            => fn (IRequest $r): array => $this->pendingRequests(),
            'getMyRequestsHistory'     => fn (IRequest $r): array => $this->completedRequests(),
            'getRequest'               => fn (IRequest $r): array => $this->requestWithDetails(),
            'getRequestHistory'        => fn (IRequest $r): array => $this->history(),
            'getOptions'               => fn (IRequest $r): array => $this->options(),
            'getRequestStatuses'       => fn (IRequest $r): array => $this->statusOptions(),
            'getApproverTypes'         => fn (IRequest $r): array => $this->approverTypeOptions(),
            'getEntityTypes'           => fn (IRequest $r): array => $this->entityTypeOptions(),
            'getActionTypes'           => fn (IRequest $r): array => $this->actionTypeOptions(),
            'getProjectsForFilter'     => fn (IRequest $r): array => $this->projects(),
            'getUsersForApprover'      => fn (IRequest $r): array => $this->users(),
            'getRolesForApprover'      => fn (IRequest $r): array => $this->roles(),
            'hasActiveScheme'          => static fn (IRequest $r): array => ['has_active_scheme' => true],
            'getSchemes'               => fn (IRequest $r): array => $this->schemesList(),
            'getScheme'                => fn (IRequest $r): array => $this->schemeDetails((string)$r->getParam('slug')),
            'getSchemeAssignments'     => fn (IRequest $r): array => $this->schemeAssignments((string)$r->getParam('scheme_slug')),
            'getEntitiesForAssignment' => fn (IRequest $r): array => $this->entitiesForAssignment((string)$r->getParam('sub_entity_type')),
        ];
    }

    /**
     * Pending requests awaiting the current user's approval.
     *
     * Mirrors AgreementRequestsModel::getRequestsForApprover(): these are raw
     * request rows, so cached_data is a JSON-encoded string (not yet decoded).
     */
    private function pendingRequests(): array
    {
        return [
            [
                'id'              => 'demo-req-1',
                'entity_type'     => 'vacation',
                'entity_id'       => 'demo-vac-1',
                'scheme_id'       => 'demo-scheme-1',
                'current_line_id' => 'demo-line-1',
                'status'          => 'pending',
                'cached_title'    => 'John Carter, backend developer',
                'cached_data'     => json_encode([
                    'employee'   => 'John Carter, backend developer',
                    'type'       => 'Paid Leave',
                    'date_start' => '2026-08-10',
                    'date_end'   => '2026-08-21',
                    'days'       => 10,
                    'comment'    => 'Summer vacation',
                ]),
                'deadline_at'  => '2026-07-28T12:00:00+00:00',
                'created_by'   => 'demo-user-1',
                'created_at'   => '2026-07-20T09:00:00+00:00',
                'updated_at'   => '2026-07-20T09:00:00+00:00',
                'completed_at' => null,
                'project_id'   => 'demo-proj-1',
            ],
            [
                'id'              => 'demo-req-2',
                'entity_type'     => 'vacation',
                'entity_id'       => 'demo-vac-2',
                'scheme_id'       => 'demo-scheme-1',
                'current_line_id' => 'demo-line-1',
                'status'          => 'pending',
                'cached_title'    => 'Emily Stone, project manager',
                'cached_data'     => json_encode([
                    'employee'   => 'Emily Stone, project manager',
                    'type'       => 'Unpaid Leave',
                    'date_start' => '2026-09-01',
                    'date_end'   => '2026-09-05',
                    'days'       => 5,
                    'comment'    => 'Family trip',
                ]),
                'deadline_at'  => null,
                'created_by'   => 'demo-user-2',
                'created_at'   => '2026-07-21T14:30:00+00:00',
                'updated_at'   => '2026-07-21T14:30:00+00:00',
                'completed_at' => null,
                'project_id'   => 'demo-proj-2',
            ],
            [
                'id'              => 'demo-req-5',
                'entity_type'     => 'vacation',
                'entity_id'       => 'demo-vac-5',
                'scheme_id'       => 'demo-scheme-3',
                'current_line_id' => 'demo-line-4',
                'status'          => 'pending',
                'cached_title'    => 'James Patel, frontend developer',
                'cached_data'     => json_encode([
                    'employee'   => 'James Patel, frontend developer',
                    'type'       => 'Paid Leave',
                    'date_start' => '2026-09-14',
                    'date_end'   => '2026-09-25',
                    'days'       => 10,
                    'comment'    => 'Trip abroad',
                ]),
                'deadline_at'  => '2026-08-15T12:00:00+00:00',
                'created_by'   => 'demo-user-7',
                'created_at'   => '2026-08-01T11:10:00+00:00',
                'updated_at'   => '2026-08-01T11:10:00+00:00',
                'completed_at' => null,
                'project_id'   => 'demo-proj-3',
            ],
            [
                'id'              => 'demo-req-6',
                'entity_type'     => 'vacation',
                'entity_id'       => 'demo-vac-6',
                'scheme_id'       => 'demo-scheme-1',
                'current_line_id' => 'demo-line-1',
                'status'          => 'pending',
                'cached_title'    => 'William Turner, devops engineer',
                'cached_data'     => json_encode([
                    'employee'   => 'William Turner, devops engineer',
                    'type'       => 'Paid Leave',
                    'date_start' => '2026-08-24',
                    'date_end'   => '2026-08-28',
                    'days'       => 5,
                    'comment'    => 'Short break',
                ]),
                'deadline_at'  => null,
                'created_by'   => 'demo-user-9',
                'created_at'   => '2026-08-03T08:40:00+00:00',
                'updated_at'   => '2026-08-03T08:40:00+00:00',
                'completed_at' => null,
                'project_id'   => 'demo-proj-4',
            ],
            [
                'id'              => 'demo-req-7',
                'entity_type'     => 'vacation',
                'entity_id'       => 'demo-vac-7',
                'scheme_id'       => 'demo-scheme-2',
                'current_line_id' => 'demo-line-3',
                'status'          => 'pending',
                'cached_title'    => 'Grace Kim, marketing specialist',
                'cached_data'     => json_encode([
                    'employee'   => 'Grace Kim, marketing specialist',
                    'type'       => 'Unpaid Leave',
                    'date_start' => '2026-10-05',
                    'date_end'   => '2026-10-09',
                    'days'       => 5,
                    'comment'    => 'Relocation',
                ]),
                'deadline_at'  => null,
                'created_by'   => 'demo-user-10',
                'created_at'   => '2026-08-05T15:20:00+00:00',
                'updated_at'   => '2026-08-05T15:20:00+00:00',
                'completed_at' => null,
                'project_id'   => 'demo-proj-5',
            ],
            [
                'id'              => 'demo-req-8',
                'entity_type'     => 'vacation',
                'entity_id'       => 'demo-vac-8',
                'scheme_id'       => 'demo-scheme-3',
                'current_line_id' => 'demo-line-4',
                'status'          => 'pending',
                'cached_title'    => 'Sophia Martinez, finance officer',
                'cached_data'     => json_encode([
                    'employee'   => 'Sophia Martinez, finance officer',
                    'type'       => 'Paid Leave',
                    'date_start' => '2026-11-02',
                    'date_end'   => '2026-11-13',
                    'days'       => 10,
                    'comment'    => 'Autumn holidays',
                ]),
                'deadline_at'  => '2026-08-20T12:00:00+00:00',
                'created_by'   => 'demo-user-8',
                'created_at'   => '2026-08-06T09:55:00+00:00',
                'updated_at'   => '2026-08-06T09:55:00+00:00',
                'completed_at' => null,
                'project_id'   => 'demo-proj-1',
            ],
        ];
    }

    /**
     * Completed requests where the current user acted as an approver.
     *
     * Mirrors AgreementRequestsModel::getCompletedRequestsForApprover(): raw
     * request rows with cached_data still JSON-encoded.
     */
    private function completedRequests(): array
    {
        return [
            [
                'id'              => 'demo-req-3',
                'entity_type'     => 'vacation',
                'entity_id'       => 'demo-vac-3',
                'scheme_id'       => 'demo-scheme-1',
                'current_line_id' => null,
                'status'          => 'approved',
                'cached_title'    => 'Michael Reed, qa engineer',
                'cached_data'     => json_encode([
                    'employee'   => 'Michael Reed, qa engineer',
                    'type'       => 'Paid Leave',
                    'date_start' => '2026-06-02',
                    'date_end'   => '2026-06-13',
                    'days'       => 10,
                    'comment'    => 'Annual leave',
                ]),
                'deadline_at'  => null,
                'created_by'   => 'demo-user-3',
                'created_at'   => '2026-05-25T08:15:00+00:00',
                'updated_at'   => '2026-05-27T11:00:00+00:00',
                'completed_at' => '2026-05-27T11:00:00+00:00',
                'project_id'   => 'demo-proj-1',
            ],
            [
                'id'              => 'demo-req-4',
                'entity_type'     => 'vacation',
                'entity_id'       => 'demo-vac-4',
                'scheme_id'       => 'demo-scheme-1',
                'current_line_id' => null,
                'status'          => 'rejected',
                'cached_title'    => 'Sarah Lin, designer',
                'cached_data'     => json_encode([
                    'employee'   => 'Sarah Lin, designer',
                    'type'       => 'Unpaid Leave',
                    'date_start' => '2026-04-14',
                    'date_end'   => '2026-04-25',
                    'days'       => 10,
                    'comment'    => 'Personal reasons',
                ]),
                'deadline_at'  => null,
                'created_by'   => 'demo-user-4',
                'created_at'   => '2026-04-01T10:00:00+00:00',
                'updated_at'   => '2026-04-03T16:45:00+00:00',
                'completed_at' => '2026-04-03T16:45:00+00:00',
                'project_id'   => 'demo-proj-2',
            ],
            [
                'id'              => 'demo-req-9',
                'entity_type'     => 'vacation',
                'entity_id'       => 'demo-vac-9',
                'scheme_id'       => 'demo-scheme-2',
                'current_line_id' => null,
                'status'          => 'approved',
                'cached_title'    => 'David Nguyen, team lead',
                'cached_data'     => json_encode([
                    'employee'   => 'David Nguyen, team lead',
                    'type'       => 'Paid Leave',
                    'date_start' => '2026-03-09',
                    'date_end'   => '2026-03-13',
                    'days'       => 5,
                    'comment'    => 'Spring break',
                ]),
                'deadline_at'  => null,
                'created_by'   => 'demo-user-5',
                'created_at'   => '2026-03-02T09:20:00+00:00',
                'updated_at'   => '2026-03-03T13:10:00+00:00',
                'completed_at' => '2026-03-03T13:10:00+00:00',
                'project_id'   => 'demo-proj-3',
            ],
            [
                'id'              => 'demo-req-10',
                'entity_type'     => 'vacation',
                'entity_id'       => 'demo-vac-10',
                'scheme_id'       => 'demo-scheme-3',
                'current_line_id' => null,
                'status'          => 'returned',
                'cached_title'    => 'Olivia Brooks, hr manager',
                'cached_data'     => json_encode([
                    'employee'   => 'Olivia Brooks, hr manager',
                    'type'       => 'Unpaid Leave',
                    'date_start' => '2026-05-18',
                    'date_end'   => '2026-05-29',
                    'days'       => 10,
                    'comment'    => 'Extended leave request',
                ]),
                'deadline_at'  => null,
                'created_by'   => 'demo-user-6',
                'created_at'   => '2026-05-04T10:40:00+00:00',
                'updated_at'   => '2026-05-06T09:15:00+00:00',
                'completed_at' => '2026-05-06T09:15:00+00:00',
                'project_id'   => 'demo-proj-1',
            ],
            [
                'id'              => 'demo-req-11',
                'entity_type'     => 'vacation',
                'entity_id'       => 'demo-vac-11',
                'scheme_id'       => 'demo-scheme-1',
                'current_line_id' => null,
                'status'          => 'approved',
                'cached_title'    => 'James Patel, frontend developer',
                'cached_data'     => json_encode([
                    'employee'   => 'James Patel, frontend developer',
                    'type'       => 'Paid Leave',
                    'date_start' => '2026-02-16',
                    'date_end'   => '2026-02-20',
                    'days'       => 5,
                    'comment'    => 'Winter holidays',
                ]),
                'deadline_at'  => null,
                'created_by'   => 'demo-user-7',
                'created_at'   => '2026-02-09T08:30:00+00:00',
                'updated_at'   => '2026-02-11T14:00:00+00:00',
                'completed_at' => '2026-02-11T14:00:00+00:00',
                'project_id'   => 'demo-proj-3',
            ],
            [
                'id'              => 'demo-req-12',
                'entity_type'     => 'vacation',
                'entity_id'       => 'demo-vac-12',
                'scheme_id'       => 'demo-scheme-2',
                'current_line_id' => null,
                'status'          => 'canceled',
                'cached_title'    => 'Grace Kim, marketing specialist',
                'cached_data'     => json_encode([
                    'employee'   => 'Grace Kim, marketing specialist',
                    'type'       => 'Unpaid Leave',
                    'date_start' => '2026-06-22',
                    'date_end'   => '2026-06-26',
                    'days'       => 5,
                    'comment'    => 'Withdrawn by employee',
                ]),
                'deadline_at'  => null,
                'created_by'   => 'demo-user-10',
                'created_at'   => '2026-06-15T11:45:00+00:00',
                'updated_at'   => '2026-06-17T10:05:00+00:00',
                'completed_at' => '2026-06-17T10:05:00+00:00',
                'project_id'   => 'demo-proj-5',
            ],
            [
                'id'              => 'demo-req-13',
                'entity_type'     => 'vacation',
                'entity_id'       => 'demo-vac-13',
                'scheme_id'       => 'demo-scheme-3',
                'current_line_id' => null,
                'status'          => 'approved',
                'cached_title'    => 'William Turner, devops engineer',
                'cached_data'     => json_encode([
                    'employee'   => 'William Turner, devops engineer',
                    'type'       => 'Paid Leave',
                    'date_start' => '2026-07-06',
                    'date_end'   => '2026-07-17',
                    'days'       => 10,
                    'comment'    => 'Summer holidays',
                ]),
                'deadline_at'  => null,
                'created_by'   => 'demo-user-9',
                'created_at'   => '2026-06-25T09:00:00+00:00',
                'updated_at'   => '2026-06-29T15:30:00+00:00',
                'completed_at' => '2026-06-29T15:30:00+00:00',
                'project_id'   => 'demo-proj-4',
            ],
            [
                'id'              => 'demo-req-14',
                'entity_type'     => 'vacation',
                'entity_id'       => 'demo-vac-14',
                'scheme_id'       => 'demo-scheme-4',
                'current_line_id' => null,
                'status'          => 'rejected',
                'cached_title'    => 'Sophia Martinez, finance officer',
                'cached_data'     => json_encode([
                    'employee'   => 'Sophia Martinez, finance officer',
                    'type'       => 'Unpaid Leave',
                    'date_start' => '2026-01-19',
                    'date_end'   => '2026-01-30',
                    'days'       => 10,
                    'comment'    => 'Rejected due to reporting period',
                ]),
                'deadline_at'  => null,
                'created_by'   => 'demo-user-8',
                'created_at'   => '2026-01-08T10:25:00+00:00',
                'updated_at'   => '2026-01-12T12:40:00+00:00',
                'completed_at' => '2026-01-12T12:40:00+00:00',
                'project_id'   => 'demo-proj-1',
            ],
        ];
    }

    /**
     * Single request with full details.
     *
     * Mirrors AgreementService::getRequestWithDetails() plus the controller's
     * can_act enrichment: cached_data is decoded to an array here, and scheme /
     * actions / history / current_line are attached.
     */
    private function requestWithDetails(): array
    {
        return [
            'id'              => 'demo-req-1',
            'entity_type'     => 'vacation',
            'entity_id'       => 'demo-vac-1',
            'scheme_id'       => 'demo-scheme-1',
            'current_line_id' => 'demo-line-1',
            'status'          => 'pending',
            'cached_title'    => 'John Carter, backend developer',
            'cached_data'     => [
                'employee'   => 'John Carter, backend developer',
                'type'       => 'Paid Leave',
                'date_start' => '2026-08-10',
                'date_end'   => '2026-08-21',
                'days'       => 10,
                'comment'    => 'Summer vacation',
            ],
            'deadline_at'  => '2026-07-28T12:00:00+00:00',
            'created_by'   => 'demo-user-1',
            'created_at'   => '2026-07-20T09:00:00+00:00',
            'updated_at'   => '2026-07-20T09:00:00+00:00',
            'completed_at' => null,
            'project_id'   => 'demo-proj-1',
            'scheme'       => [
                'id'          => 'demo-scheme-1',
                'name'        => 'Vacation Approval',
                'entity_type' => 'vacation',
                'is_active'   => true,
                'is_default'  => true,
                'lines'       => [
                    [
                        'id'          => 'demo-line-1',
                        'scheme_id'   => 'demo-scheme-1',
                        'name'        => 'Line Manager',
                        'sort_order'  => 1,
                        'require_all' => false,
                        'approvers'   => [
                            ['id' => 'demo-appr-1', 'line_id' => 'demo-line-1', 'approver_type' => 'user', 'user_id' => 'demo-user-5', 'role_id' => null, 'name' => 'David Nguyen, team lead'],
                        ],
                    ],
                    [
                        'id'          => 'demo-line-2',
                        'scheme_id'   => 'demo-scheme-1',
                        'name'        => 'HR Department',
                        'sort_order'  => 2,
                        'require_all' => false,
                        'approvers'   => [
                            ['id' => 'demo-appr-2', 'line_id' => 'demo-line-2', 'approver_type' => 'role', 'user_id' => null, 'role_id' => 3, 'name' => 'Head of Department'],
                        ],
                    ],
                ],
            ],
            'actions'      => [],
            'history'      => $this->history(),
            'current_line' => [
                'id'          => 'demo-line-1',
                'scheme_id'   => 'demo-scheme-1',
                'name'        => 'Line Manager',
                'sort_order'  => 1,
                'require_all' => false,
            ],
            'can_act' => [
                'can_act'    => false,
                'reason'     => 'Available in the full version',
                'is_pending' => true,
            ],
        ];
    }

    /**
     * Request history entries.
     *
     * Mirrors AgreementRequestHistoryModel rows (getHistoryForRequest): raw
     * columns event_type, status_before/after, line_id_before/after, action,
     * changed_by, comment, created_at.
     */
    private function history(): array
    {
        return [
            [
                'id'             => 'demo-hist-2',
                'request_id'     => 'demo-req-1',
                'event_type'     => 'action_performed',
                'status_before'  => 'pending',
                'status_after'   => 'pending',
                'line_id_before' => 'demo-line-1',
                'line_id_after'  => 'demo-line-1',
                'action'         => 'approved',
                'changed_by'     => 'demo-user-5',
                'comment'        => 'Looks good, approved.',
                'created_at'     => '2026-07-21T10:30:00+00:00',
            ],
            [
                'id'             => 'demo-hist-1',
                'request_id'     => 'demo-req-1',
                'event_type'     => 'created',
                'status_before'  => null,
                'status_after'   => 'pending',
                'line_id_before' => null,
                'line_id_after'  => 'demo-line-1',
                'action'         => null,
                'changed_by'     => 'demo-user-1',
                'comment'        => null,
                'created_at'     => '2026-07-20T09:00:00+00:00',
            ],
        ];
    }

    /**
     * Mirrors AgreementController::getOptions(): request statuses, approver
     * types, entity types, action types, sub-entity types and event types.
     */
    private function options(): array
    {
        return [
            'requestStatuses' => $this->statusOptions(),
            'approverTypes'   => $this->approverTypeOptions(),
            'entityTypes'     => $this->entityTypeOptions(),
            'actionTypes'     => $this->actionTypeOptions(),
            'subEntityTypes'  => [
                ['id' => 'user', 'name' => 'User'],
                ['id' => 'project', 'name' => 'Project'],
            ],
            'eventTypes' => [
                'created'          => 'Created',
                'status_changed'   => 'Status changed',
                'line_changed'     => 'Line changed',
                'action_performed' => 'Action performed',
            ],
        ];
    }

    /**
     * Mirrors AgreementRequestsModel::getStatusOptions().
     */
    private function statusOptions(): array
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
     * Mirrors AgreementSchemeApproversModel::getApproverTypeOptions().
     */
    private function approverTypeOptions(): array
    {
        return [
            ['id' => 'user', 'name' => 'User'],
            ['id' => 'role', 'name' => 'Role'],
        ];
    }

    /**
     * Mirrors AgreementSchemesModel::getEntityTypeOptions().
     */
    private function entityTypeOptions(): array
    {
        return [
            ['id' => 'vacation', 'name' => 'Vacation'],
        ];
    }

    /**
     * Mirrors AgreementRequestActionsModel::getActionTypeOptions().
     */
    private function actionTypeOptions(): array
    {
        return [
            ['id' => 'approved', 'name' => 'Approved', 'color' => '#4CAF50'],
            ['id' => 'rejected', 'name' => 'Rejected', 'color' => '#F44336'],
            ['id' => 'returned', 'name' => 'Returned for revision', 'color' => '#2196F3'],
        ];
    }

    /**
     * Mirrors ProjectModel::getListByFilter([], ['id', 'name'], ...).
     */
    private function projects(): array
    {
        return [
            ['id' => 'demo-proj-1', 'name' => 'Apollo'],
            ['id' => 'demo-proj-2', 'name' => 'Gemini'],
            ['id' => 'demo-proj-3', 'name' => 'Mercury'],
            ['id' => 'demo-proj-4', 'name' => 'Orion'],
            ['id' => 'demo-proj-5', 'name' => 'Atlas'],
        ];
    }

    /**
     * Mirrors UserModel::getListForLink(): id + "Full name, position".
     */
    private function users(): array
    {
        return [
            ['id' => 'demo-user-1', 'name' => 'John Carter, backend developer'],
            ['id' => 'demo-user-2', 'name' => 'Emily Stone, project manager'],
            ['id' => 'demo-user-3', 'name' => 'Michael Reed, qa engineer'],
            ['id' => 'demo-user-4', 'name' => 'Sarah Lin, designer'],
            ['id' => 'demo-user-5', 'name' => 'David Nguyen, team lead'],
            ['id' => 'demo-user-6', 'name' => 'Olivia Brooks, hr manager'],
            ['id' => 'demo-user-7', 'name' => 'James Patel, frontend developer'],
            ['id' => 'demo-user-8', 'name' => 'Sophia Martinez, finance officer'],
            ['id' => 'demo-user-9', 'name' => 'William Turner, devops engineer'],
            ['id' => 'demo-user-10', 'name' => 'Grace Kim, marketing specialist'],
        ];
    }

    /**
     * Mirrors GlobalRolesModel::getListByFilter() rows.
     */
    private function roles(): array
    {
        return [
            ['id' => 3, 'name' => 'Head of Department', 'sort' => 1, 'deleted' => false],
            ['id' => 2, 'name' => 'Officer', 'sort' => 2, 'deleted' => false],
            ['id' => 1, 'name' => 'CEO', 'sort' => 3, 'deleted' => false],
            ['id' => 4, 'name' => 'HR Manager', 'sort' => 4, 'deleted' => false],
            ['id' => 5, 'name' => 'Finance Controller', 'sort' => 5, 'deleted' => false],
        ];
    }

    /**
     * Mirrors AgreementSchemesModel::getListByFilter(): raw scheme rows without
     * lines. The schemes settings page loads full details separately via
     * getScheme, so the list only needs the base columns.
     */
    private function schemesList(): array
    {
        return [
            [
                'id'          => 'demo-scheme-1',
                'name'        => 'Vacation Approval',
                'description' => 'Default two-step approval flow for employee vacation requests.',
                'entity_type' => 'vacation',
                'is_default'  => true,
                'is_active'   => true,
                'created_by'  => 'demo-user-5',
                'created_at'  => '2026-05-01T08:00:00+00:00',
                'updated_at'  => '2026-06-15T10:20:00+00:00',
            ],
            [
                'id'          => 'demo-scheme-2',
                'name'        => 'Standard approval',
                'description' => 'Single-step approval used for short leave requests.',
                'entity_type' => 'vacation',
                'is_default'  => false,
                'is_active'   => true,
                'created_by'  => 'demo-user-5',
                'created_at'  => '2026-06-10T09:30:00+00:00',
                'updated_at'  => '2026-06-10T09:30:00+00:00',
            ],
            [
                'id'          => 'demo-scheme-3',
                'name'        => 'Extended Leave Approval',
                'description' => 'Three-step flow for long leave requests with HR review.',
                'entity_type' => 'vacation',
                'is_default'  => false,
                'is_active'   => true,
                'created_by'  => 'demo-user-6',
                'created_at'  => '2026-06-20T11:00:00+00:00',
                'updated_at'  => '2026-07-05T14:15:00+00:00',
            ],
            [
                'id'          => 'demo-scheme-4',
                'name'        => 'Executive Approval',
                'description' => 'Finance and executive sign-off for special cases.',
                'entity_type' => 'vacation',
                'is_default'  => false,
                'is_active'   => false,
                'created_by'  => 'demo-user-8',
                'created_at'  => '2026-07-01T08:45:00+00:00',
                'updated_at'  => '2026-07-01T08:45:00+00:00',
            ],
        ];
    }

    /**
     * Mirrors AgreementService::getSchemeWithDetails(): a raw scheme row with a
     * 'lines' array, each line carrying its own 'approvers' array enriched with a
     * resolved user/role 'name'. Resolves by slug so both demo schemes render
     * distinct details; defaults to the primary scheme.
     */
    private function schemeDetails(string $slug): array
    {
        $schemes = [
            'demo-scheme-1' => [
                'id'          => 'demo-scheme-1',
                'name'        => 'Vacation Approval',
                'description' => 'Default two-step approval flow for employee vacation requests.',
                'entity_type' => 'vacation',
                'is_default'  => true,
                'is_active'   => true,
                'created_by'  => 'demo-user-5',
                'created_at'  => '2026-05-01T08:00:00+00:00',
                'updated_at'  => '2026-06-15T10:20:00+00:00',
                'lines'       => [
                    [
                        'id'               => 'demo-line-1',
                        'scheme_id'        => 'demo-scheme-1',
                        'name'             => 'Line Manager',
                        'sort_order'       => 1,
                        'require_all'      => false,
                        'deadline_enabled' => false,
                        'deadline_days'    => null,
                        'approvers'        => [
                            ['id' => 'demo-appr-1', 'line_id' => 'demo-line-1', 'approver_type' => 'user', 'user_id' => 'demo-user-5', 'role_id' => null, 'name' => 'David Nguyen, team lead'],
                        ],
                    ],
                    [
                        'id'               => 'demo-line-2',
                        'scheme_id'        => 'demo-scheme-1',
                        'name'             => 'HR Department',
                        'sort_order'       => 2,
                        'require_all'      => true,
                        'deadline_enabled' => true,
                        'deadline_days'    => 3,
                        'approvers'        => [
                            ['id' => 'demo-appr-2', 'line_id' => 'demo-line-2', 'approver_type' => 'role', 'user_id' => null, 'role_id' => 3, 'name' => 'Head of Department'],
                        ],
                    ],
                ],
            ],
            'demo-scheme-2' => [
                'id'          => 'demo-scheme-2',
                'name'        => 'Standard approval',
                'description' => 'Single-step approval used for short leave requests.',
                'entity_type' => 'vacation',
                'is_default'  => false,
                'is_active'   => true,
                'created_by'  => 'demo-user-5',
                'created_at'  => '2026-06-10T09:30:00+00:00',
                'updated_at'  => '2026-06-10T09:30:00+00:00',
                'lines'       => [
                    [
                        'id'               => 'demo-line-3',
                        'scheme_id'        => 'demo-scheme-2',
                        'name'             => 'Manager approval',
                        'sort_order'       => 1,
                        'require_all'      => false,
                        'deadline_enabled' => false,
                        'deadline_days'    => null,
                        'approvers'        => [
                            ['id' => 'demo-appr-3', 'line_id' => 'demo-line-3', 'approver_type' => 'user', 'user_id' => 'demo-user-2', 'role_id' => null, 'name' => 'Emily Stone, project manager'],
                            ['id' => 'demo-appr-4', 'line_id' => 'demo-line-3', 'approver_type' => 'role', 'user_id' => null, 'role_id' => 2, 'name' => 'Officer'],
                        ],
                    ],
                ],
            ],
            'demo-scheme-3' => [
                'id'          => 'demo-scheme-3',
                'name'        => 'Extended Leave Approval',
                'description' => 'Three-step flow for long leave requests with HR review.',
                'entity_type' => 'vacation',
                'is_default'  => false,
                'is_active'   => true,
                'created_by'  => 'demo-user-6',
                'created_at'  => '2026-06-20T11:00:00+00:00',
                'updated_at'  => '2026-07-05T14:15:00+00:00',
                'lines'       => [
                    [
                        'id'               => 'demo-line-4',
                        'scheme_id'        => 'demo-scheme-3',
                        'name'             => 'Line Manager',
                        'sort_order'       => 1,
                        'require_all'      => false,
                        'deadline_enabled' => false,
                        'deadline_days'    => null,
                        'approvers'        => [
                            ['id' => 'demo-appr-5', 'line_id' => 'demo-line-4', 'approver_type' => 'user', 'user_id' => 'demo-user-5', 'role_id' => null, 'name' => 'David Nguyen, team lead'],
                        ],
                    ],
                    [
                        'id'               => 'demo-line-5',
                        'scheme_id'        => 'demo-scheme-3',
                        'name'             => 'HR Review',
                        'sort_order'       => 2,
                        'require_all'      => true,
                        'deadline_enabled' => true,
                        'deadline_days'    => 5,
                        'approvers'        => [
                            ['id' => 'demo-appr-6', 'line_id' => 'demo-line-5', 'approver_type' => 'role', 'user_id' => null, 'role_id' => 4, 'name' => 'HR Manager'],
                            ['id' => 'demo-appr-7', 'line_id' => 'demo-line-5', 'approver_type' => 'user', 'user_id' => 'demo-user-6', 'role_id' => null, 'name' => 'Olivia Brooks, hr manager'],
                        ],
                    ],
                    [
                        'id'               => 'demo-line-6',
                        'scheme_id'        => 'demo-scheme-3',
                        'name'             => 'Department Head',
                        'sort_order'       => 3,
                        'require_all'      => false,
                        'deadline_enabled' => false,
                        'deadline_days'    => null,
                        'approvers'        => [
                            ['id' => 'demo-appr-8', 'line_id' => 'demo-line-6', 'approver_type' => 'role', 'user_id' => null, 'role_id' => 3, 'name' => 'Head of Department'],
                        ],
                    ],
                ],
            ],
            'demo-scheme-4' => [
                'id'          => 'demo-scheme-4',
                'name'        => 'Executive Approval',
                'description' => 'Finance and executive sign-off for special cases.',
                'entity_type' => 'vacation',
                'is_default'  => false,
                'is_active'   => false,
                'created_by'  => 'demo-user-8',
                'created_at'  => '2026-07-01T08:45:00+00:00',
                'updated_at'  => '2026-07-01T08:45:00+00:00',
                'lines'       => [
                    [
                        'id'               => 'demo-line-7',
                        'scheme_id'        => 'demo-scheme-4',
                        'name'             => 'Finance Review',
                        'sort_order'       => 1,
                        'require_all'      => false,
                        'deadline_enabled' => true,
                        'deadline_days'    => 2,
                        'approvers'        => [
                            ['id' => 'demo-appr-9', 'line_id' => 'demo-line-7', 'approver_type' => 'role', 'user_id' => null, 'role_id' => 5, 'name' => 'Finance Controller'],
                            ['id' => 'demo-appr-10', 'line_id' => 'demo-line-7', 'approver_type' => 'user', 'user_id' => 'demo-user-8', 'role_id' => null, 'name' => 'Sophia Martinez, finance officer'],
                        ],
                    ],
                    [
                        'id'               => 'demo-line-8',
                        'scheme_id'        => 'demo-scheme-4',
                        'name'             => 'Executive Sign-off',
                        'sort_order'       => 2,
                        'require_all'      => false,
                        'deadline_enabled' => false,
                        'deadline_days'    => null,
                        'approvers'        => [
                            ['id' => 'demo-appr-11', 'line_id' => 'demo-line-8', 'approver_type' => 'role', 'user_id' => null, 'role_id' => 1, 'name' => 'CEO'],
                        ],
                    ],
                ],
            ],
        ];

        return $schemes[$slug] ?? $schemes['demo-scheme-1'];
    }

    /**
     * Mirrors AgreementController::getSchemeAssignments(): raw assignment rows
     * enriched with 'sub_entity_name'. Resolved by scheme slug; the secondary
     * scheme has no custom assignments so its empty state renders.
     */
    private function schemeAssignments(string $slug): array
    {
        $assignments = [
            'demo-scheme-1' => [
                [
                    'id'              => 'demo-assign-1',
                    'scheme_id'       => 'demo-scheme-1',
                    'entity_type'     => 'vacation',
                    'sub_entity_type' => 'user',
                    'sub_entity_id'   => 'demo-user-2',
                    'sub_entity_name' => 'Emily Stone, project manager',
                    'priority'        => 20,
                    'created_by'      => 'demo-user-5',
                    'created_at'      => '2026-06-16T09:00:00+00:00',
                ],
                [
                    'id'              => 'demo-assign-2',
                    'scheme_id'       => 'demo-scheme-1',
                    'entity_type'     => 'vacation',
                    'sub_entity_type' => 'project',
                    'sub_entity_id'   => 'demo-proj-1',
                    'sub_entity_name' => 'Apollo',
                    'priority'        => 10,
                    'created_by'      => 'demo-user-5',
                    'created_at'      => '2026-06-16T09:05:00+00:00',
                ],
                [
                    'id'              => 'demo-assign-3',
                    'scheme_id'       => 'demo-scheme-1',
                    'entity_type'     => 'vacation',
                    'sub_entity_type' => 'project',
                    'sub_entity_id'   => 'demo-proj-4',
                    'sub_entity_name' => 'Orion',
                    'priority'        => 15,
                    'created_by'      => 'demo-user-5',
                    'created_at'      => '2026-06-16T09:10:00+00:00',
                ],
            ],
            'demo-scheme-2' => [],
            'demo-scheme-3' => [
                [
                    'id'              => 'demo-assign-4',
                    'scheme_id'       => 'demo-scheme-3',
                    'entity_type'     => 'vacation',
                    'sub_entity_type' => 'project',
                    'sub_entity_id'   => 'demo-proj-3',
                    'sub_entity_name' => 'Mercury',
                    'priority'        => 30,
                    'created_by'      => 'demo-user-6',
                    'created_at'      => '2026-06-21T10:00:00+00:00',
                ],
                [
                    'id'              => 'demo-assign-5',
                    'scheme_id'       => 'demo-scheme-3',
                    'entity_type'     => 'vacation',
                    'sub_entity_type' => 'user',
                    'sub_entity_id'   => 'demo-user-8',
                    'sub_entity_name' => 'Sophia Martinez, finance officer',
                    'priority'        => 25,
                    'created_by'      => 'demo-user-6',
                    'created_at'      => '2026-06-21T10:05:00+00:00',
                ],
            ],
            'demo-scheme-4' => [],
        ];

        return $assignments[$slug] ?? [];
    }

    /**
     * Mirrors AgreementController::getEntitiesForAssignment(): returns the
     * UserModel/ProjectModel getListForLink shape ([id, name]) for the requested
     * sub-entity type.
     */
    private function entitiesForAssignment(string $subEntityType): array
    {
        return match ($subEntityType) {
            'project' => $this->projects(),
            default   => $this->users(),
        };
    }
}
