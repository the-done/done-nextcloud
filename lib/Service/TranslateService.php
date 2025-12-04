<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Service;

use OCP\IL10N;
use OCP\Server;

class TranslateService
{
    /** @var IL10N */
    public IL10N $l10n;

    /** @var TranslateService */
    private static TranslateService $instance;

    // Context hierarchy for contextual translations
    private const CONTEXT_HIERARCHY = [
        'admin/projects'     => ['admin/projects', 'admin', 'global'],
        'admin/users'        => ['admin/users', 'admin', 'global'],
        'admin/finances'     => ['admin/finances', 'admin', 'global'],
        'user/time-tracking' => ['user/time-tracking', 'user', 'global'],
    ];

    // Paths to contextual translation files for each context
    private const CONTEXTUAL_TRANSLATION_FILES = [
        'admin/projects'     => 'l10n/contexts/admin/projects',
        'admin/users'        => 'l10n/contexts/admin/users',
        'admin/finances'     => 'l10n/contexts/admin/finances',
        'user/time-tracking' => 'l10n/contexts/user/time-tracking',
    ];

    public function __construct(
        IL10N $l10n,
    ) {
        $this->l10n = $l10n;
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = Server::get(self::class);
        }

        return self::$instance;
    }

    /**
     * Main method for getting translations - uses native Nextcloud system
     */
    public function getTranslate(
        ?string $key,
        array | string $options = []
    ): string {
        if (!$key) {
            return '';
        }

        return $this->l10n->t($key, $options);
    }

    /**
     * Get translation with context consideration (contextual translations + native Nextcloud system)
     */
    public function getContextualTranslation(string $key, string $context, array $parameters = []): string
    {
        // Get context hierarchy
        $hierarchy = self::CONTEXT_HIERARCHY[$context] ?? [$context, 'global'];

        foreach ($hierarchy as $ctx) {
            // Load contextual translations from file for this context
            $contextualTranslations = $this->loadContextualTranslationsFromFile($ctx);

            if (isset($contextualTranslations[$key])) {
                $translation = $contextualTranslations[$key];

                // Substitute parameters
                foreach ($parameters as $param => $value) {
                    $translation = str_replace('{' . $param . '}', $value, $translation);
                }

                return $translation;
            }
        }

        // Fallback to native Nextcloud system
        return $this->l10n->t($key, $parameters);
    }

    /**
     * Load contextual translations from files for specified context
     * Supports both .js and .json files
     */
    private function loadContextualTranslationsFromFile(string $context): array
    {
        $contextPath = self::CONTEXTUAL_TRANSLATION_FILES[$context] ?? null;

        if (!$contextPath) {
            return [];
        }

        $appPath = \OC::$SERVERROOT . '/apps/done/';
        $fullPath = $appPath . $contextPath;

        // Check directory existence
        if (!is_dir($fullPath)) {
            return [];
        }

        $translations = [];
        $currentLanguage = $this->l10n->getLanguageCode();

        // First try JSON file
        $jsonFile = $fullPath . '/' . $currentLanguage . '.json';

        if (file_exists($jsonFile)) {
            $translations = $this->parseJsonTranslationFile($jsonFile);
        } else {
            // Fallback to JS file
            $jsFile = $fullPath . '/' . $currentLanguage . '.js';

            if (file_exists($jsFile)) {
                $translations = $this->parseJsTranslationFile($jsFile);
            } else {
                // Fallback to English JSON
                $englishJsonFile = $fullPath . '/en.json';

                if (file_exists($englishJsonFile)) {
                    $translations = $this->parseJsonTranslationFile($englishJsonFile);
                } else {
                    // Fallback to English JS
                    $englishJsFile = $fullPath . '/en.js';

                    if (file_exists($englishJsFile)) {
                        $translations = $this->parseJsTranslationFile($englishJsFile);
                    }
                }
            }
        }

        return $translations;
    }

    /**
     * Parse .json contextual translation file
     */
    private function parseJsonTranslationFile(string $filePath): array
    {
        $content = file_get_contents($filePath);

        if (!$content) {
            return [];
        }

        $data = json_decode($content, true);

        if (!$data || !isset($data['translations'])) {
            return [];
        }

        return $data['translations'];
    }

    /**
     * Parse .js contextual translation file
     */
    private function parseJsTranslationFile(string $filePath): array
    {
        $content = file_get_contents($filePath);

        if (!$content) {
            return [];
        }

        // Extract translations object from OC.L10N.register
        if (preg_match('/OC\.L10N\.register\s*\(\s*"[^"]*"\s*,\s*({[^}]+})\s*,/', $content, $matches)) {
            $jsonStr = $matches[1];
            // Replace single quotes with double quotes for valid JSON
            $jsonStr = str_replace("'", '"', $jsonStr);
            $jsonStr = preg_replace('/(\w+):/', '"$1":', $jsonStr);

            $translations = json_decode($jsonStr, true);

            return $translations ?: [];
        }

        return [];
    }

    /**
     * Get information about available contextual file formats for context
     */
    public function getAvailableContextualFormats(string $context): array
    {
        $contextPath = self::CONTEXTUAL_TRANSLATION_FILES[$context] ?? null;

        if (!$contextPath) {
            return [];
        }

        $appPath = \OC::$SERVERROOT . '/apps/done/';
        $fullPath = $appPath . $contextPath;

        if (!is_dir($fullPath)) {
            return [];
        }

        $formats = [];
        $currentLanguage = $this->l10n->getLanguageCode();

        // Check JSON files
        $jsonFile = $fullPath . '/' . $currentLanguage . '.json';

        if (file_exists($jsonFile)) {
            $formats['json'] = $jsonFile;
        }

        // Check JS files
        $jsFile = $fullPath . '/' . $currentLanguage . '.js';

        if (file_exists($jsFile)) {
            $formats['js'] = $jsFile;
        }

        return $formats;
    }

    /**
     * Create JSON contextual translation file for specified context and language
     */
    public function createContextualJsonTranslationFile(string $context, string $language, array $translations): bool
    {
        $contextPath = self::CONTEXTUAL_TRANSLATION_FILES[$context] ?? null;

        if (!$contextPath) {
            return false;
        }

        $appPath = \OC::$SERVERROOT . '/apps/done/';
        $fullPath = $appPath . $contextPath;

        // Create directory if not exists
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $jsonFile = $fullPath . '/' . $language . '.json';

        // Form data for JSON file
        $data = [
            'translations' => $translations,
            'pluralForm'   => $this->getPluralForm($language),
        ];

        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return file_put_contents($jsonFile, $jsonContent) !== false;
    }

    /**
     * Get plural form for language
     */
    private function getPluralForm(string $language): string
    {
        $pluralForms = [
            'ru'      => 'nplurals=4; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<12 || n%100>14) ? 1 : n%10==0 || (n%10>=5 && n%10<=9) || (n%100>=11 && n%100<=14)? 2 : 3);',
            'en'      => 'nplurals=2; plural=(n != 1);',
            'de'      => 'nplurals=2; plural=(n != 1);',
            'es'      => 'nplurals=2; plural=(n != 1);',
            'fr'      => 'nplurals=3; plural=n == 1 ? 0 : n != 0 && n % 1000000 == 0 ? 1 : 2;',
            'default' => 'nplurals=2; plural=(n != 1);',
        ];

        return $pluralForms[$language] ?? $pluralForms['default'];
    }

    /**
     * Get translation with parameters (uses native Nextcloud system)
     */
    public function t(string $key, array $parameters = []): string
    {
        return $this->l10n->t($key, $parameters);
    }

    /**
     * Get translation with plural form (uses native Nextcloud system)
     */
    public function n(string $singular, string $plural, int $count, array $parameters = []): string
    {
        return $this->l10n->n($singular, $plural, $count, $parameters);
    }

    /**
     * Get language code (uses native Nextcloud system)
     */
    public function getLanguageCode(): string
    {
        return $this->l10n->getLanguageCode();
    }
}
