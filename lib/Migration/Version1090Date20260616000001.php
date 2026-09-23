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

/**
 * Adds a half_day flag to vacation requests. When set, the last day of the
 * absence counts as half a day, so the total length is (calendar days − 0.5).
 */
class Version1090Date20260616000001 extends SimpleMigrationStep
{
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('done_vacations')) {
            $table = $schema->getTable('done_vacations');

            if (!$table->hasColumn('half_day')) {
                $table->addColumn('half_day', 'boolean', [
                    'notnull' => false,
                    'default' => false,
                    'comment' => 'When true, the last day of the absence is a half day (total length = calendar days - 0.5).',
                ]);
            }
        }

        return $schema;
    }
}
