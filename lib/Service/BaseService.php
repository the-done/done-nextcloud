<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */


declare(strict_types=1);

namespace OCA\Done\Service;

use OCP\IUserManager;
use OCP\Server;

class BaseService
{
    /** @var IUserManager */
    private IUserManager $userManager;

    /** @var TranslateService */
    protected TranslateService $translateService;

    /** @var BaseService */
    private static BaseService $instance;

    public function __construct(
        IUserManager $userManager,
        TranslateService $translateService,
    ) {
        $this->userManager      = $userManager;
        $this->translateService = $translateService;
    }

    /**
     * Convert minutes to hours-minutes
     *
     * @param int $minutes
     *
     * @return string
     */
    public function getTimeView(int $minutes): string
    {
        $hoursValue   = floor($minutes / 60);
        $hoursView    = $hoursValue > 0 ? $hoursValue.' '.$this->translateService->getTranslate('h').' ' : '';
        $minutesValue = ($minutes - floor($minutes / 60) * 60);
        $minutesView  = $minutesValue > 0 ? $minutesValue.' '.$this->translateService->getTranslate('min') : '';

        return $hoursView.$minutesView;
    }

    /**
     * Converts list to tree structure.
     *
     * @param array $list
     * @param string|null $firstParentId
     * @param string|null $parent
     * @param string|null $parentType
     * @param string|null $parentOfParent
     * @param string $childName
     * @param string $firstParentType
     *
     * @return array
     */
    public static function toTree(
        array &$list,
        string $firstParentId = null,
        string $parent = null,
        string $parentType = null,
        string $parentOfParent = null,
        string $childName = 'children',
        string $firstParentType = 'year',
    ): array {
        $result = [];

        foreach ($list as $idx => $item) {
            $itemId        = $item['id'];
            $parentId      = $item['parent'];
            $parentTypeVal = $item['parent_type'];

            if (
                $parentId === $parent &&
                $parentTypeVal === $parentType &&
                $item["parent_{$firstParentType}"] === $firstParentId &&
                $item['type'] !== 'day'
            ) {
                $childs = self::toTree(
                    $list,
                    $item['type'] == $firstParentType ? $itemId : $item["parent_{$firstParentType}"],
                    $itemId,
                    $item['type'],
                    $item['parent'],
                    $childName,
                    $firstParentType
                );

                $item[$childName] = $childs;
                $result[]         = $item;
                unset($list[$idx]);
            } elseif (
                $parentId === $parent &&
                $parentTypeVal === $parentType &&
                $item["parent_{$firstParentType}"] === $firstParentId &&
                $item['type'] == 'day' && $item['parent_month'] == $parentOfParent
            ) {
                $result[] = $item;
            }
        }

        return $result;
    }

    public function getOuterUsers(array $in = [], array $notIn = []): array
    {
        $users = [];

        foreach ($this->userManager->getBackends() as $backend) {
            $backendUsers = $backend->getUsers('');
            foreach ($backendUsers as $uid) {
                if ((!empty($notIn) && in_array($uid, $notIn)) || (!empty($in) && !in_array($uid, $in))) {
                    continue;
                }

                $users[] = [
                    'uuid' => $uid,
                    'name' => $this->userManager->get($uid)->getDisplayName(),
                ];
            }
        }

        return $users;
    }

    /**
     * Get one field from array of arrays (array of objects).
     */
    public static function getField(array $arrayList, string $fieldName = 'id', bool $needUnique = false): array
    {
        $result = [];

        foreach ($arrayList as $option) {
            $result[] = $option[$fieldName];
        }

        return $needUnique ? array_unique($result) : $result;
    }

    /**
     * makeHash
     * Change array keys to value of one of array fields with ability to group by it.
     *
     * @param array $arr
     * @param string $field
     * @param bool $group
     * @param string|null $subKey
     * @param bool $subGroup
     * @return array
     */
    public static function makeHash(
        array $arr,
        string $field = 'id',
        bool $group = false,
        string $subKey = null,
        bool $subGroup = false
    ): array {
        $result = [];

        if (count($arr) == 0) {
            return $result;
        }
        $row = current($arr);

        if (!array_key_exists($field, $row)) {
            return [];
        }

        foreach ($arr as $row) {
            if ($group) {
                $key = !empty($subKey) && !empty($row[$subKey]) ? $row[$subKey] : '';

                if ($key && $subGroup) {
                    $result[$row[$field]][$key][] = $row;
                } elseif ($key) {
                    $result[$row[$field]][$key] = $row;
                } else {
                    $result[$row[$field]][] = $row;
                }
            } else {
                $result[$row[$field]] = $row;
            }
        }

        return $result;
    }

    public static function makeMd5Hash(string $string): string
    {
        $currentDateTime = date('Y-m-d H:i:s');
        $chars           = '0123456789abcdefghijklmnopqrstuvwxyz';
        $randomString    = substr(str_shuffle($chars), 0, 15);
        $preparedString  = "{$string}-{$randomString}-{$currentDateTime}";

        return md5($preparedString);
    }

    public static function getQuarter(\DateTime|\DateTimeImmutable $dateTime)
    {
        return ceil($dateTime->format('m') / 3);
    }

    public static function getWeekDays(): array
    {
        return [
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
        ];
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = Server::get(BaseService::class);
        }

        return self::$instance;
    }
}
