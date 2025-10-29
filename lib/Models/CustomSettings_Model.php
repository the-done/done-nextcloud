<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


namespace OCA\Done\Models;

use OCA\Done\Models\Dictionaries\GlobalRoles_Model;
use OCA\Done\Service\BaseService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class CustomSettings_Model.
 */
class CustomSettings_Model extends Base_Model
{
    public string $modelTitle = 'Custom settings';
    public string $modelName = 'customSettingsModel';

    /* Setting types */
    public const CACHE_TIME_SETTING = 1;
    public const HIDE_EMPTY_FIELDS_IN_PREVIEW = 2;
    public const USER_LANGUAGE = 3;

    /* Setting value types */
    public const CHECKBOX_TYPE = 1;
    public const STRING_TYPE = 2;
    public const NUMBER_TYPE = 3;
    public const SELECT_TYPE = 4;
    public const TEXTAREA_TYPE = 5;

    public function getList(
        array $ids = [],
        array $fields = ['*'],
        bool $needIndex = false,
        bool $group = false,
        string $keyField = 'id',
        string $subField = null
    ): array
    {
        $settings = [
            self::CACHE_TIME_SETTING => [
                'title' => 'To memorize the last set time',
                'type' => self::CHECKBOX_TYPE,
                'description' => 'When this option is enabled, the app will remember the time you set last time and suggest it when creating a new entry.'
            ],
            self::USER_LANGUAGE => [
                'title' => 'Language of interface',
                'type' => self::SELECT_TYPE,
                'description' => 'Select the language of the Nextcloud interface. The change will take effect after refreshing the page.',
                'options' => [], // will be filled on frontend
            ],
        ];

        if ($this->userService->can([GlobalRoles_Model::OFFICER, GlobalRoles_Model::HEAD, GlobalRoles_Model::ADMIN])) {
            $settings[self::HIDE_EMPTY_FIELDS_IN_PREVIEW] = [
                'title' => 'Hide empty fields in preview',
                'type' => self::CHECKBOX_TYPE,
                'description' => 'When this option is enabled, empty record fields will be hidden when viewing the card.'
            ];
        }

        return $settings;
    }
}