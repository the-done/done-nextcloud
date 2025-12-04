<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Models;

use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class FieldComment_Model
 *
 * Comments for core and dynamic fields.
 * Field "field" is either the core field name or the dynamic field id.
 */
class FieldComment_Model extends Base_Model
{
    public string $table = 'done_field_comments';
    public string $modelTitle = 'Field comments';
    public string $modelName = 'fieldCommentsModel';
    public string $dbTableComment = 'Comments for core and dynamic fields. Core fields are addressed by name; dynamic fields are addressed by dynamic field id (references oc_done_dynamic_fields.id).';

    protected array $hashFields = [
        'source',
        'field',
        'comment',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Unique identifier for comment',
        ],
        'source' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Source entity',
            'required'   => true,
            'db_comment' => 'Entity source identifier. See OCA\Done\Models\PermissionsEntities_Model constants (e.g., 1 - Users, 2 - Projects).',
            'show'       => true,
        ],
        'field' => [
            'type'             => IQueryBuilder::PARAM_STR,
            'title'            => 'Field key',
            'required'         => true,
            'validation_rules' => ['trim' => true],
            'db_comment'       => 'Core field: field name (e.g., "name"). Dynamic field: dynamic field id (references oc_done_dynamic_fields.id).',
            'show'             => true,
        ],
        'comment' => [
            'type'             => IQueryBuilder::PARAM_LOB,
            'title'            => 'Comment',
            'required'         => true,
            'validation_rules' => ['trim' => true],
            'db_comment'       => 'Comment text',
            'show'             => true,
        ],
        'created_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Created at',
            'db_comment' => 'Record creation timestamp in UTC',
        ],
        'updated_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Updated at',
            'db_comment' => 'Record last update timestamp in UTC',
        ],
    ];

    /**
     * Fetch all comments for a specific source+field pair
     */
    public function getFieldCommentsForField(int $source, string $field): array
    {
        return $this->getIndexedListByFilter(
            'field',
            [
                'source' => ['=', $source],
                'field'  => ['=', $field],
            ],
            ['id', 'source', 'field', 'comment', 'created_at', 'updated_at'],
            ['created_at', 'ASC']
        );
    }

    /**
     * Fetch all comments for a specific source
     */
    public function getFieldCommentsBySource(int $source): array
    {
        return $this->getIndexedListByFilter(
            'field',
            [
                'source' => ['=', $source],
            ],
            ['id', 'source', 'field', 'comment', 'created_at', 'updated_at'],
            ['created_at', 'ASC']
        );
    }

    /**
     * Check if comment already exists for field
     */
    public function commentExistsForField(int $source, string $field): bool
    {
        $result = $this->getListByFilter(
            [
                'source' => ['=', $source],
                'field'  => ['=', $field],
            ],
            ['id']
        );

        return !empty($result);
    }
}
