<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


namespace OCA\Done\Models;

use OCA\Done\Modules\BaseModuleService;
use OCA\Done\Modules\Finances\Model\Payments_Model;
use OCA\Done\Modules\Projects\Models\Project_Model;
use OCA\Done\Modules\Teams\Models\Teams_Model;

/**
 * Class PermissionsEntities_Model.
 */
class PermissionsEntities_Model
{
    public const USER_ENTITY = 1;
    public const PROJECT_ENTITY = 2;
    public const TEAM_ENTITY = 3;
    public const PAYMENTS_ENTITY = 4;

    public static function getPermissionsEntities(int $source = null): array
    {
        $entities = [
            self::USER_ENTITY    => [
                'slug'        => 'user_card',
                'foreign_key' => 'user_id',
                'entity_name' => 'User card',
                'model'       => User_Model::class,
            ],
            self::PROJECT_ENTITY => [
                'slug'        => 'project_card',
                'foreign_key' => 'project_id',
                'entity_name' => 'Project card',
                'model'       => Project_Model::class,
            ],
        ];

        if (BaseModuleService::moduleExists('teams')) {
            $entities[self::TEAM_ENTITY] = [
                'slug'        => 'team_card',
                'foreign_key' => 'team_id',
                'entity_name' => 'Team card',
                'model'       => Teams_Model::class,
            ];
        }

        if (BaseModuleService::moduleExists('finances')) {
            $entities[self::PAYMENTS_ENTITY] = [
                'slug'        => 'payment_card',
                'foreign_key' => 'payment_id',
                'entity_name' => 'Payment card',
                'model'       => Payments_Model::class,
            ];
        }

        if (isset($source) && array_key_exists($source, $entities)) {
            return [$source => $entities[$source]];
        }

        return $entities;
    }

    public static function entityExists(int $source = null): bool
    {
        return in_array($source, [
            self::USER_ENTITY,
            self::PROJECT_ENTITY,
            self::TEAM_ENTITY,
            self::PAYMENTS_ENTITY,
        ]);
    }
}