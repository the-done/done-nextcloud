<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Pro;

/**
 * Installs paid module packages into lib/Modules.
 *
 * The package is a zip whose top-level directories are module names from
 * ALLOWED_MODULES (Agreement/, Finances/, Teams/, Vacations/). Extraction is
 * guarded against zip-slip; installation is atomic with rollback.
 */
class ProModuleInstaller
{
    public const ALLOWED_MODULES = ['Agreement', 'Finances', 'Teams', 'Vacations'];

    public function __construct(
        private readonly string $modulesDir,
        private readonly string $tmpDir,
    ) {}

    /**
     * Validate the package without touching the target directory.
     *
     * @return list<string> module names present in the package
     *
     * @throws \RuntimeException on any unsafe or unexpected entry
     */
    public function validate(string $zipPath): array
    {
        if (!is_file($zipPath)) {
            throw new \RuntimeException('package_not_found');
        }
        $zip = new \ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('package_unreadable');
        }
        $modules = [];

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string)$zip->statIndex($i)['name'];

                if ($name === '' || $name[0] === '/' || str_contains($name, '..')
                    || str_contains($name, '\\') || str_contains($name, "\0")) {
                    throw new \RuntimeException('package_unsafe_entry');
                }
                $opsys = 0;
                $attr = 0;

                if ($zip->getExternalAttributesIndex($i, $opsys, $attr)) {
                    $mode = $attr >> 16;

                    if ($opsys === \ZipArchive::OPSYS_UNIX && ($mode & 0xF000) === 0xA000) {
                        throw new \RuntimeException('package_symlink');
                    }
                }
                $top = explode('/', $name)[0];

                if (!\in_array($top, self::ALLOWED_MODULES, true)) {
                    throw new \RuntimeException('package_unknown_module');
                }

                if (!\in_array($top, $modules, true)) {
                    $modules[] = $top;
                }
            }
        } finally {
            $zip->close();
        }

        if ($modules === []) {
            throw new \RuntimeException('package_empty');
        }

        return $modules;
    }

    /**
     * @return list<string> installed module names
     *
     * @throws \RuntimeException
     */
    public function install(string $zipPath): array
    {
        $modules = $this->validate($zipPath);

        if (!is_dir($this->modulesDir) || !is_writable($this->modulesDir)) {
            throw new \RuntimeException('modules_dir_not_writable');
        }

        $staging = $this->tmpDir . '/done-pro-' . bin2hex(random_bytes(6));

        if (!mkdir($staging, 0700, true) && !is_dir($staging)) {
            throw new \RuntimeException('staging_failed');
        }

        $backups = [];   // module => backup path
        $installed = [];  // modules already moved into place

        try {
            $zip = new \ZipArchive();

            if ($zip->open($zipPath) !== true) {
                throw new \RuntimeException('package_unreadable');
            }
            $ok = $zip->extractTo($staging);
            $zip->close();

            if (!$ok) {
                throw new \RuntimeException('extract_failed');
            }

            foreach ($modules as $module) {
                $controller = $staging . '/' . $module . '/Controller/' . $module . 'Controller.php';

                if (!is_file($controller)) {
                    throw new \RuntimeException('module_incomplete:' . $module);
                }
            }

            foreach ($modules as $module) {
                $target = $this->modulesDir . '/' . $module;

                if (file_exists($target)) {
                    $backup = $target . '.bak-' . bin2hex(random_bytes(4));

                    if (!rename($target, $backup)) {
                        throw new \RuntimeException('backup_failed:' . $module);
                    }
                    $backups[$module] = $backup;
                }

                if (!rename($staging . '/' . $module, $target)) {
                    throw new \RuntimeException('install_failed:' . $module);
                }
                $installed[] = $module;
            }
        } catch (\Throwable $e) {
            foreach ($installed as $module) {
                self::rrmdir($this->modulesDir . '/' . $module);
            }

            foreach ($backups as $module => $backup) {
                @rename($backup, $this->modulesDir . '/' . $module);
            }
            self::rrmdir($staging);
            throw $e;
        }

        foreach ($backups as $backup) {
            self::rrmdir($backup);
        }
        self::rrmdir($staging);
        $this->refreshRuntime();

        return $installed;
    }

    /**
     * Remove installed pro modules (revert to demo).
     *
     * @param list<string> $modules
     *
     * @return list<string> removed module names
     */
    public function uninstall(array $modules): array
    {
        $removed = [];

        foreach ($modules as $module) {
            if (!\in_array($module, self::ALLOWED_MODULES, true)) {
                continue;
            }
            $target = $this->modulesDir . '/' . $module;

            if (is_dir($target)) {
                self::rrmdir($target);
                $removed[] = $module;
            }
        }

        if ($removed !== []) {
            $this->refreshRuntime();
        }

        return $removed;
    }

    private function refreshRuntime(): void
    {
        if (\function_exists('opcache_reset')) {
            @opcache_reset();
        }

        if (\function_exists('apcu_clear_cache')) {
            @apcu_clear_cache();
        }
    }

    private static function rrmdir(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item !== '.' && $item !== '..') {
                self::rrmdir($path . '/' . $item);
            }
        }
        @rmdir($path);
    }
}
