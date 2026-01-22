<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Models;

use OCA\Done\Modules\BaseModuleService;
use OCA\Done\Modules\Finances\Model\ContractParameterGroupsModel;
use OCA\Done\Modules\Finances\Model\ContractParametersModel;
use OCA\Done\Modules\Finances\Model\ContractsModel;
use OCA\Done\Modules\Finances\Model\PaymentsModel;
use OCA\Done\Modules\Projects\Models\ProjectModel;
use OCA\Done\Modules\Teams\Models\TeamsModel;

/**
 * Class PermissionsEntitiesModel.
 */
class PermissionsEntitiesModel
{
    public const USER_ENTITY = 1;
    public const PROJECT_ENTITY = 2;
    public const TEAM_ENTITY = 3;
    public const PAYMENTS_ENTITY = 4;
    public const FYN_CONTRACTS_ENTITY = 5;
    public const FYN_CONTRACTS_PARAMETERS_ENTITY = 6;
    public const FYN_CONTRACTS_PARAMETER_GROUPS_ENTITY = 7;

    public static function getPermissionsEntities(?int $source = null): array
    {
        $entities = [
            self::USER_ENTITY => [
                'slug'        => 'user_card',
                'foreign_key' => 'user_id',
                'entity_name' => 'User card',
                'model'       => UserModel::class,
            ],
            self::PROJECT_ENTITY => [
                'slug'        => 'project_card',
                'foreign_key' => 'project_id',
                'entity_name' => 'Project card',
                'model'       => ProjectModel::class,
            ],
        ];

        if (BaseModuleService::moduleExists('teams')) {
            $entities[self::TEAM_ENTITY] = [
                'slug'        => 'team_card',
                'foreign_key' => 'team_id',
                'entity_name' => 'Team card',
                'model'       => TeamsModel::class,
            ];
        }

        if (BaseModuleService::moduleExists('finances')) {
            $entities[self::PAYMENTS_ENTITY] = [
                'slug'        => 'payment_card',
                'foreign_key' => 'payment_id',
                'entity_name' => 'Payment card',
                'model'       => PaymentsModel::class,
            ];

            $entities[self::FYN_CONTRACTS_ENTITY] = [
                'slug'        => 'contract_card',
                'foreign_key' => 'contract_id',
                'entity_name' => 'Contract card',
                'model'       => ContractsModel::class,
            ];

            $entities[self::FYN_CONTRACTS_PARAMETERS_ENTITY] = [
                'slug'        => 'contract_parameter_card',
                'foreign_key' => 'contract_parameter_id',
                'entity_name' => 'Contract parameter card',
                'model'       => ContractParametersModel::class,
            ];

            $entities[self::FYN_CONTRACTS_PARAMETER_GROUPS_ENTITY] = [
                'slug'        => 'contract_parameter_group_card',
                'foreign_key' => 'contract_parameter_group_id',
                'entity_name' => 'Contract parameter group card',
                'model'       => ContractParameterGroupsModel::class,
            ];
        }

        if (isset($source) && \array_key_exists($source, $entities)) {
            return [$source => $entities[$source]];
        }

        return $entities;
    }

    public static function entityExists(?int $source = null): bool
    {
        return \in_array($source, [
            self::USER_ENTITY,
            self::PROJECT_ENTITY,
            self::TEAM_ENTITY,
            self::PAYMENTS_ENTITY,
            self::FYN_CONTRACTS_ENTITY,
            self::FYN_CONTRACTS_PARAMETERS_ENTITY,
            self::FYN_CONTRACTS_PARAMETER_GROUPS_ENTITY,
        ]);
    }
}
