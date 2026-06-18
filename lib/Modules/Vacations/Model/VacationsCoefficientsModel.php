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
 * Class VacationsCoefficientsModel.
 *
 * Stores vacation accrual coefficients per employee and vacation type with
 * effective date ranges. The active coefficient on any given date is the one
 * whose [effective_from; effective_to] interval contains that date
 * (effective_to may be null for an open-ended record).
 *
 * If no coefficient record covers a date, the implicit value is 1.0.
 */
class VacationsCoefficientsModel extends BaseModel
{
    public string $table = 'done_vac_coefficients';
    public string $modelTitle = 'Vacation accrual coefficients';
    public string $modelName = 'vacationsCoefficientsModel';
    public string $dbTableComment = 'Vacation accrual coefficients per employee and vacation type, with effective dates.';

    protected array $hashFields = [
        'user_id',
        'type_id',
        'effective_from',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Unique identifier for a coefficient entry',
        ],
        'user_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Employee',
            'required'   => true,
            'link'       => UserModel::class,
            'db_comment' => 'Employee ID. References oc_done_users_data.id',
            'show'       => true,
        ],
        'type_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Vacation type',
            'required'   => true,
            'link'       => VacationsTypesModel::class,
            'db_comment' => 'Vacation type ID. References oc_done_vacations_types.id',
            'show'       => true,
        ],
        'value' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Coefficient',
            'required'   => true,
            'db_comment' => 'Coefficient multiplier applied to monthly accrual (e.g., 1.20)',
            'show'       => true,
        ],
        'effective_from' => [
            'type'       => IQueryBuilder::PARAM_DATE_IMMUTABLE,
            'title'      => 'Effective from',
            'required'   => true,
            'db_comment' => 'Date when this coefficient becomes active (inclusive)',
            'show'       => true,
        ],
        'effective_to' => [
            'type'       => IQueryBuilder::PARAM_DATE_IMMUTABLE,
            'title'      => 'Effective to',
            'required'   => false,
            'db_comment' => 'Date when this coefficient stops being active (inclusive); null means open-ended',
            'show'       => true,
        ],
        'comment' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Comment',
            'required'         => false,
            'db_comment'       => 'Optional comment (reason for the coefficient, etc.)',
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
            'db_comment' => 'User who created or last modified the record. References oc_done_users_data.id',
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
     * Return the coefficient record active for (user, type) on a given date,
     * or null if none.
     */
    public function getActive(string $userId, string $typeId, \DateTimeImmutable $date): ?array
    {
        $list = $this->getListByFilter([
            'user_id'        => $userId,
            'type_id'        => $typeId,
            'effective_from' => ['<=', $date],
        ], ['*'], ['effective_from', 'DESC']);

        foreach ($list as $row) {
            $to = $row['effective_to'] ?? null;

            if ($to === null) {
                return $row;
            }

            $toObj = $to instanceof \DateTimeInterface ? $to : new \DateTimeImmutable($to);

            if ($toObj >= $date) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Return all coefficient records for (user, type) overlapping the given year.
     *
     * @return array<int, array>
     */
    public function getForYear(string $userId, string $typeId, int $year): array
    {
        $yearEnd = new \DateTimeImmutable($year . '-12-31');

        $list = $this->getListByFilter([
            'user_id'        => $userId,
            'type_id'        => $typeId,
            'effective_from' => ['<=', $yearEnd],
        ], ['*'], ['effective_from', 'ASC']);

        $yearStart = new \DateTimeImmutable($year . '-01-01');

        return array_values(array_filter($list, static function (array $row) use ($yearStart): bool {
            $to = $row['effective_to'] ?? null;

            if ($to === null) {
                return true;
            }

            $toObj = $to instanceof \DateTimeInterface ? $to : new \DateTimeImmutable($to);

            return $toObj >= $yearStart;
        }));
    }
}
