<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1070Date20260422000001 extends SimpleMigrationStep
{
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $table = $schema->getTable('done_agr_requests');

        if (!$table->hasColumn('project_id')) {
            $table->addColumn('project_id', 'string', [
                'notnull' => false,
                'default' => null,
                'length'  => 32,
                'comment' => 'Optional project ID associated with this request. References oc_done_projects.id',
            ]);
            $table->addIndex(['project_id'], 'agr_req_project_id_idx');
        }

        return $schema;
    }
}
