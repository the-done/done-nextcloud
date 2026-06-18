<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Vacations\Model;

use OCA\Done\Models\BaseModel;
use OCA\Done\Models\UserModel;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class VacationsBalanceModel.
 *
 * Stores individual vacation day balances for employees.
 * HR can manually adjust balances (e.g., bonus days, custom limits).
 * If no record exists for a user/type/year, the default from VacationsTypeSettingsModel is used.
 */
class VacationsBalanceModel extends BaseModel
{
    public string $table = 'done_vac_balances';
    public string $modelTitle = 'Employee vacation balances';
    public string $modelName = 'vacationsBalanceModel';
    public string $dbTableComment = 'Employee vacation day balances: manual adjustments by HR for each vacation type per year.';

    protected array $hashFields = [
        'user_id',
        'type_id',
        'year',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Unique identifier for a balance entry',
        ],
        'user_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Employee',
            'required'   => true,
            'link'       => UserModel::class,
            'db_comment' => 'Employee ID. References oc_done_users_data.id',
        ],
        'type_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Vacation type',
            'required'   => true,
            'link'       => VacationsTypesModel::class,
            'db_comment' => 'Vacation type ID. References oc_done_vacations_types.id',
        ],
        'year' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Year',
            'required'   => true,
            'db_comment' => 'Year for which this balance applies',
        ],
        'days_total' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Total days',
            'required'   => true,
            'unsigned'   => true,
            'db_comment' => 'Total days available (overrides default from type settings)',
            'show'       => true,
        ],
        'comment' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Comment',
            'required'         => false,
            'db_comment'       => 'Comment (e.g., reason for adjustment, bonus days)',
            'show'             => true,
            'validation_rules' => [
                'trim' => true,
            ],
        ],
        'adjusted_by' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Adjusted by',
            'required'   => false,
            'link'       => UserModel::class,
            'db_comment' => 'User who made the adjustment. References oc_done_users_data.id',
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

    /**
     * Get balance for a specific employee, type, and year
     *
     * @param string $userId
     * @param string $typeId
     * @param int    $year
     *
     * @return null|array
     */
    public function getBalanceForEmployee(string $userId, string $typeId, int $year): ?array
    {
        $result = $this->getItemByFilter([
            'user_id' => $userId,
            'type_id' => $typeId,
            'year'    => $year,
        ]);

        return !empty($result) ? $result : null;
    }

    /**
     * Get all balances for an employee for a specific year
     *
     * @param string $userId
     * @param int    $year
     *
     * @return array
     */
    public function getBalancesForEmployee(string $userId, int $year): array
    {
        return $this->getIndexedListByFilter('type_id', [
            'user_id' => $userId,
            'year'    => $year,
        ]);
    }

    /**
     * Set or update balance for an employee
     *
     * @param string      $userId
     * @param string      $typeId
     * @param int         $year
     * @param int         $daysTotal
     * @param null|string $adjustedBy
     * @param null|string $comment
     *
     * @return string
     */
    public function setBalance(
        string $userId,
        string $typeId,
        int $year,
        int $daysTotal,
        ?string $adjustedBy = null,
        ?string $comment = null
    ): string {
        $data = [
            'user_id'     => $userId,
            'type_id'     => $typeId,
            'year'        => $year,
            'days_total'  => $daysTotal,
            'adjusted_by' => $adjustedBy,
            'comment'     => $comment,
        ];

        $filter = [
            'user_id' => $userId,
            'type_id' => $typeId,
            'year'    => $year,
        ];

        return $this->upsertByFilter($data, $filter);
    }
}
