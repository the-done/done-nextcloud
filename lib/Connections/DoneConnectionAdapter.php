<?php

namespace OCA\Done\Connections;

use OCP\IDBConnection;

class DoneConnectionAdapter {
    public IDBConnection $db;

    public function __construct() {
        $connection = \OC::$server->get(IDBConnection::class);
        $this->db = $connection;
    }

    public function getInstance() {
        return $this->db;
    }
}