<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Models;

use OCA\Done\Models\Dictionaries\Roles_Model;
use OCA\Done\Modules\Projects\Models\Project_Model;
use OCA\Done\Service\BaseService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class UsersRolesInProjects_Model.
 */
class UsersRolesInProjects_Model extends Base_Model
{
    public string $table = 'done_users_roles';
    public string $modelTitle = 'Roles of employees in projects';
    public string $modelName = 'usersRolesModel';
    public string $dbTableComment = 'Links users to roles in projects: defines which role an employee performs in a specific project.';

    protected array $hashFields = [
        'user_id',
        'project_id',
        'role_id',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Unique identifier for a user role assignment in a project',
        ],
        'user_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Employee',
            'required'   => true,
            'link'       => User_Model::class,
            'db_comment' => 'User ID. References oc_done_users_data.id',
        ],
        'role_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Role in project',
            'required'   => true,
            'link'       => Roles_Model::class,
            'db_comment' => 'Role in project ID. References oc_done_roles.id',
        ],
        'project_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Project',
            'link'       => Project_Model::class,
            'required'   => true,
            'db_comment' => 'Project ID. References oc_done_projects.id',
        ],
        'created_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC',
        ],
        'updated_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Updated at',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC',
        ],
    ];

    /**
     * @override
     */
    public function validateData(array $data, bool $save = false, array $ignoreFields = []): array
    {
        [$data, $errors] = parent::validateData($data, $save, $ignoreFields);

        if (
            $save
            && !empty(
                $this->getItemByFilter(
                    [
                        'user_id'    => $data['user_id'],
                        'project_id' => $data['project_id'],
                        'role_id'    => $data['role_id'],
                    ]
                )
            )
        ) {
            $errors[] = $this->translateService->getTranslate(
                'This employee is already attached to the selected project and role'
            );
        }

        if (
            !$save
            && !empty(
                $this->getItemByFilter(
                    [
                        'user_id'    => $data['user_id'],
                        'project_id' => $data['project_id'],
                        'role_id'    => $data['role_id'],
                        'id'         => ['!=', $data['id']],
                    ]
                )
            )
        ) {
            $errors[] = $this->translateService->getTranslate(
                'This employee is already attached to the selected project and role'
            );
        }

        return [$data, $errors];
    }

    /**
     * Get user roles in project
     *
     * @param string $projectId
     *
     * @return array<int, mixed>
     */
    public function getUserRoles(string $projectId): array
    {
        $data = $this->getListByFilter(['project_id' => $projectId], ['*'], ['id', 'ASC']);

        $usersIds = BaseService::getField($data, 'user_id', true);
        $rolesIds = BaseService::getField($data, 'role_id', true);

        $users = (new User_Model())
            ->getIndexedUsersFullNameWithPosition(['id' => ['IN', $usersIds, IQueryBuilder::PARAM_STR_ARRAY]]);
        $roles = (new Roles_Model())
            ->getIndexedListByFilter('id', ['id' => ['IN', $rolesIds, IQueryBuilder::PARAM_STR_ARRAY]]);

        foreach ($data as $idx => $row) {
            $userId = $row['user_id'];
            $roleId = $row['role_id'];

            $user = $users[$userId] ?? [];
            $role = $roles[$roleId] ?? [];

            $roleName = !empty($role) ? $role['name'] : $this->translateService->getTranslate('Role is missing');
            $userName = !empty($user) ? $user['uname'] : $this->translateService->getTranslate('User is missing');

            $data[$idx]['rname'] = $roleName;
            $data[$idx]['uname'] = $userName;
        }

        usort($data, static fn ($a, $b) => strnatcmp($a['uname'], $b['uname']));

        return $data;
    }

    /**
     * Get user roles in passed projects
     *
     * @param string[] $projects
     *
     * @return array<string, array<string, mixed>>
     */
    public function getUsersRolesInProjects(array $projects = []): array
    {
        $result = [];
        $data = $this->getListByFilter(['project_id' => ['IN', $projects, IQueryBuilder::PARAM_STR_ARRAY]]);
        $dataLinked = $this->getLinkedList(['project_id' => ['IN', $projects, IQueryBuilder::PARAM_STR_ARRAY]]);

        foreach ($data as $idx => $item) {
            $itemLinked = $dataLinked[$idx];
            $user = $item['user_id'];
            $project = $item['project_id'];
            $role = $itemLinked['role_id'] ?? '';

            $result[$project][$user][] = $role;
        }

        return $result;
    }

    /**
     * Get projects user participates in
     *
     * @param string $userId
     *
     * @return array<int, array{slug: string, slug_type: int|string, name: string, id: string}>
     */
    public function getUserProjects(string $userId): array
    {
        $result = $checkedProjects = [];

        $data = $this->getListByFilter(['user_id' => $userId]);

        $projectsIds = BaseService::getField($data, 'project_id', true);
        $projects = (new Project_Model())->getIndexedListByFilter(
            'id',
            ['id' => ['IN', $projectsIds, IQueryBuilder::PARAM_STR_ARRAY]]
        );

        foreach ($data as $row) {
            $projectId = $row['project_id'];

            if (isset($checkedProjects[$projectId]) || !isset($projects[$projectId])) {
                continue;
            }

            $result[] = [
                'slug'      => $projects[$projectId]['slug'] ?? '',
                'slug_type' => $projects[$projectId]['slug_type'] ?? '',
                'name'      => $projects[$projectId]['name'] ?? '',
                'id'        => $projects[$projectId]['id'] ?? '',
            ];

            $checkedProjects[$projectId] = $projectId;
        }

        return $result;
    }

    /**
     * Get projects user participates in, sorted by last usage
     *
     * @param string $userId
     * @param int    $recentCount
     *
     * @return array<int, array{slug: string, slug_type: int|string, name: string, id: string, is_separator?: bool}>
     */
    public function getUserProjectsForReport(string $userId, int $recentCount = 3): array
    {
        $allProjects = $this->getUserProjects($userId);

        if (empty($allProjects)) {
            return [];
        }

        $recentProjectIds = $this->getLastUsedProjectIds($userId, $recentCount);

        $projectsMap = [];

        foreach ($allProjects as $project) {
            $projectsMap[$project['id']] = $project;
        }

        $recentProjectsList = [];
        $otherProjects = [];

        foreach ($recentProjectIds as $projectId) {
            if (isset($projectsMap[$projectId])) {
                $recentProjectsList[] = $projectsMap[$projectId];
            }
        }

        foreach ($allProjects as $project) {
            if (!\in_array($project['id'], $recentProjectIds)) {
                $otherProjects[] = $project;
            }
        }

        usort($otherProjects, static fn ($a, $b) => strcmp($a['name'], $b['name']));

        $result = $recentProjectsList;

        // Add a separator if there are both the latest and the other projects
        if (!empty($recentProjectsList) && !empty($otherProjects)) {
            $result[] = [
                'id'           => 'separator',
                'name'         => '──────────────',
                'is_separator' => true,
                'slug'         => 'separator',
                'slug_type'    => 0,
            ];
        }

        $result = array_merge($result, $otherProjects);

        return $result;
    }

    /**
     * Get last used project IDs by user
     *
     * @param string $userId
     * @param int    $count
     *
     * @return array<int, string>
     */
    private function getLastUsedProjectIds(string $userId, int $count): array
    {
        $timesModel = new Times_Model();

        $reports = $timesModel->getListByFilter(
            ['user_id' => $userId],
            ['project_id', 'created_at'],
            ['created_at', 'DESC']
        );

        $uniqueProjectIds = [];

        foreach ($reports as $report) {
            $projectId = $report['project_id'];

            if (!\in_array($projectId, $uniqueProjectIds) && \count($uniqueProjectIds) < $count) {
                $uniqueProjectIds[] = $projectId;
            }
        }

        return $uniqueProjectIds;
    }
}
