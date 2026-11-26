<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Migration;

use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCA\Done\Models\Dictionaries\GlobalRolesModel;
use OCA\Done\Models\GlobalRoleActionRightsModel;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Server;

/**
 * Fresh installs run schema-only migrations, so their data hooks must run separately.
 * Also repairs installations where both role dictionaries were left entirely empty.
 */
class InitializeAppData implements IRepairStep
{
    public function __construct(private IDBConnection $connection) {}

    public function getName(): string
    {
        return 'Initialize Done application data';
    }

    public function run(IOutput $output): void
    {
        // Never replay historical permission changes over a configured installation.
        // getList includes soft-deleted roles, which must also prevent reseeding.
        if (!$this->hasEmptyRoleData()) {
            return;
        }

        $this->connection->beginTransaction();

        try {
            $this->initializeData($output);
            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }

        $output->info('Initialized Done default roles, permissions and dictionaries.');
    }

    protected function hasEmptyRoleData(): bool
    {
        return (new GlobalRolesModel())->getList() === []
            && (new GlobalRoleActionRightsModel())->getList() === [];
    }

    protected function initializeData(IOutput $output): void
    {
        // Reuse the existing data definitions, in migration order, without rerunning
        // schema changes or changing Nextcloud's migration history.
        $schema = new SchemaWrapper(Server::get(Connection::class));
        $schemaClosure = static fn () => $schema;
        $steps = [
            Version1000Date20251017000001::class,
            Version1020Date20251202000001::class,
            Version1030Date20251209000001::class,
            Version1050Date20260112000001::class,
            Version1060Date20260122000001::class,
            Version1080Date20260515000001::class,
        ];

        foreach ($steps as $step) {
            (new $step($this->connection))->postSchemaChange($output, $schemaClosure, []);
        }
    }
}
