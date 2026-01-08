<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Migration;

use OCA\Done\Models\Dictionaries\GlobalRolesModel;
use OCA\Done\Models\GlobalRoleActionRightsModel;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1030Date20251209000001 extends SimpleMigrationStep
{
    protected IDBConnection $connection;

    public function __construct(IDBConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param IOutput                    $output
     * @param \Closure(): ISchemaWrapper $schemaClosure
     * @param array                      $options
     *
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        return $schemaClosure();
    }

    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void
    {
        $globalRolesModel = new GlobalRolesModel();
        $globalRoleActionRightsModel = new GlobalRoleActionRightsModel();

        if (
            $this->connection->tableExists($globalRolesModel->table)
            && empty($globalRolesModel->getItem(GlobalRolesModel::AI_CHAT))
        ) {
            $query = $this->connection->getQueryBuilder();
            $query->insert($globalRolesModel->table);
            $query->values([
                'id'   => $query->createNamedParameter(GlobalRolesModel::AI_CHAT, IQueryBuilder::PARAM_INT),
                'name' => $query->createNamedParameter('AI Chat', IQueryBuilder::PARAM_STR),
                'sort' => $query->createNamedParameter(7, IQueryBuilder::PARAM_INT),
            ]);
            $query->execute();
        }

        if (
            $this->connection->tableExists($globalRoleActionRightsModel->table)
            && empty($globalRoleActionRightsModel->getItemByFilter(['global_role_id' => GlobalRolesModel::AI_CHAT]))
        ) {
            $dataToSave = [
                'global_role_id' => GlobalRolesModel::AI_CHAT,
                'action'         => GlobalRolesModel::CAN_VIEW_AI_CHAT,
                'can'            => true,
            ];

            $globalRoleActionRightsModel->addData($dataToSave);
        }
    }
}
