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
 * Adds depersonalized external-link columns to time records so an integration
 * module can mirror a record into an external system and later find/update it.
 */
class Version1100Date20261006000001 extends SimpleMigrationStep
{
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('done_times_data')) {
            return null;
        }

        $table = $schema->getTable('done_times_data');

        if (!$table->hasColumn('external_report_id')) {
            $table->addColumn('external_report_id', 'string', [
                'notnull' => false,
                'length'  => 64,
                'default' => null,
                'comment' => 'ID of the mirrored record in an external system (set by an integration module).',
            ]);
        }

        if (!$table->hasColumn('external_source')) {
            $table->addColumn('external_source', 'string', [
                'notnull' => false,
                'length'  => 64,
                'default' => null,
                'comment' => 'Integration key that owns external_report_id.',
            ]);
        }

        return $schema;
    }
}
