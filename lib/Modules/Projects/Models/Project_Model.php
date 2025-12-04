<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Projects\Models;

use OCA\Done\Models\Base_Model;
use OCA\Done\Models\Dictionaries\Customers_Model;
use OCA\Done\Models\Dictionaries\Direction_Model;
use OCA\Done\Models\Dictionaries\Stages_Model;
use OCA\Done\Models\User_Model;
use OCA\Done\Models\UsersRolesInProjects_Model;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class Project_Model.
 */
class Project_Model extends Base_Model
{
    public string $table = 'done_projects';
    public string $modelTitle = 'Projects';
    public string $modelName = 'projectsModel';
    public string $dbTableComment = 'Company projects: core data, planning, management, budgeting. Uses soft-delete.';

    protected array $hashFields = [
        'name',
        'nickname',
        'direction_id',
        'owner_id',
        'stage_id',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Unique identifier for a project',
        ],
        'name' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Title',
            'required'         => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Project name',
            'show'       => true,
        ],
        'nickname' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Short project name',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Short name or codename for the project',
            'show'       => true,
        ],
        'direction_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Direction',
            'link'       => Direction_Model::class,
            'required'   => false,
            'permission' => true,
            'db_comment' => 'Direction ID. References oc_done_directions.id',
            'show'       => true,
        ],
        'owner_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Project owner',
            'link'       => User_Model::class,
            'required'   => true,
            'permission' => true,
            'db_comment' => 'Project owner ID. References oc_done_users_data.id',
            'show'       => true,
        ],
        'stage_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Project stage',
            'link'       => Stages_Model::class,
            'required'   => false,
            'permission' => true,
            'db_comment' => 'Project stage ID. References oc_done_stages.id',
            'show'       => true,
        ],
        'customer_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Customer',
            'link'       => Customers_Model::class,
            'required'   => false,
            'permission' => true,
            'db_comment' => 'Customer ID. References oc_done_customers.id',
            'show'       => true,
        ],
        'project_manager_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Project manager',
            'link'       => User_Model::class,
            'required'   => false,
            'permission' => true,
            'db_comment' => 'Project manager ID. References oc_done_users_data.id',
            'show'       => true,
        ],
        'project_head_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Project head',
            'link'       => User_Model::class,
            'required'   => false,
            'permission' => true,
            'db_comment' => 'Project head ID. References oc_done_users_data.id',
            'show'       => true,
        ],
        'created_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Project created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC',
        ],
        'updated_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Project updated at',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC',
        ],
        'deleted' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Deleted',
            'db_comment' => 'Soft-delete flag (1 - deleted, 0 - active). Deleted records should be excluded from queries.',
        ],
    ];

    public array $linkedModels = [
        UsersRolesInProjects_Model::class => [
            'title'         => 'Employees',
            'frontend_type' => 'staff',
            'filter_field'  => 'project_id',
            'key_field'     => 'user_id',
        ],
    ];

    public string $appearanceModel = ProjectAppearance_Model::class;

    /**
     * @override
     */
    public function validateData(array $data, bool $save = false, array $ignoreFields = []): array
    {
        [$data, $errors] = parent::validateData($data, $save, $ignoreFields);

        if (isset($data['nickname']) && !preg_match('/^[a-zA-Z0-9\-._]+$/', $data['nickname'])) {
            $errors[] = $this->translateService->getTranslate(
                'Invalid characters found in field «%s»',
                ['nickname']
            );
        }

        return [$data, $errors];
    }

    /**
     * Get project by its ID
     *
     * @param string $projectId
     *
     * @return array<string, mixed>
     */
    public function getProject(string $projectId): array
    {
        $usersIds = $directionsIds = $stagesIds = $customersIds = [];
        $projectIds = [];

        $project = $this->getItem($projectId);

        $directionsIds[$project['direction_id']] = $project['direction_id'];
        $usersIds[$project['owner_id']] = $project['owner_id'];
        $usersIds[$project['project_manager_id']] = $project['project_manager_id'];
        $usersIds[$project['project_head_id']] = $project['project_head_id'];
        $stagesIds[$project['stage_id']] = $project['stage_id'];
        $customersIds[$project['customer_id']] = $project['customer_id'];
        $projectIds[] = $projectId;

        [
            $directions,
            $users,
            $stages,
            $customers,
            $appearances,
        ] = $this->getAuxiliaryData($directionsIds, $usersIds, $stagesIds, $customersIds, $projectIds);

        $project['direction_name'] = $directions[$project['direction_id']]['name'] ?? $this->translateService->getTranslate(
            'Without direction'
        );
        $project['owner_name'] = $users[$project['owner_id']]['user_display_name'] ?? $this->translateService->getTranslate(
            'Without owner'
        );
        $project['pm_name'] = $users[$project['project_manager_id']]['user_display_name'] ?? $this->translateService->getTranslate(
            'Without a project manager'
        );
        $project['ph_name'] = $users[$project['project_head_id']]['user_display_name'] ?? $this->translateService->getTranslate(
            'Without head'
        );
        $project['stage_name'] = $stages[$project['stage_id']]['name'] ?? $this->translateService->getTranslate(
            'Without stage'
        );
        $project['customer_name'] = $customers[$project['customer_id']]['name'] ?? $this->translateService->getTranslate(
            'Without customer'
        );

        $appearance = $appearances[$projectId] ?? [];
        $project['avatar'] = $appearance['avatar'] ?? null;
        $project['symbol'] = $appearance['symbol'] ?? null;
        $project['bg_image'] = $appearance['bg_image'] ?? null;
        $project['color'] = $appearance['color'] ?? null;

        return $project;
    }

    /**
     * Get all projects
     *
     * @return array<int, mixed>
     */
    public function getProjects(): array
    {
        $usersIds = $directionsIds = $stagesIds = $customersIds = [];
        $projectIds = [];

        $projects = $this->getListByFilter([], ['*'], ['name', 'ASC']);

        foreach ($projects as $project) {
            $directionsIds[$project['direction_id']] ??= $project['direction_id'];
            $usersIds[$project['owner_id']] ??= $project['owner_id'];
            $usersIds[$project['project_manager_id']] ??= $project['project_manager_id'];
            $usersIds[$project['project_head_id']] ??= $project['project_head_id'];
            $stagesIds[$project['stage_id']] ??= $project['stage_id'];
            $customersIds[$project['customer_id']] ??= $project['customer_id'];
            $projectIds[] = $project['id'];
        }

        [
            $directions,
            $users,
            $stages,
            $customers,
            $appearances,
        ] = $this->getAuxiliaryData($directionsIds, $usersIds, $stagesIds, $customersIds, $projectIds);

        foreach ($projects as $idx => $project) {
            $projects[$idx]['direction_name'] = $directions[$project['direction_id']]['name'] ?? $this->translateService->getTranslate(
                'Without direction'
            );
            $projects[$idx]['owner_name'] = $users[$project['owner_id']]['user_display_name'] ?? $this->translateService->getTranslate(
                'Without owner'
            );
            $projects[$idx]['pm_name'] = $users[$project['project_manager_id']]['user_display_name'] ?? $this->translateService->getTranslate(
                'Without a project manager'
            );
            $projects[$idx]['ph_name'] = $users[$project['project_head_id']]['user_display_name'] ?? $this->translateService->getTranslate(
                'Without head'
            );
            $projects[$idx]['stage_name'] = $stages[$project['stage_id']]['name'] ?? $this->translateService->getTranslate(
                'Without stage'
            );
            $projects[$idx]['customer_name'] = $customers[$project['customer_id']]['name'] ?? $this->translateService->getTranslate(
                'Without customer'
            );

            $appearance = $appearances[$project['id']] ?? [];
            $projects[$idx]['avatar'] = $appearance['avatar'] ?? null;
            $projects[$idx]['symbol'] = $appearance['symbol'] ?? null;
            $projects[$idx]['bg_image'] = $appearance['bg_image'] ?? null;
            $projects[$idx]['color'] = $appearance['color'] ?? null;

            $projects[$idx] = $this->addSlugToItem($projects[$idx]);
        }

        return $projects;
    }

    /**
     * Get auxiliary parameters for projects
     *
     * @param string[] $directionsIds
     * @param string[] $usersIds
     * @param string[] $stagesIds
     * @param string[] $customersIds
     * @param string[] $projectIds
     *
     * @return array
     */
    public function getAuxiliaryData(
        array $directionsIds,
        array $usersIds,
        array $stagesIds,
        array $customersIds,
        array $projectIds = []
    ): array {
        return [
            (new Direction_Model())->getIndexedListByFilter(
                'id',
                ['id' => ['IN', $directionsIds, IQueryBuilder::PARAM_STR_ARRAY]],
                ['id', 'name']
            ),
            (new User_Model())->getIndexedListByFilter(
                'id',
                ['id' => ['IN', $usersIds, IQueryBuilder::PARAM_STR_ARRAY]],
                ['id', 'name', 'user_display_name']
            ),
            (new Stages_Model())->getIndexedListByFilter(
                'id',
                ['id' => ['IN', $stagesIds, IQueryBuilder::PARAM_STR_ARRAY]],
                ['id', 'name']
            ),
            (new Customers_Model())->getIndexedListByFilter(
                'id',
                ['id' => ['IN', $customersIds, IQueryBuilder::PARAM_STR_ARRAY]],
                ['id', 'name']
            ),
            (new ProjectAppearance_Model())->getIndexedListByFilter(
                'project_id',
                ['project_id' => ['IN', $projectIds, IQueryBuilder::PARAM_STR_ARRAY]],
                ['project_id', 'avatar', 'symbol', 'bg_image', 'color']
            ),
        ];
    }
}
