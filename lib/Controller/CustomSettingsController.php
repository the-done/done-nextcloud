<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Done\Controller;

use OCA\Done\Models\CustomSettingsDataModel;
use OCA\Done\Models\CustomSettingsModel;
use OCA\Done\Models\UserModel;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class CustomSettingsController extends CommonController
{
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function getCustomSettingsList(): JSONResponse
    {
        return new JSONResponse((new CustomSettingsModel())->getList(), Http::STATUS_OK);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function getUserCustomSettings(IRequest $request): JSONResponse
    {
        $userSlug = $request->getParam('user_slug');
        $settingId = $request->getParam('setting_id');
        $filter = [];
        $customSettingsData = new CustomSettingsDataModel();

        if (!empty($userSlug)) {
            $userId = (new UserModel())->getItemIdBySlug($userSlug);
            $filter['user_id'] = $userId;
        }

        if (!empty($settingId)) {
            $filter['setting_id'] = $settingId;
        }

        // Get settings from database
        $settings = $customSettingsData->getListByFilter($filter);

        // Add current user language from Nextcloud (dynamically, without saving to DB)
        $user = $this->userSession->getUser();

        if ($user) {
            $currentLanguage = $this->config->getUserValue($user->getUID(), 'core', 'lang', 'en');

            // Add language setting as virtual setting (not from DB)
            $settings[] = [
                'setting_id' => CustomSettingsModel::USER_LANGUAGE,
                'value'      => $currentLanguage,
                'type_id'    => CustomSettingsModel::SELECT_TYPE,
                'user_id'    => $this->userService->getCurrentUserId(),
                'slug'       => 'virtual_language_setting', // Virtual slug for frontend
                'is_virtual' => true, // Flag that this is a virtual setting
            ];
        }

        return new JSONResponse($settings, Http::STATUS_OK);
    }

    /**
     * Save user settings
     */
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function saveUserSettings(IRequest $request): JSONResponse
    {
        $settings = $request->getParam('settings');

        if (empty($settings) || !\is_array($settings)) {
            return new JSONResponse(
                [
                    'message' => $this->translateService->getTranslate('There was an error saving settings'),
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $currentUserId = $this->userService->getCurrentUserId();
        $customSettingsData = new CustomSettingsDataModel();
        $results = [];

        foreach ($settings as $setting) {
            $settingId = $setting['setting_id'] ?? null;
            $value = $setting['value'] ?? null;
            $slug = $setting['slug'] ?? null;
            $typeId = $setting['type_id'] ?? null;

            if (empty($settingId) || empty($typeId)) {
                $results[] = [
                    'setting_id' => $settingId,
                    'message'    => $this->translateService->getTranslate('Missing required parameters'),
                    'success'    => false,
                ];
                continue;
            }

            try {
                // Check if this is a language setting (virtual setting)
                if ($settingId == CustomSettingsModel::USER_LANGUAGE) {
                    // For language use special Nextcloud API
                    $user = $this->userSession->getUser();

                    if ($user) {
                        $this->config->setUserValue($user->getUID(), 'core', 'lang', $value);
                        $results[] = [
                            'setting_id' => $settingId,
                            'success'    => true,
                            'slug'       => 'virtual_language_setting',
                            'message'    => $this->translateService->getTranslate('Language changed'),
                        ];
                    } else {
                        $results[] = [
                            'setting_id' => $settingId,
                            'success'    => false,
                            'message'    => $this->translateService->getTranslate('User not found'),
                        ];
                    }
                } else {
                    // For other settings save to DB
                    if (!empty($slug)) {
                        // Update existing setting
                        $customSettingsData->update([
                            'value' => $value,
                        ], $slug);
                        $resultSlug = $slug;
                    } else {
                        // Create new setting
                        $resultSlug = $customSettingsData->addData([
                            'user_id'    => $currentUserId,
                            'setting_id' => $settingId,
                            'type_id'    => $typeId,
                            'value'      => $value,
                        ]);
                    }

                    $results[] = [
                        'setting_id' => $settingId,
                        'success'    => true,
                        'slug'       => $resultSlug,
                        'message'    => $this->translateService->getTranslate('Setting saved'),
                    ];
                }
            } catch (\Exception $e) {
                $results[] = [
                    'setting_id' => $settingId,
                    'success'    => false,
                    'message'    => $this->translateService->getTranslate('Error while saving:') . $e->getMessage(),
                ];
            }
        }

        return new JSONResponse(
            [
                'message' => $this->translateService->getTranslate('Settings processed'),
                'results' => $results,
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Get list of available languages
     */
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function getAvailableLanguages(): JSONResponse
    {
        $languages = $this->l10nFactory->getLanguages();

        // Convert to frontend format
        $languageOptions = [];

        // Add common languages
        foreach ($languages['commonLanguages'] as $lang) {
            $languageOptions[] = [
                'value' => $lang['code'],
                'label' => $lang['name'],
            ];
        }

        // Add all languages if available
        if (isset($languages['allLanguages'])) {
            foreach ($languages['allLanguages'] as $lang) {
                // Check if language is not already added
                $exists = false;

                foreach ($languageOptions as $existing) {
                    if ($existing['value'] === $lang['code']) {
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $languageOptions[] = [
                        'value' => $lang['code'],
                        'label' => $lang['name'],
                    ];
                }
            }
        }

        // Sort by name
        usort($languageOptions, static function ($a, $b) {
            return strcmp($a['label'], $b['label']);
        });

        return new JSONResponse($languageOptions, Http::STATUS_OK);
    }

    /**
     * Change user language
     */
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function changeUserLanguage(string $language): JSONResponse
    {
        try {
            $user = $this->userSession->getUser();

            if (!$user) {
                return new JSONResponse(['error' => $this->translateService->getTranslate('User not found')], Http::STATUS_NOT_FOUND);
            }

            // Set user language in Nextcloud
            $this->config->setUserValue($user->getUID(), 'core', 'lang', $language);

            return new JSONResponse([
                'success' => true,
                'message' => $this->translateService->getTranslate('Language changed successfully'),
            ], Http::STATUS_OK);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
