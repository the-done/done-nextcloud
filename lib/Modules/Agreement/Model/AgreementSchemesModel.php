<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Agreement\Model;

use OCA\Done\Models\BaseModel;
use OCA\Done\Models\UserModel;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class AgreementSchemesModel.
 *
 * Agreement schemes - templates for agreement processes.
 */
class AgreementSchemesModel extends BaseModel
{
    public string $table = 'done_agr_schemes';
    public string $modelTitle = 'Agreement Schemes';
    public string $modelName = 'agreementSchemes';
    public string $dbTableComment = 'Agreement schemes: templates for agreement processes with their settings.';

    protected array $hashFields = [
        'name',
        'description',
        'entity_type',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'required'   => true,
            'db_comment' => 'Unique identifier for an agreement scheme',
        ],
        'name' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Name',
            'required'         => true,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Scheme name',
        ],
        'description' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Description',
            'required'         => false,
            'validation_rules' => [
                'trim' => true,
            ],
            'db_comment' => 'Scheme description',
        ],
        'entity_type' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Entity type',
            'required'   => true,
            'db_comment' => 'Type of entity this scheme applies to (e.g., vacation, purchase)',
        ],
        'is_default' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Is default',
            'required'   => false,
            'db_comment' => 'Whether this is the default scheme for the entity type (1=yes, 0=no)',
        ],
        'is_active' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Is active',
            'required'   => false,
            'db_comment' => 'Whether the scheme is active and can be used (1=yes, 0=no)',
        ],
        'created_by' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Created by',
            'required'   => false,
            'link'       => UserModel::class,
            'db_comment' => 'User who created the scheme. References oc_done_users_data.id',
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

    // Entity types for agreement
    public const ENTITY_TYPE_VACATION = 'vacation';

    /**
     * Get entity type options for frontend.
     */
    public function getEntityTypeOptions(): array
    {
        return [
            ['id' => self::ENTITY_TYPE_VACATION, 'name' => $this->translateService->getTranslate('Vacation')],
        ];
    }

    /**
     * Get default scheme for entity type.
     */
    public function getDefaultScheme(string $entityType): ?array
    {
        $scheme = $this->getItemByFilter([
            'entity_type' => $entityType,
            'is_default'  => true,
            'is_active'   => true,
        ]);

        return !empty($scheme) ? $scheme : null;
    }

    /**
     * Get all active schemes for entity type.
     */
    public function getActiveSchemes(string $entityType): array
    {
        return $this->getListByFilter([
            'entity_type' => $entityType,
            'is_active'   => true,
        ]);
    }

    /**
     * Set scheme as default (and unset previous default).
     */
    public function setAsDefault(string $schemeId): bool
    {
        $scheme = $this->getItem($schemeId);

        if (empty($scheme)) {
            return false;
        }

        // Unset previous default
        $qb = $this->db->getQueryBuilder();
        $qb->update($this->table)
            ->set('is_default', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
            ->where($qb->expr()->eq('entity_type', $qb->createNamedParameter($scheme['entity_type'])))
            ->andWhere($qb->expr()->eq('is_default', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)));
        $qb->executeStatement();

        // Set new default
        $this->update(['is_default' => true], $schemeId);

        return true;
    }
}
