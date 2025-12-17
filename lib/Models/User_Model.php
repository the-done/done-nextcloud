<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Models;

use OCA\Done\Models\Appearance\UserAppearance_Model;
use OCA\Done\Models\Dictionaries\Contracts_Model;
use OCA\Done\Models\Dictionaries\GlobalRoles_Model;
use OCA\Done\Models\Dictionaries\Positions_Model;
use OCA\Done\Service\BaseService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class User_Model.
 */
class User_Model extends Base_Model
{
    public string $table = 'done_users_data';
    public string $modelTitle = 'Users';
    public string $modelName = 'usersModel';
    public string $dbTableComment = 'User data: employee profiles, contact information, HR data. Uses soft-delete.';
    public string $primarySlugField = 'user_display_name';
    protected array $hashFields = [
        'user_id',
        'user_display_name',
        'lastname',
        'name',
        'middle_name',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Unique identifier for a user (employee)',
        ],
        'user_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Nextcloud user uuid',
            'required'   => false,
            'permission' => true,
            'db_comment' => 'User UID (login) in the external Nextcloud system',
            'show'       => true,
        ],
        'user_display_name' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Nickname',
            'required'         => false,
            'permission'       => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'User display name (nickname)',
            'show'       => true,
        ],
        'lastname' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'User lastname',
            'required'         => false,
            'permission'       => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'User\'s last name',
            'show'       => true,
        ],
        'name' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'User name',
            'required'         => true,
            'permission'       => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'User\'s first name',
            'show'       => true,
        ],
        'middle_name' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'User middle name',
            'required'         => false,
            'permission'       => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'User\'s middle name',
            'show'       => true,
        ],
        'position_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Position',
            'required'   => false,
            'link'       => Positions_Model::class,
            'permission' => true,
            'db_comment' => 'Position ID. References oc_done_positions.id',
            'show'       => true,
        ],
        'contract_type_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Contract type',
            'required'   => false,
            'link'       => Contracts_Model::class,
            'permission' => true,
            'db_comment' => 'Contract type ID. References oc_done_contracts.id',
            'show'       => true,
        ],
        'deleted' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Deleted',
            'db_comment' => 'Soft-delete flag (1 - deleted, 0 - active). Deleted records should be excluded from queries.',
        ],
        'created_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC',
        ],
        'updated_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Updated at',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC',
        ],
    ];

    public array $linkedModels = [
        UsersToDirections_Model::class => [
            'title'         => 'Directions',
            'frontend_type' => 'directions',
            'filter_field'  => 'user_id',
            'key_field'     => 'direction_id',
        ],
        UsersGlobalRoles_Model::class => [
            'title'         => 'Roles',
            'frontend_type' => 'roles',
            'filter_field'  => 'user_id',
            'key_field'     => 'role_id',
        ],
    ];

    public string $appearanceModel = UserAppearance_Model::class;

    /**
     * @override
     */
    public function validateData(
        array $data,
        bool $save = false,
        array $ignoreFields = [],
        ?string $id = null
    ): array {
        $result = $errors = [];

        if (isset($data['id'])) {
            $result['id'] = $data['id'];
        }

        if ($save) {
            foreach ($this->fields as $field => $params) {
                if (\in_array($field, $this->excludedKeys) || \in_array($field, $ignoreFields)) {
                    continue;
                }

                $required = $params['required'] ?? false;
                $needTrim = $params['validation_rules']['trim'] ?? false;
                $title = $params['title'];

                $value = $data[$field] ?? null;

                if (empty($value) && $required) {
                    $errors[] = $this->translateService->getTranslate('The «{%s}» field must be filled in', [$title]);
                    continue;
                }

                if ($field == 'user_display_name') {
                    if (!preg_match('/^[a-zA-Z0-9\-._]+$/', $value)) {
                        $errors[] = $this->translateService->getTranslate(
                            'Invalid characters found in field «%s»',
                            [$title]
                        );
                        continue;
                    }

                    $user = $this->getItemByFilter(['user_display_name' => ['=', $value]]);

                    if (!empty($user) && $id !== $user['id']) {
                        $errors[] = $this->translateService->getTranslate('Nickname already exists');
                        continue;
                    }
                }

                if ($field == 'gender' && !\in_array($value, [0, 1, null])) {
                    $errors[] = $this->translateService->getTranslate('Invalid gender specified');
                    continue;
                }

                if ($params['type'] == IQueryBuilder::PARAM_DATE_IMMUTABLE && isset($value)) {
                    $value = (new \DateTimeImmutable($value));
                }

                if ($needTrim) {
                    $value = trim($value);
                }

                $result[$field] = $value;
            }

            return [$result, $errors];
        }

        foreach ($data as $key => $value) {
            if (!isset($this->fields[$key]) || \in_array($key, $this->excludedKeys) || \in_array($key, $ignoreFields)) {
                continue;
            }

            $params = $this->fields[$key];
            $required = $params['required'] ?? false;
            $needTrim = $params['validation_rules']['trim'] ?? false;
            $title = $params['title'];

            $value = $value ?? null;

            if (empty($value) && $required) {
                $errors[] = $this->translateService->getTranslate('The «{%s}» field must be filled in', [$title]);
                continue;
            }

            if ($key == 'user_display_name') {
                if (!preg_match('/^[a-zA-Z0-9\-._]+$/', $value)) {
                    $errors[] = $this->translateService->getTranslate(
                        'Invalid characters found in field «%s»',
                        [$title]
                    );
                    continue;
                }

                $user = $this->getItemByFilter(['user_display_name' => ['=', $value]]);

                if (!empty($user) && $id !== $user['id']) {
                    $errors[] = $this->translateService->getTranslate('Nickname already exists');
                    continue;
                }
            }

            if ($key == 'gender' && !\in_array($value, [0, 1, null])) {
                $errors[] = $this->translateService->getTranslate('Invalid gender specified');
                continue;
            }

            if ($this->fields[$key]['type'] == IQueryBuilder::PARAM_DATE_IMMUTABLE && isset($value)) {
                $value = (new \DateTimeImmutable($value));
            }

            if ($needTrim) {
                $value = trim($value);
            }

            $result[$key] = $value;
        }

        return [$result, $errors];
    }

    /**
     * @override
     */
    public function getListByFilter(
        array $filter = [],
        array $fields = ['*'],
        array $orderBy = [],
        array $additionalOrderBy = [],
        bool $needDeleted = false,
    ): array {
        $qb = $this->db->getQueryBuilder();

        $fields = $this->prepareSelectFields($fields);

        $concat = $qb->func()->concat(
            'lastname',
            $qb->expr()->literal(' '),
            'name',
            $qb->expr()->literal(' '),
            'middle_name'
        );

        $qb->select($fields)
            ->selectAlias($concat, 'full_name')
            ->from($this->table);

        if (isset($this->fields['deleted']) && !$needDeleted) {
            $qb->where($qb->expr()->eq('deleted', $qb->createNamedParameter(0)));
        } else {
            $qb->where(1);
        }

        if (!empty($filter)) {
            $qb = $this->makeFilter($qb, $filter);
        }

        if (!empty($orderBy)) {
            $qb->orderBy($orderBy[0], $orderBy[1]);
        }

        if (!empty($orderBy) && !empty($additionalOrderBy)) {
            $qb->addOrderBy($additionalOrderBy[0], $additionalOrderBy[1]);
        }

        $items = $qb->executeQuery()->fetchAll();

        return $this->prepareItems($items);
    }

    /**
     * Prepare user records
     *
     * @param array $users
     *
     * @return array
     */
    public function prepareData(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $positionsModel = new Positions_Model();
        $contractsModel = new Contracts_Model();

        $contractTypesIds = BaseService::getField($users, 'contract_type_id');
        $positionsIds = BaseService::getField($users, 'position_id');

        $positionsList = !empty($positionsIds)
            ? $positionsModel->getIndexedListByFilter(
                'id',
                ['id' => ['IN', $positionsIds, IQueryBuilder::PARAM_STR_ARRAY]]
            ) : [];
        $contractsList = !empty($contractTypesIds)
            ? $contractsModel->getIndexedListByFilter(
                'id',
                ['id' => ['IN', $contractTypesIds, IQueryBuilder::PARAM_STR_ARRAY]]
            ) : [];

        foreach ($users as $idx => $user) {
            $users[$idx] = self::prepareUser($user, $positionsList, $contractsList);
        }

        return $users;
    }

    /**
     * Prepare user record
     *
     * @param array<string, mixed> $user
     *
     * @return array
     */
    public function prepareUserItem(array $user): array
    {
        if (empty($user)) {
            return [];
        }

        $positionsModel = new Positions_Model();
        $contractsModel = new Contracts_Model();

        $positionsList = !empty($user['position_id'])
            ? $positionsModel->getIndexedListByFilter(
                'id',
                ['id' => ['IN', [$user['position_id']], IQueryBuilder::PARAM_STR_ARRAY]]
            )
            : [];

        $contractsList = !empty($user['contract_type_id'])
            ? $contractsModel->getIndexedListByFilter(
                'id',
                ['id' => ['IN', [$user['contract_type_id']], IQueryBuilder::PARAM_STR_ARRAY]]
            )
            : [];

        return self::prepareUser($user, $positionsList, $contractsList);
    }

    /**
     * Add additional parameters to user record
     *
     * @param array<string, mixed> $user
     * @param array<string, mixed> $positionsList
     * @param array<string, mixed> $contractsList
     *
     * @return array
     */
    private static function prepareUser(array $user, array $positionsList, array $contractsList): array
    {
        if (empty($user)) {
            return [];
        }

        $user['cname'] = $contractsList[$user['contract_type_id']]['name'] ?? '';
        $user['pname'] = $positionsList[$user['position_id']]['name'] ?? '';

        return $user;
    }

    /**
     * Add users indexed by id with position
     *
     * @param array<string, mixed> $filter
     * @param bool                 $needDeleted
     *
     * @return array
     */
    public function getIndexedUsersFullNameWithPosition(
        array $filter = [],
        bool $needDeleted = false,
    ): array {
        $data = [];

        $usersLinked = $this->getLinkedList(
            $filter,
            ['id', 'name', 'middle_name', 'lastname', 'position_id', 'deleted'],
            false,
            $needDeleted
        );

        $deletedUser = $this->translateService->getTranslate('User deleted');

        foreach ($usersLinked as $user) {
            $lastname = $user['lastname'] ?? '';
            $name = $user['name'] ?? '';
            $middleName = $user['middle_name'] ?? '';
            $position = $user['position_id'] ?? '';
            $deleted = (bool)$user['deleted'];
            $position = mb_strtolower($position);
            $fullName = "{$lastname} {$name} {$middleName}";
            $uname = !empty($position) ? "{$fullName}, {$position}" : $fullName;
            $uname = $deleted ? "{$uname} ({$deletedUser})" : $uname;
            $slug = $user['slug'] ?? '';

            $data[$user['id']] = [
                'id'    => $user['id'],
                'uname' => $uname,
                'slug'  => $slug,
            ];
        }

        return $data;
    }

    /**
     * Get all users with common fields
     *
     * @return array
     */
    public function getSimpleUsers(): array
    {
        $result = [];

        $users = (new self())->getListByFilter([], ['id', 'name', 'lastname']);

        foreach ($users as $user) {
            $result[] = [
                'id'        => $user['id'],
                'slug'      => $user['slug'],
                'slug_type' => $user['slug_type'],
                'name'      => $user['name'] . ' ' . $user['lastname'],
            ];
        }

        return $result;
    }

    /**
     * @override
     */
    public function getListForLink(
        array $filter = [],
        bool $needIndex = false,
        bool $needDeleted = false
    ): array {
        $result = [];

        $users = $this->getListByFilter(
            $filter,
            ['id', 'name', 'lastname', 'middle_name', 'position_id', 'deleted', 'id'],
            ['full_name', 'ASC'],
            [],
            $needDeleted
        );

        $positionsIds = BaseService::getField($users, 'position_id');

        $positionsList = !empty($positionsIds)
            ? (new Positions_Model())->getIndexedListByFilter(
                'id',
                ['id' => ['IN', $positionsIds, IQueryBuilder::PARAM_STR_ARRAY]]
            ) : [];

        $userDeletedMessage = $this->translateService->getTranslate('User deleted');

        foreach ($users as $user) {
            $position = $positionsList[$user['position_id']]['name'] ?? 'no position';
            $deleted = (bool)$user['deleted'];
            $position = mb_strtolower($position);
            $fullName = $user['full_name'] ?? 'No name';
            $uname = "{$fullName}, {$position}";
            $uname = $deleted ? "{$uname} ({$userDeletedMessage})" : $uname;
            $item = [
                'id'   => $user['id'],
                'name' => $uname,
            ];

            if ($needIndex) {
                $result[$user['id']] = $item;
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Get user by Nextcloud UUID
     *
     * @param null|string $uuid
     *
     * @return array|bool
     */
    public function getUserByUuid(?string $uuid = null): array | bool
    {
        return $this->getItemByFilter(['user_id' => $uuid]);
    }

    /**
     * Get user IDs from specified contract types
     *
     * @param string[] $contractTypes
     *
     * @return string[]
     */
    public function getEmployeesByContractTypes(array $contractTypes = []): array
    {
        if (empty($contractTypes)) {
            return [];
        }

        $list = $this->getListByFilter(
            ['contract_type_id' => ['IN', $contractTypes, IQueryBuilder::PARAM_STR_ARRAY]]
        );

        return array_values(BaseService::getField($list, 'id', true));
    }

    /**
     * Create first user
     *
     * @param string $currentUserUid
     *
     * @return string
     */
    public function addFirstUser(string $currentUserUid): string
    {
        $data = [
            'user_id'           => $currentUserUid,
            'user_display_name' => 'admin',
            'lastname'          => 'admin',
            'name'              => 'admin',
            'middle_name'       => 'admin',
        ];
        $userId = $this->addData($data);
        (new UsersGlobalRoles_Model())->addData(['user_id' => $userId, 'role_id' => GlobalRoles_Model::ADMIN]);

        return $userId;
    }
}
