#!/usr/bin/env php
<?php
/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 *
 * Standalone app signing script that doesn't require Nextcloud occ or database connection.
 * This script generates the same signature format as Nextcloud's integrity:sign-app command.
 */

function showUsage() {
    echo "Usage: sign-app.php --privateKey=<path> --certificate=<path> --path=<app-path>\n";
    echo "\n";
    echo "Options:\n";
    echo "  --privateKey   Path to private key file\n";
    echo "  --certificate  Path to certificate file\n";
    echo "  --path         Path to the app directory to sign\n";
    exit(1);
}

// Parse command line arguments
$options = getopt('', ['privateKey:', 'certificate:', 'path:']);

if (!isset($options['privateKey']) || !isset($options['certificate']) || !isset($options['path'])) {
    showUsage();
}

$privateKeyPath = $options['privateKey'];
$certificatePath = $options['certificate'];
$appPath = rtrim($options['path'], '/');

// Validate inputs
if (!file_exists($privateKeyPath)) {
    fwrite(STDERR, "Error: Private key not found: $privateKeyPath\n");
    exit(1);
}

if (!file_exists($certificatePath)) {
    fwrite(STDERR, "Error: Certificate not found: $certificatePath\n");
    exit(1);
}

if (!is_dir($appPath)) {
    fwrite(STDERR, "Error: App directory not found: $appPath\n");
    exit(1);
}

// Load private key and certificate
$privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
if ($privateKey === false) {
    fwrite(STDERR, "Error: Failed to load private key\n");
    exit(1);
}

$certificate = file_get_contents($certificatePath);
if ($certificate === false) {
    fwrite(STDERR, "Error: Failed to load certificate\n");
    exit(1);
}

echo "Signing app at: $appPath\n";

// Recursively get all files
function getFiles($dir, $basePath) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $filePath = $file->getPathname();
            // Get relative path
            $relativePath = substr($filePath, strlen($basePath) + 1);
            // Skip signature.json if it exists
            if ($relativePath !== 'appinfo/signature.json') {
                $files[] = $relativePath;
            }
        }
    }

    sort($files);
    return $files;
}

// Calculate file hashes
echo "Calculating file hashes...\n";
$files = getFiles($appPath, $appPath);
$hashes = [];

foreach ($files as $file) {
    $filePath = $appPath . '/' . $file;
    $hash = hash_file('sha512', $filePath);
    if ($hash === false) {
        fwrite(STDERR, "Error: Failed to hash file: $file\n");
        exit(1);
    }
    $hashes[$file] = $hash;
}

echo "Hashed " . count($hashes) . " files\n";

// Create signature data
$signatureData = [
    'hashes' => $hashes,
    'signature' => '',
    'certificate' => $certificate
];

// Sign the hashes
$hashesSorted = $signatureData['hashes'];
ksort($hashesSorted);
$hashesJson = json_encode($hashesSorted);

$signature = '';
if (!openssl_sign($hashesJson, $signature, $privateKey, OPENSSL_ALGO_SHA512)) {
    fwrite(STDERR, "Error: Failed to sign data\n");
    exit(1);
}

$signatureData['signature'] = base64_encode($signature);

// Ensure appinfo directory exists
$appinfoDir = $appPath . '/appinfo';
if (!is_dir($appinfoDir)) {
    if (!mkdir($appinfoDir, 0755, true)) {
        fwrite(STDERR, "Error: Failed to create appinfo directory\n");
        exit(1);
    }
}

// Write signature file
$signatureFile = $appinfoDir . '/signature.json';
$signatureJson = json_encode($signatureData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (file_put_contents($signatureFile, $signatureJson) === false) {
    fwrite(STDERR, "Error: Failed to write signature file: $signatureFile\n");
    exit(1);
}

echo "✓ App signed successfully\n";
echo "Signature file: $signatureFile\n";

openssl_free_key($privateKey);
exit(0);
