<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Connections;

use OCP\IDBConnection;

class DoneConnectionAdapter
{
    public IDBConnection $db;

    public function __construct()
    {
        $connection = \OC::$server->get(IDBConnection::class);
        $this->db = $connection;
    }

    public function getInstance(): IDBConnection
    {
        return $this->db;
    }
}
