#!/usr/bin/env php
<?php
/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 *
 * This script exports certificate data from environment variables to files.
 * Used in CI/CD pipelines to set up signing certificates.
 */

$appName = 'done';
$certDir = getenv('HOME') . '/.nextcloud/certificates';
$privateKeyEnv = getenv('APP_PRIVATE_KEY');
$publicCertEnv = getenv('APP_PUBLIC_CRT');

// Create certificate directory if it doesn't exist
if (!is_dir($certDir)) {
    if (!mkdir($certDir, 0700, true)) {
        fwrite(STDERR, "Error: Failed to create certificate directory: $certDir\n");
        exit(1);
    }
}

// Export private key
if ($privateKeyEnv) {
    $keyPath = "$certDir/$appName.key";
    if (file_put_contents($keyPath, $privateKeyEnv) === false) {
        fwrite(STDERR, "Error: Failed to write private key to: $keyPath\n");
        exit(1);
    }
    chmod($keyPath, 0600);
    echo "Private key exported to: $keyPath\n";
} else {
    fwrite(STDERR, "Warning: APP_PRIVATE_KEY environment variable not set\n");
}

// Export public certificate
if ($publicCertEnv) {
    $certPath = "$certDir/$appName.crt";
    if (file_put_contents($certPath, $publicCertEnv) === false) {
        fwrite(STDERR, "Error: Failed to write certificate to: $certPath\n");
        exit(1);
    }
    chmod($certPath, 0644);
    echo "Public certificate exported to: $certPath\n";
} else {
    fwrite(STDERR, "Warning: APP_PUBLIC_CRT environment variable not set\n");
}

exit(0);
