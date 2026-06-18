<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Agreement\Model;

use OCA\Done\Models\BaseModel;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class AgreementSchemeLinesModel.
 *
 * Lines (stages) within agreement schemes.
 */
class AgreementSchemeLinesModel extends BaseModel
{
    public string $table = 'done_agr_scheme_lines';
    public string $modelTitle = 'Agreement Scheme Lines';
    public string $modelName = 'agreementSchemeLines';
    public string $dbTableComment = 'Agreement scheme lines: stages within agreement schemes.';

    protected array $hashFields = [
        'scheme_id',
        'name',
        'sort_order',
        'require_all',
        'deadline_enabled',
        'deadline_days',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'required'   => true,
            'db_comment' => 'Unique identifier for an agreement scheme line',
        ],
        'scheme_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Scheme',
            'required'   => true,
            'link'       => AgreementSchemesModel::class,
            'db_comment' => 'Parent scheme ID. References oc_done_agr_schemes.id',
        ],
        'name' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Name',
            'required'         => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Line name (e.g., "Manager approval", "HR approval")',
        ],
        'sort_order' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Sort order',
            'required'   => false,
            'db_comment' => 'Order of the line in the agreement process (1, 2, 3...)',
        ],
        'require_all' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Require all',
            'required'   => false,
            'db_comment' => 'If true, all approvers must agree. If false, one approver is enough (1=all, 0=any)',
        ],
        'deadline_enabled' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Deadline enabled',
            'required'   => false,
            'db_comment' => 'Whether deadline is enabled for this line (1=yes, 0=no)',
        ],
        'deadline_days' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Deadline days',
            'required'   => false,
            'db_comment' => 'Number of days for agreement before auto-rejection',
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
        'deleted' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Deleted',
            'db_comment' => 'Soft delete flag (1=deleted, 0=active)',
        ],
    ];

    /**
     * Get lines for a scheme ordered by sort_order.
     */
    public function getLinesForScheme(string $schemeId): array
    {
        return $this->getListByFilter(
            ['scheme_id' => $schemeId],
            ['*'],
            ['sort_order', 'ASC']
        );
    }

    /**
     * Get next line after current line in a scheme.
     */
    public function getNextLine(string $schemeId, int $currentSortOrder): ?array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->table)
            ->where($qb->expr()->eq('scheme_id', $qb->createNamedParameter($schemeId)))
            ->andWhere($qb->expr()->gt('sort_order', $qb->createNamedParameter($currentSortOrder, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('sort_order', 'ASC')
            ->setMaxResults(1);

        $result = $qb->executeQuery()->fetch();

        return $result ?: null;
    }

    /**
     * Get first line of a scheme.
     */
    public function getFirstLine(string $schemeId): ?array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->table)
            ->where($qb->expr()->eq('scheme_id', $qb->createNamedParameter($schemeId)))
            ->andWhere($qb->expr()->eq('deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('sort_order', 'ASC')
            ->setMaxResults(1);

        $result = $qb->executeQuery()->fetch();

        return $result ?: null;
    }
}
