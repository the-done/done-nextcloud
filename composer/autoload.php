<?php
declare(strict_types=1);

// Check if vendor autoload exists before requiring it
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
	require_once $vendorAutoload;
}