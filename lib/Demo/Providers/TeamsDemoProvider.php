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
 * Fake, read-only demo data for the Teams module. English only.
 * Does not reference any OCA\Done\Modules\Teams class - every shape below
 * is hardcoded to mirror the real TeamsController / TableService responses.
 */
class TeamsDemoProvider extends AbstractDemoProvider
{
    /** @var string Platform team id used across the fake dataset. */
    private const TEAM_PLATFORM = 'demo-team-1';

    /** @var string Growth team id. */
    private const TEAM_GROWTH = 'demo-team-2';

    /** @var string Design team id. */
    private const TEAM_DESIGN = 'demo-team-3';

    /** @var string Data team id. */
    private const TEAM_DATA = 'demo-team-4';

    /** @var string Mobile team id. */
    private const TEAM_MOBILE = 'demo-team-5';

    /** @var string Security team id. */
    private const TEAM_SECURITY = 'demo-team-6';

    protected function readMethods(): array
    {
        return [
            // DynamicTable source (mirror TableService::getTableDataForEntity).
            'getTeamsTableData' => fn (IRequest $r): array => $this->teamsTable(),

            // Team read endpoints (mirror TeamsController). getTeams returns a
            // single team when a slug is given (edit form prefill) and the full
            // list otherwise.
            'getTeams' => function (IRequest $r): array {
                $slug = (string)$r->getParam('slug');

                return $slug !== '' ? $this->teamBySlug($slug) : $this->teams();
            },
            'getTeamsPublicData' => fn (IRequest $r): array => $this->teamPublicData((string)$r->getParam('slug')),

            // Association read endpoints.
            'getEmployeesInTeams'  => fn (IRequest $r): array => $this->employeesInTeams(),
            'getTeamsInProjects'   => fn (IRequest $r): array => $this->teamsInProjects(),
            'getTeamsInDirections' => fn (IRequest $r): array => $this->teamsInDirections(),
        ];
    }

    /**
     * Shared DynamicTable helper. Mirrors TableService::getTableDataForEntity
     * (lib/Service/TableService.php): returns exactly
     * { allColumnsOrdering, data, settings }.
     *
     * allColumnsOrdering is a sequential LIST of column descriptors
     * ({ key, hidden, title, rules, info }) as produced by sortFields();
     * the rows live under `data` (NOT `rows`), each keyed by field name.
     *
     * @param array<int, array{0: string, 1: string}> $columns list of [key, title]
     * @param array<int, array<string, mixed>>        $rows
     */
    private function table(array $columns, array $rows): array
    {
        $ordering = [];

        foreach ($columns as $column) {
            [$key, $title] = $column;
            $ordering[] = [
                'key'    => $key,
                'hidden' => false,
                'title'  => $this->tr($title),
                'rules'  => null,
                'info'   => null,
            ];
        }

        return [
            'allColumnsOrdering' => $ordering,
            'data'               => $rows,
            'settings'           => [
                'tableColumnView'        => [],
                'tableSortColumns'       => [],
                'tableSortWithinColumns' => [],
                'tableFilter'            => [],
            ],
        ];
    }

    /**
     * Mirror getTeamsTableData over TeamsModel (shown fields: name, comment).
     */
    private function teamsTable(): array
    {
        return $this->table(
            [
                ['name', 'Team name'],
                ['comment', 'Comment'],
            ],
            [
                [
                    'id'        => self::TEAM_PLATFORM,
                    'slug'      => self::TEAM_PLATFORM,
                    'slug_type' => 1,
                    'name'      => 'Platform',
                    'comment'   => 'Core services and infrastructure',
                ],
                [
                    'id'        => self::TEAM_GROWTH,
                    'slug'      => self::TEAM_GROWTH,
                    'slug_type' => 1,
                    'name'      => 'Growth',
                    'comment'   => 'Acquisition and product analytics',
                ],
                [
                    'id'        => self::TEAM_DESIGN,
                    'slug'      => self::TEAM_DESIGN,
                    'slug_type' => 1,
                    'name'      => 'Design',
                    'comment'   => 'Product design and user experience',
                ],
                [
                    'id'        => self::TEAM_DATA,
                    'slug'      => self::TEAM_DATA,
                    'slug_type' => 1,
                    'name'      => 'Data',
                    'comment'   => 'Data platform and analytics pipelines',
                ],
                [
                    'id'        => self::TEAM_MOBILE,
                    'slug'      => self::TEAM_MOBILE,
                    'slug_type' => 1,
                    'name'      => 'Mobile',
                    'comment'   => 'iOS and Android applications',
                ],
                [
                    'id'        => self::TEAM_SECURITY,
                    'slug'      => self::TEAM_SECURITY,
                    'slug_type' => 1,
                    'name'      => 'Security',
                    'comment'   => 'Application security and compliance',
                ],
            ]
        );
    }

    /**
     * Mirror getTeams (TeamsModel::getListByFilter with all fields).
     */
    private function teams(): array
    {
        return [
            [
                'id'        => self::TEAM_PLATFORM,
                'slug'      => self::TEAM_PLATFORM,
                'slug_type' => 1,
                'name'      => 'Platform',
                'comment'   => 'Core services and infrastructure',
                'lead_name' => 'John Carter, Backend Developer',
            ],
            [
                'id'        => self::TEAM_GROWTH,
                'slug'      => self::TEAM_GROWTH,
                'slug_type' => 1,
                'name'      => 'Growth',
                'comment'   => 'Acquisition and product analytics',
                'lead_name' => 'Emma Reid, Product Manager',
            ],
            [
                'id'        => self::TEAM_DESIGN,
                'slug'      => self::TEAM_DESIGN,
                'slug_type' => 1,
                'name'      => 'Design',
                'comment'   => 'Product design and user experience',
                'lead_name' => 'Sophia Bennett, Lead Designer',
            ],
            [
                'id'        => self::TEAM_DATA,
                'slug'      => self::TEAM_DATA,
                'slug_type' => 1,
                'name'      => 'Data',
                'comment'   => 'Data platform and analytics pipelines',
                'lead_name' => 'Liam Novak, Data Engineer',
            ],
            [
                'id'        => self::TEAM_MOBILE,
                'slug'      => self::TEAM_MOBILE,
                'slug_type' => 1,
                'name'      => 'Mobile',
                'comment'   => 'iOS and Android applications',
                'lead_name' => 'Olivia Hayes, Mobile Lead',
            ],
            [
                'id'        => self::TEAM_SECURITY,
                'slug'      => self::TEAM_SECURITY,
                'slug_type' => 1,
                'name'      => 'Security',
                'comment'   => 'Application security and compliance',
                'lead_name' => 'Noah Fischer, Security Engineer',
            ],
        ];
    }

    /**
     * A single team by slug from the full-field list (used by fetchTeamBySlug
     * for the edit form). Falls back to the first demo team on an unknown slug.
     *
     * @return array<string, mixed>
     */
    private function teamBySlug(string $slug): array
    {
        foreach ($this->teams() as $team) {
            if ($team['slug'] === $slug || $team['id'] === $slug) {
                return $team;
            }
        }

        return $this->teams()[0];
    }

    /**
     * Mirror getTeamsPublicData (TeamsModel::getBySlug): the real endpoint
     * returns a SINGLE team for the requested slug. Match by id/slug, else
     * fall back to the first demo team.
     *
     * @return array<string, mixed>
     */
    private function teamPublicData(string $slug): array
    {
        $teams = [
            self::TEAM_PLATFORM => [
                'id'        => self::TEAM_PLATFORM,
                'name'      => 'Platform',
                'lead_name' => 'John Carter, Backend Developer',
                'comment'   => 'Core platform team',
            ],
            self::TEAM_GROWTH => [
                'id'        => self::TEAM_GROWTH,
                'name'      => 'Growth',
                'lead_name' => 'Emma Reid, Product Manager',
                'comment'   => 'Acquisition and product analytics',
            ],
            self::TEAM_DESIGN => [
                'id'        => self::TEAM_DESIGN,
                'name'      => 'Design',
                'lead_name' => 'Sophia Bennett, Lead Designer',
                'comment'   => 'Product design and user experience',
            ],
            self::TEAM_DATA => [
                'id'        => self::TEAM_DATA,
                'name'      => 'Data',
                'lead_name' => 'Liam Novak, Data Engineer',
                'comment'   => 'Data platform and analytics pipelines',
            ],
            self::TEAM_MOBILE => [
                'id'        => self::TEAM_MOBILE,
                'name'      => 'Mobile',
                'lead_name' => 'Olivia Hayes, Mobile Lead',
                'comment'   => 'iOS and Android applications',
            ],
            self::TEAM_SECURITY => [
                'id'        => self::TEAM_SECURITY,
                'name'      => 'Security',
                'lead_name' => 'Noah Fischer, Security Engineer',
                'comment'   => 'Application security and compliance',
            ],
        ];

        return $teams[$slug] ?? reset($teams);
    }

    /**
     * Mirror getEmployeesInTeams (EmployeesToTeamsModel::getLinkedList with
     * returnLinkedRecords = true): each linked field carries the full linked
     * record plus a scalar "{field}_linked" name.
     */
    private function employeesInTeams(): array
    {
        return [
            [
                'id'             => 'demo-ett-1',
                'user_id'        => ['id' => 'demo-user-1', 'name' => 'John Carter, Backend Developer'],
                'user_id_linked' => 'John Carter, Backend Developer',
                'team_id'        => ['id' => self::TEAM_PLATFORM, 'name' => 'Platform'],
                'team_id_linked' => 'Platform',
                'role_id'        => ['id' => 'demo-role-1', 'name' => 'Team Lead'],
                'role_id_linked' => 'Team Lead',
            ],
            [
                'id'             => 'demo-ett-2',
                'user_id'        => ['id' => 'demo-user-2', 'name' => 'Emma Reid, Product Manager'],
                'user_id_linked' => 'Emma Reid, Product Manager',
                'team_id'        => ['id' => self::TEAM_GROWTH, 'name' => 'Growth'],
                'team_id_linked' => 'Growth',
                'role_id'        => ['id' => 'demo-role-2', 'name' => 'Member'],
                'role_id_linked' => 'Member',
            ],
            [
                'id'             => 'demo-ett-3',
                'user_id'        => ['id' => 'demo-user-3', 'name' => 'Mia Thompson, Site Reliability Engineer'],
                'user_id_linked' => 'Mia Thompson, Site Reliability Engineer',
                'team_id'        => ['id' => self::TEAM_PLATFORM, 'name' => 'Platform'],
                'team_id_linked' => 'Platform',
                'role_id'        => ['id' => 'demo-role-2', 'name' => 'Member'],
                'role_id_linked' => 'Member',
            ],
            [
                'id'             => 'demo-ett-4',
                'user_id'        => ['id' => 'demo-user-4', 'name' => 'Lucas Weber, DevOps Engineer'],
                'user_id_linked' => 'Lucas Weber, DevOps Engineer',
                'team_id'        => ['id' => self::TEAM_PLATFORM, 'name' => 'Platform'],
                'team_id_linked' => 'Platform',
                'role_id'        => ['id' => 'demo-role-4', 'name' => 'Contributor'],
                'role_id_linked' => 'Contributor',
            ],
            [
                'id'             => 'demo-ett-5',
                'user_id'        => ['id' => 'demo-user-5', 'name' => 'Daniel Foster, Growth Marketer'],
                'user_id_linked' => 'Daniel Foster, Growth Marketer',
                'team_id'        => ['id' => self::TEAM_GROWTH, 'name' => 'Growth'],
                'team_id_linked' => 'Growth',
                'role_id'        => ['id' => 'demo-role-1', 'name' => 'Team Lead'],
                'role_id_linked' => 'Team Lead',
            ],
            [
                'id'             => 'demo-ett-6',
                'user_id'        => ['id' => 'demo-user-6', 'name' => 'Ava Morales, Data Analyst'],
                'user_id_linked' => 'Ava Morales, Data Analyst',
                'team_id'        => ['id' => self::TEAM_GROWTH, 'name' => 'Growth'],
                'team_id_linked' => 'Growth',
                'role_id'        => ['id' => 'demo-role-2', 'name' => 'Member'],
                'role_id_linked' => 'Member',
            ],
            [
                'id'             => 'demo-ett-7',
                'user_id'        => ['id' => 'demo-user-7', 'name' => 'Sophia Bennett, Lead Designer'],
                'user_id_linked' => 'Sophia Bennett, Lead Designer',
                'team_id'        => ['id' => self::TEAM_DESIGN, 'name' => 'Design'],
                'team_id_linked' => 'Design',
                'role_id'        => ['id' => 'demo-role-1', 'name' => 'Team Lead'],
                'role_id_linked' => 'Team Lead',
            ],
            [
                'id'             => 'demo-ett-8',
                'user_id'        => ['id' => 'demo-user-8', 'name' => 'Ethan Clarke, UX Researcher'],
                'user_id_linked' => 'Ethan Clarke, UX Researcher',
                'team_id'        => ['id' => self::TEAM_DESIGN, 'name' => 'Design'],
                'team_id_linked' => 'Design',
                'role_id'        => ['id' => 'demo-role-2', 'name' => 'Member'],
                'role_id_linked' => 'Member',
            ],
            [
                'id'             => 'demo-ett-9',
                'user_id'        => ['id' => 'demo-user-9', 'name' => 'Liam Novak, Data Engineer'],
                'user_id_linked' => 'Liam Novak, Data Engineer',
                'team_id'        => ['id' => self::TEAM_DATA, 'name' => 'Data'],
                'team_id_linked' => 'Data',
                'role_id'        => ['id' => 'demo-role-1', 'name' => 'Team Lead'],
                'role_id_linked' => 'Team Lead',
            ],
            [
                'id'             => 'demo-ett-10',
                'user_id'        => ['id' => 'demo-user-10', 'name' => 'Isabella Rossi, Machine Learning Engineer'],
                'user_id_linked' => 'Isabella Rossi, Machine Learning Engineer',
                'team_id'        => ['id' => self::TEAM_DATA, 'name' => 'Data'],
                'team_id_linked' => 'Data',
                'role_id'        => ['id' => 'demo-role-4', 'name' => 'Contributor'],
                'role_id_linked' => 'Contributor',
            ],
            [
                'id'             => 'demo-ett-11',
                'user_id'        => ['id' => 'demo-user-11', 'name' => 'Grace Kim, Analytics Engineer'],
                'user_id_linked' => 'Grace Kim, Analytics Engineer',
                'team_id'        => ['id' => self::TEAM_DATA, 'name' => 'Data'],
                'team_id_linked' => 'Data',
                'role_id'        => ['id' => 'demo-role-3', 'name' => 'Observer'],
                'role_id_linked' => 'Observer',
            ],
            [
                'id'             => 'demo-ett-12',
                'user_id'        => ['id' => 'demo-user-12', 'name' => 'Olivia Hayes, Mobile Lead'],
                'user_id_linked' => 'Olivia Hayes, Mobile Lead',
                'team_id'        => ['id' => self::TEAM_MOBILE, 'name' => 'Mobile'],
                'team_id_linked' => 'Mobile',
                'role_id'        => ['id' => 'demo-role-1', 'name' => 'Team Lead'],
                'role_id_linked' => 'Team Lead',
            ],
            [
                'id'             => 'demo-ett-13',
                'user_id'        => ['id' => 'demo-user-13', 'name' => 'Henry Walsh, iOS Developer'],
                'user_id_linked' => 'Henry Walsh, iOS Developer',
                'team_id'        => ['id' => self::TEAM_MOBILE, 'name' => 'Mobile'],
                'team_id_linked' => 'Mobile',
                'role_id'        => ['id' => 'demo-role-2', 'name' => 'Member'],
                'role_id_linked' => 'Member',
            ],
            [
                'id'             => 'demo-ett-14',
                'user_id'        => ['id' => 'demo-user-14', 'name' => 'Noah Fischer, Security Engineer'],
                'user_id_linked' => 'Noah Fischer, Security Engineer',
                'team_id'        => ['id' => self::TEAM_SECURITY, 'name' => 'Security'],
                'team_id_linked' => 'Security',
                'role_id'        => ['id' => 'demo-role-1', 'name' => 'Team Lead'],
                'role_id_linked' => 'Team Lead',
            ],
            [
                'id'             => 'demo-ett-15',
                'user_id'        => ['id' => 'demo-user-15', 'name' => 'Chloe Martin, Compliance Analyst'],
                'user_id_linked' => 'Chloe Martin, Compliance Analyst',
                'team_id'        => ['id' => self::TEAM_SECURITY, 'name' => 'Security'],
                'team_id_linked' => 'Security',
                'role_id'        => ['id' => 'demo-role-5', 'name' => 'Advisor'],
                'role_id_linked' => 'Advisor',
            ],
        ];
    }

    /**
     * Mirror getTeamsInProjects (TeamsToProjectsModel::getLinkedList without
     * linked records): linked fields are resolved to their scalar name.
     */
    private function teamsInProjects(): array
    {
        return [
            [
                'id'         => 'demo-ttp-1',
                'team_id'    => 'Platform',
                'project_id' => 'Apollo',
            ],
            [
                'id'         => 'demo-ttp-2',
                'team_id'    => 'Growth',
                'project_id' => 'Gemini',
            ],
            [
                'id'         => 'demo-ttp-3',
                'team_id'    => 'Platform',
                'project_id' => 'Helios',
            ],
            [
                'id'         => 'demo-ttp-4',
                'team_id'    => 'Growth',
                'project_id' => 'Mercury',
            ],
            [
                'id'         => 'demo-ttp-5',
                'team_id'    => 'Design',
                'project_id' => 'Aurora',
            ],
            [
                'id'         => 'demo-ttp-6',
                'team_id'    => 'Data',
                'project_id' => 'Nebula',
            ],
            [
                'id'         => 'demo-ttp-7',
                'team_id'    => 'Mobile',
                'project_id' => 'Orion',
            ],
            [
                'id'         => 'demo-ttp-8',
                'team_id'    => 'Security',
                'project_id' => 'Sentinel',
            ],
        ];
    }

    /**
     * Mirror getTeamsInDirections (TeamsToDirectionsModel::getLinkedList without
     * linked records): linked fields are resolved to their scalar name.
     */
    private function teamsInDirections(): array
    {
        return [
            [
                'id'           => 'demo-ttd-1',
                'team_id'      => 'Platform',
                'direction_id' => 'Engineering',
            ],
            [
                'id'           => 'demo-ttd-2',
                'team_id'      => 'Growth',
                'direction_id' => 'Marketing',
            ],
            [
                'id'           => 'demo-ttd-3',
                'team_id'      => 'Platform',
                'direction_id' => 'Operations',
            ],
            [
                'id'           => 'demo-ttd-4',
                'team_id'      => 'Design',
                'direction_id' => 'Product',
            ],
            [
                'id'           => 'demo-ttd-5',
                'team_id'      => 'Data',
                'direction_id' => 'Engineering',
            ],
            [
                'id'           => 'demo-ttd-6',
                'team_id'      => 'Mobile',
                'direction_id' => 'Engineering',
            ],
            [
                'id'           => 'demo-ttd-7',
                'team_id'      => 'Security',
                'direction_id' => 'Operations',
            ],
            [
                'id'           => 'demo-ttd-8',
                'team_id'      => 'Growth',
                'direction_id' => 'Sales',
            ],
        ];
    }
}
