<?php

namespace OCA\Done\Models;

use OCA\Done\Service\BaseService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Table of personal settings set by user for ordering entity fields in card when viewing and editing.
 * User can have only one record for each entity field.
 *
 * Class FieldsOrdering_Model.
 */
class FieldsOrdering_Model extends Base_Model
{
    public string $table = 'done_fields_orderings';
    public string $modelTitle = 'Custom orderings for fields in card';
    public string $modelName = 'fieldsOrderings';

    protected array $hashFields = [
        'user_id',
        'entity_id',
        'field',
        'ordering',
    ];

    public array $fields = [
        'id'         => [
            'type'     => IQueryBuilder::PARAM_STR,
            'db_comment' => 'Internal unique key for a user\'s custom fields orderings',
            'required' => true,
        ],
        'user_id' => [
            'type'     => IQueryBuilder::PARAM_STR,
            'db_comment' => 'User ID. References oc_done_users_data.id',
            'link'     => User_Model::class,
            'required' => true,
        ],
        'entity_id' => [
            'type'     => IQueryBuilder::PARAM_INT,
            'db_comment' => 'Identifier of the application section. Possible values: 1 (Users), 2 (Projects), 3 (Teams), 4 (Payments)',
            'required' => true,
        ],
        'field' => [
            'type'     => IQueryBuilder::PARAM_STR,
            'db_comment' => 'Technical name of the column from the table indicated by `entity_id` (e.g., `name` from `oc_done_projects`)',
            'required' => true,
        ],
        'ordering'      => [
            'type'     => IQueryBuilder::PARAM_INT,
            'db_comment' => 'Ordering number of the field in the entity card',
            'required' => false,
        ],
        'created_at'         => [
            'type'     => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'db_comment' => 'Record creation timestamp in UTC',
            'required' => false
        ],
        'updated_at'         => [
            'type'     => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'db_comment' => 'Record last update timestamp in UTC',
            'required' => false
        ],

    ];

    /**
     * Override getListByFilter method to not use deleted filter
     * since this model is not SoftDelete
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

        $qb->select($fields)
            ->from($this->table);

        // Do not use deleted filter since this model is not SoftDelete
        $qb->where(1);

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
}