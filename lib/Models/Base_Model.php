<?php

namespace OCA\Done\Models;

use OCA\Done\Connections\DoneConnectionAdapter;
use OCA\Done\Service\BaseService;
use OCA\Done\Service\TranslateService;
use OCA\Done\Service\UserService;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

abstract class Base_Model
{
    public string $table;
    public string $modelTitle;
    public string $modelName;
    public string $dbTableComment;
    /**
     * Field definitions for the model
     * 
     * @var array<string, array{
     *     type: int,                    // Database parameter type (IQueryBuilder::PARAM_*)
     *     title?: string,               // Display name for the field
     *     required?: bool,              // Whether the field is required
     *     db_comment?: string,          // Database column comment
     *     validation_rules?: array,     // Validation rules (e.g., ['trim' => true])
     *     permission?: bool,            // Whether field requires permission check
     *     show?: bool,                  // Whether to show field in UI
     *     link?: string                 // Related model class name
     * }>
     */
    public array $fields;

    // Field used as slug
    protected string $primarySlugField = '';

    // Table fields for forming id
    protected array $hashFields = [];

    // Whether to prepare dates
    public bool $needPrepareDates = true;

    // Whether to prepare record fields
    public bool $needPrepareFields = true;

    // Remove identifier from record
    public bool $unsetIndexField = true;

    // Fields with prepared values
    public array $fieldsWithPreparedValues = [];

    // Linked models
    public array $linkedModels = [];

    // Appearance model
    public string $appearanceModel = '';

    protected array $excludedKeys = [
        'id',
        'created_at',
        'updated_at',
        'deleted',
        'hash',
    ];

    protected array $systemFields = [
        'created_at',
        'updated_at',
        'deleted',
    ];

    protected IDBConnection $db;
    protected TranslateService $translateService;
    protected BaseService $baseService;
    protected UserService $userService;

    public const SLUG_TYPE_FIELD = 1;
    public const SLUG_TYPE_ID = 2;

    public function __construct()
    {
        $this->baseService      = BaseService::getInstance();
        $this->userService      = UserService::getInstance();
        $this->translateService = TranslateService::getInstance();
        $this->db               = (new DoneConnectionAdapter())->getInstance();
        $this->setFieldsWithPreparedValues();
    }

    public function setFieldsWithPreparedValues(): void
    {
        if (!empty($this->fields)) {
            foreach ($this->fields as $field => $params) {
                if (isset($params['values'])) {
                    foreach ($params['values'] as $idx => $value) {
                        $this->fieldsWithPreparedValues[$field][$idx] = $this->translateService->getTranslate($value);
                    }
                }
            }
        }
    }

    /**
     * Get records with filter by ids
     *
     * @param string[] $ids
     * @param string[] $fields
     * @param bool $needIndex
     * @param bool $group
     * @param string $keyField
     * @param null|string $subField
     *
     * @return array
     */
    public function getList(
        array $ids = [],
        array $fields = ['*'],
        bool $needIndex = false,
        bool $group = false,
        string $keyField = 'id',
        string $subField = null
    ): array {
        $qb = $this->db->getQueryBuilder();

        $fields = $this->prepareSelectFields($fields);

        $qb->select($fields)
            ->from($this->table);

        if (!empty($ids)) {
            $qb->where($qb->expr()->in('id', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_STR_ARRAY)));
        }

        $items = $qb->executeQuery()->fetchAll();

        $items = $this->prepareItems($items);

        return $needIndex ? BaseService::makeHash($items, $keyField, $group, $subField) : $items;
    }

    /**
     * Get record by filter
     *
     * @param array<string, mixed> $filter
     * @param string[] $fields
     * @param string[] $orderBy
     *
     * @return array
     */
    public function getItemByFilter(
        array $filter = [],
        array $fields = ['*'],
        array $orderBy = [],
    ): array {
        $list = $this->getListByFilter($filter, $this->prepareSelectFields($fields), $orderBy);

        return $list[0] ?? [];
    }

    /**
     * Get record by id
     *
     * @param string $id
     * @param string[] $fields
     *
     * @return array
     */
    public function getItem(
        string $id,
        array $fields = ['*'],
    ): array {
        $fields = $this->prepareSelectFields($fields);

        $qb = $this->db->getQueryBuilder();
        $qb->select($fields)
            ->from($this->table)
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

        $item = $qb->executeQuery()->fetch();

        return $this->prepareItem($item, $this->getModelDateFields());
    }

    /**
     * Add slug and slug_type to record
     *
     * @param array<string, mixed> $item
     *
     * @return array
     */
    public function addSlugToItem(array $item): array
    {
        $primarySlugField = $this->primarySlugField ?? '';

        if (!empty($primarySlugField) && isset($this->fields[$primarySlugField]) && !empty($item[$primarySlugField])) {
            $item['slug']      = $item[$primarySlugField];
            $item['slug_type'] = self::SLUG_TYPE_FIELD;

            return $item;
        }

        $item['slug']      = $item['id'];
        $item['slug_type'] = self::SLUG_TYPE_ID;

        return $item;
    }

    /**
     * Get record slug
     *
     * @param array<string, mixed> $item
     * @param null|string $itemId
     *
     * @return int|string
     */
    public function getItemSlug(array $item = [], ?string $itemId = null): int|string
    {
        $primarySlugField = $this->primarySlugField ?? '';

        if (empty($item) && !empty($itemId)) { // For new records
            $item = $this->getItem($itemId);
        }

        if (!empty($primarySlugField) && isset($this->fields[$primarySlugField]) && !empty($item[$primarySlugField])) {
            $slug = $item[$primarySlugField];
        } else {
            $slug = $item['id'];
        }

        return $slug;
    }

    /**
     * Get records
     *
     * @param array<string, mixed> $filter
     * @param string[] $fields
     * @param string[] $orderBy
     * @param string[] $additionalOrderBy
     * @param bool $needDeleted
     *
     * @return array
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

    public function prepareItems(array $items = []): array
    {
        $dateFields = $this->getModelDateFields();

        return array_map(function ($item) use ($dateFields) {
            return $this->prepareItem($item, $dateFields);
        }, $items);
    }

    public function prepareItem(array $item = [], array $dateFields = []): array
    {
        if ($this->unsetIndexField) {
            //unset($item['id']);
        }

        if ($this->needPrepareDates) {
            foreach ($dateFields as $dateField) {
                if (isset($item[$dateField])) {
                    $item[$dateField] = (new \DateTimeImmutable($item[$dateField]))->format('Y-m-d\TH:i:s.v\Z');
                }
            }
        }

        if ($this->needPrepareFields) {
            $item = $this->prepareItemFields($item, $this->fieldsWithPreparedValues);
        }

        return $this->addSlugToItem($item);
    }

    public function getModelDateFields(): array
    {
        $result = [];

        foreach ($this->fields as $field => $params) {
            if (
                in_array(
                    $params['type'],
                    [IQueryBuilder::PARAM_DATETIME_IMMUTABLE, IQueryBuilder::PARAM_DATE_IMMUTABLE]
                )
            ) {
                $result[] = $field;
            }
        }

        return $result;
    }

    /**
     * Get records indexed by field
     *
     * @param string $indexField
     * @param array<string, mixed> $filter
     * @param string[] $fields
     * @param string[] $orderBy
     * @param string[] $additionalOrderBy
     * @param bool $needDeleted
     *
     * @return array
     */
    public function getIndexedListByFilter(
        string $indexField = 'id',
        array $filter = [],
        array $fields = ['*'],
        array $orderBy = [],
        array $additionalOrderBy = [],
        bool $needDeleted = false,
    ): array {
        $list = $this->getListByFilter(
            $filter,
            $this->prepareSelectFields($fields),
            $orderBy,
            $additionalOrderBy,
            $needDeleted
        );

        return BaseService::makeHash($list, $indexField);
    }

    /**
     * Generate filter
     *
     * @param IQueryBuilder $qb
     * @param array<string, mixed> $filter
     *
     * @return IQueryBuilder
     */
    public function makeFilter(IQueryBuilder $qb, array $filter): IQueryBuilder
    {
        foreach ($filter as $field => $params) {
            if (!isset($this->fields[$field])) {
                continue;
            }

            $modelField = $this->fields[$field];
            $fieldType  = $modelField['type'];

            $operation = $params[0] ?? null;
            $value     = is_array($params) ? $params[1] : $params;
            $typeParam = $params[2] ?? null;

            if (
                in_array(
                    $fieldType,
                    [IQueryBuilder::PARAM_DATETIME_IMMUTABLE, IQueryBuilder::PARAM_DATE_IMMUTABLE]
                ) && !$value instanceof \DateTimeImmutable && !is_array($value)
            ) {
                $value = new \DateTimeImmutable($value);
            }

            switch ($operation) {
                case '=':
                    $qb->andWhere($qb->expr()->eq($field, $qb->createNamedParameter($value, $fieldType)));
                    break;
                case '!=':
                    $qb->andWhere($qb->expr()->neq($field, $qb->createNamedParameter($value, $fieldType)));
                    break;
                case '>':
                    $qb->andWhere($qb->expr()->gt($field, $qb->createNamedParameter($value, $fieldType)));
                    break;
                case '<':
                    $qb->andWhere($qb->expr()->lt($field, $qb->createNamedParameter($value, $fieldType)));
                    break;
                case '>=':
                    $qb->andWhere($qb->expr()->gte($field, $qb->createNamedParameter($value, $fieldType)));
                    break;
                case '<=':
                    $qb->andWhere($qb->expr()->lte($field, $qb->createNamedParameter($value, $fieldType)));
                    break;
                case 'IN':
                    $qb->andWhere(
                        $qb->expr()->in(
                            $field,
                            $qb->createNamedParameter($value, $typeParam ?? IQueryBuilder::PARAM_INT_ARRAY)
                        )
                    );
                    break;
                case 'NOT IN':
                    $qb->andWhere(
                        $qb->expr()->notIn(
                            $field,
                            $qb->createNamedParameter($value, $typeParam ?? IQueryBuilder::PARAM_INT_ARRAY)
                        )
                    );
                    break;
                case 'IS NULL':
                    $qb->andWhere($qb->expr()->isNull($field));
                    break;
                case 'IS NOT NULL':
                    $qb->andWhere($qb->expr()->isNotNull($field));
                    break;
                case 'LIKE':
                    $qb->andWhere($qb->expr()->like($field, $qb->createNamedParameter("%{$value}%", $fieldType)));
                    break;
                case 'NOT LIKE':
                    $qb->andWhere($qb->expr()->notLike($field, $qb->createNamedParameter("%{$value}%", $fieldType)));
                    break;
                case 'BETWEEN':
                    $qb->andWhere($qb->expr()->gte($field, $qb->createNamedParameter($value[0])));
                    $qb->andWhere($qb->expr()->lte($field, $qb->createNamedParameter($value[1])));
                    break;
                case 'OR':
                    $conditions = [];

                    foreach ($value as $condition) {
                        $expression = $this->getQbExpression($qb, $field, $condition);

                        if (!empty($expression)) {
                            $conditions[] = $expression;
                        }
                    }

                    if (count($conditions) == 1) {
                        $qb->andWhere(...$conditions);
                    } else {
                        $qb->andWhere(
                            $qb->expr()->orX(...$conditions)
                        );
                    }
                    break;
                case 'AND':
                    $conditions = [];

                    foreach ($value as $condition) {
                        $expression = $this->getQbExpression($qb, $field, $condition);

                        if (!empty($expression)) {
                            $conditions[] = $expression;
                        }
                    }

                    if (count($conditions) == 1) {
                        $qb->andWhere(...$conditions);
                    } else {
                        $qb->andWhere(
                            $qb->expr()->andX(...$conditions)
                        );
                    }
                    break;
                default:
                    $qb->andWhere($qb->expr()->eq($field, $qb->createNamedParameter($value, $fieldType)));
                    break;
            }
        }

        return $qb;
    }

    public function getQbExpression(IQueryBuilder $qb, string $field, array $params): string
    {
        if (!isset($this->fields[$field])) {
            return '';
        }

        $modelField = $this->fields[$field];
        $fieldType  = $modelField['type'];
        $operation  = $params[0] ?? null;
        $value      = $params[1] ?? null;
        $typeParam  = $params[2] ?? null;

        if (
            $value == '' &&
            in_array($operation, ['=', '!=', '', null]) &&
            !in_array($fieldType, [IQueryBuilder::PARAM_STR, IQueryBuilder::PARAM_LOB])
        ) {
            return '';
        }

        return match ($operation) {
            '=' => $qb->expr()->eq($field, $qb->createNamedParameter($value, $fieldType)),
            '!=' => $qb->expr()->neq($field, $qb->createNamedParameter($value, $fieldType)),
            '>' => $qb->expr()->gt($field, $qb->createNamedParameter($value, $fieldType)),
            '<' => $qb->expr()->lt($field, $qb->createNamedParameter($value, $fieldType)),
            '>=' => $qb->expr()->gte($field, $qb->createNamedParameter($value, $fieldType)),
            '<=' => $qb->expr()->lte($field, $qb->createNamedParameter($value, $fieldType)),
            'IN' => $qb->expr()->in(
                $field,
                $qb->createNamedParameter($value, $typeParam ?? IQueryBuilder::PARAM_INT_ARRAY)
            ),
            'NOT IN' => $qb->expr()->notIn(
                $field,
                $qb->createNamedParameter($value, $typeParam ?? IQueryBuilder::PARAM_INT_ARRAY)
            ),
            'IS NULL' => $qb->expr()->isNull($field),
            'IS NOT NULL' => $qb->expr()->isNotNull($field),
            'LIKE' => $qb->expr()->like($field, $qb->createNamedParameter("%{$value}%", $fieldType)),
            'NOT LIKE' => $qb->expr()->notLike($field, $qb->createNamedParameter("%{$value}%", $fieldType)),
            default => $qb->expr()->eq($field, $qb->createNamedParameter($params, $fieldType)),
        };
    }

    /**
     * Create record
     *
     * @param array<string, mixed> $data
     *
     * @return null|string
     */
    public function addData(array $data): ?string
    {
        $dataToSave      = [];
        $currentDateTime = (new \DateTimeImmutable());

        $query = $this->db->getQueryBuilder();
        $query->insert($this->table);

        foreach ($data as $field => $value) {
            if (!isset($this->fields[$field])) {
                continue;
            }

            $type  = $this->fields[$field]['type'];
            $value = in_array(
                $type,
                [IQueryBuilder::PARAM_DATETIME_IMMUTABLE, IQueryBuilder::PARAM_DATE_IMMUTABLE]
            ) && !empty($value) && !($value instanceof \DateTimeImmutable) ? (new \DateTimeImmutable($value)) : $value;

            $dataToSave[$field] = $query->createNamedParameter($value, $type ?? IQueryBuilder::PARAM_STR);
        }

        if (empty($dataToSave)) {
            return null;
        }

        if (isset($this->fields['created_at'])) {
            $dataToSave['created_at'] = $query->createNamedParameter(
                $currentDateTime,
                IQueryBuilder::PARAM_DATETIME_IMMUTABLE
            );
        }

        if (isset($this->fields['updated_at'])) {
            $dataToSave['updated_at'] = $query->createNamedParameter(
                $currentDateTime,
                IQueryBuilder::PARAM_DATETIME_IMMUTABLE
            );
        }

        if (isset($this->fields['id']) && !empty($this->hashFields)) {
            $dataToSave['id'] = $query->createNamedParameter(
                $this->prepareMd5Hash($currentDateTime->format('Y-m-d H:i:s'))
            );
        }

        $query->values($dataToSave);
        $query->executeStatement();

        return $this->getLastInsertId();
    }

    public function getLastInsertId(): ?string
    {
        if (!isset($this->fields['created_at'])) {
            return null;
        }

        $item = $this->getItemByFilter([], ['*'], ['created_at', 'DESC']);

        return $item['id'] ?? null;
    }

    /**
     * Update record
     *
     * @param array<string, mixed> $data
     * @param string $id
     *
     * @return string
     */
    public function update(array $data, string $id): string
    {
        unset($data['id'], $data['hash']);

        $query = $this->db->getQueryBuilder();
        $query->update($this->table);

        foreach ($data as $field => $value) {
            if (!isset($this->fields[$field])) {
                continue;
            }

            $type  = $this->fields[$field]['type'];
            $value = in_array(
                $type,
                [IQueryBuilder::PARAM_DATETIME_IMMUTABLE, IQueryBuilder::PARAM_DATE_IMMUTABLE]
            ) && !empty($value) && !($value instanceof \DateTimeImmutable) ? (new \DateTimeImmutable($value)) : $value;

            $query->set($field, $query->createNamedParameter($value, $type ?? IQueryBuilder::PARAM_STR));
        }

        if (isset($this->fields['updated_at'])) {
            $currentDateTime = new \DateTimeImmutable();
            $query->set(
                'updated_at',
                $query->createNamedParameter(
                    $currentDateTime,
                    IQueryBuilder::PARAM_DATETIME_IMMUTABLE
                )
            );
        }

        $query->where($query->expr()->eq('id', $query->createNamedParameter($id)));
        $query->executeStatement();

        return $id;
    }

    /**
     * Delete record
     *
     * @param string $id
     *
     * @return void
     */
    public function delete(string $id): void
    {
        $query = $this->db->getQueryBuilder();

        if (isset($this->fields['deleted'])) {
            $query->update($this->table);
            $query->set('deleted', $query->createNamedParameter(1))
                ->where($query->expr()->eq('id', $query->createNamedParameter($id)))
                ->executeStatement();
        } else {
            $query->delete($this->table)
                ->where($query->expr()->eq('id', $query->createNamedParameter($id)))
                ->executeStatement();
        }
    }

    /**
     * Delete record
     *
     * @param array $filter
     * @return void
     * @throws Exception
     */
    public function deleteByFilter(array $filter = []): void
    {
        $query = $this->db->getQueryBuilder();

        if (isset($this->fields['deleted'])) {
            $query->update($this->table);
            $query->set('deleted', $query->createNamedParameter(1));
        } else {
            $query->delete($this->table);
        }

        $query->where(1);

        if (!empty($filter)) {
            $query = $this->makeFilter($query, $filter);
        }

        $query->executeStatement();
    }

    /**
     * Get record with linked fields from other models
     *
     * @param string $id
     * @param string[] $fields
     * @param false $returnLinkedRecords
     *
     * @return array
     */
    public function getLinkedItem(
        string $id = null,
        array $fields = ['*'],
        bool $returnLinkedRecords = false
    ): array {
        if (!$id) {
            return [];
        }

        return $this->getLinkedList(['id' => $id], $fields, $returnLinkedRecords)[0] ?? [];
    }

    /**
     * Get records with linked fields from other models
     *
     * @param array<string, mixed> $filter
     * @param string[] $fields
     * @param false $returnLinkedRecords
     *
     * @return array
     */
    public function getLinkedList(
        array $filter = [],
        array $fields = ['*'],
        bool $returnLinkedRecords = false
    ): array {
        $result = $models = $fieldsValues = $indexedList = [];

        $list = $this->getListByFilter($filter, $fields);

        if ($fields == ['*']) {
            foreach ($this->fields as $field => $params) {
                if (!isset($params['link'])) {
                    continue;
                }

                $models[$field] = $params['link'];

                $fieldsValues[$field] = BaseService::getField($list, $field);
            }
        } else {
            foreach ($fields as $field) {
                $modelField = $this->fields[$field] ?? null;

                if (!$modelField || !isset($modelField['link'])) {
                    continue;
                }

                $models[$field] = $modelField['link'];

                $fieldsValues[$field] = BaseService::getField($list, $field);
            }
        }


        foreach ($models as $field => $link) {
            $values              = $fieldsValues[$field] ?? [];
            $values              = array_unique($values);
            $linkedModel         = new $link();
            $indexedList[$field] = $this->getValuesByIdForModel($values, $linkedModel);
        }

        foreach ($list as $idx => $item) {
            foreach ($item as $field => $value) {
                if (!isset($this->fields[$field]) || !isset($this->fields[$field]['link'])) {
                    $result[$idx][$field] = $value;
                    continue;
                }

                if ($returnLinkedRecords) {
                    $result[$idx][$field]            = $indexedList[$field][$value] ?? $value;
                    $result[$idx]["{$field}_linked"] = $indexedList[$field][$value]['name'] ?? $value;
                } else {
                    $result[$idx][$field] = $indexedList[$field][$value]['name'] ?? $value;
                }
            }
        }

        return $result;
    }

    /**
     * Get records by ID from specified model
     *
     * @param string[] $values
     * @param Base_Model $model
     *
     * @return array
     */
    public function getValuesByIdForModel(array $values, Base_Model $model): array
    {
        return !empty($values) ?
            $model->getListForLink(
                ['id' => ['IN', $values, IQueryBuilder::PARAM_STR_ARRAY]],
                true
            ) : [];
    }

    /**
     * Validate data before saving/updating
     *
     * @param array<string, mixed> $data
     * @param bool $save
     *
     * @return array
     */
    public function validateData(array $data, bool $save = false, array $ignoreFields = []): array
    {
        return $save ?
            $this->validateDataForSave($data, $ignoreFields) :
            $this->validateDataForUpdate($data, $ignoreFields);
    }

    /**
     * Validation for saving
     *
     * @param array<string, mixed> $data
     *
     * @return array
     */
    public function validateDataForSave(array $data = [], array $ignoreFields = []): array
    {
        $result = $errors = [];

        foreach ($this->fields as $field => $params) {
            if (in_array($field, $this->excludedKeys) || in_array($field, $ignoreFields)) {
                continue;
            }

            $required = $params['required'] ?? false;
            $needTrim = $params['validation_rules']['trim'] ?? false;
            $unsigned = $params['unsigned'] ?? false;
            $title    = $params['title'] ?? '';
            $type     = $params['type'] ?? '';

            $value = $data[$field] ?? null;

            if (empty($value) && $required) {
                $errors[] = $this->translateService->getTranslate(
                    "The «{%s}» field must be filled in",
                    [$this->translateService->getTranslate($title)]
                );
            }

            if ($unsigned && (int)$value < 0) {
                $errors[] = $this->translateService->getTranslate(
                    "The «{%s}» field must not contain a negative value",
                    [$this->translateService->getTranslate($title)]
                );
            }

            if ($needTrim) {
                $value = trim($value);
            }

            if (
                in_array($type, [IQueryBuilder::PARAM_DATETIME_IMMUTABLE, IQueryBuilder::PARAM_DATE_IMMUTABLE])
                && isset($value)
            ) {
                $value = (new \DateTimeImmutable($value));
            }

            $result[$field] = $value;
        }

        return [$result, $errors];
    }

    /**
     * Validation for updating
     *
     * @param array<string, mixed> $data
     *
     * @return array
     */
    public function validateDataForUpdate(array $data = [], array $ignoreFields = []): array
    {
        $result = $errors = [];

        if (isset($data['id'])) {
            $result['id'] = $data['id'];
        }

        foreach ($data as $key => $value) {
            if (!isset($this->fields[$key]) || in_array($key, $this->excludedKeys) || in_array($key, $ignoreFields)) {
                continue;
            }

            $params   = $this->fields[$key];
            $required = $params['required'] ?? false;
            $needTrim = $params['validation_rules']['trim'] ?? false;
            $unsigned = $params['unsigned'] ?? false;
            $title    = $params['title'] ?? '';
            $type     = $params['type'] ?? '';

            $value = $value ?? null;

            if (empty($value) && $required) {
                $errors[] = $this->translateService->getTranslate(
                    "The «{%s}» field must be filled in",
                    [$this->translateService->getTranslate($title)]
                );
            }

            if ($unsigned && (int)$value < 0) {
                $errors[] = $this->translateService->getTranslate(
                    "The «{%s}» field must not contain a negative value",
                    [$this->translateService->getTranslate($title)]
                );
            }

            if ($needTrim) {
                $value = trim($value);
            }

            if (
                in_array($type, [IQueryBuilder::PARAM_DATETIME_IMMUTABLE, IQueryBuilder::PARAM_DATE_IMMUTABLE])
                && isset($value)
            ) {
                $value = (new \DateTimeImmutable($value));
            }

            $result[$key] = $value;
        }

        return [$result, $errors];
    }

    /**
     * Get records for linking to other record fields (helper function for getLinkedList)
     *
     * @param array<string, mixed> $filter
     * @param bool $needIndex
     *
     * @return array
     */
    public function getListForLink(
        array $filter = [],
        bool $needIndex = false
    ): array {
        $fields = ['id', 'name'];

        if ($needIndex) {
            return BaseService::makeHash(
                $this->getListByFilter($filter, $fields),
            );
        }

        return $this->getListByFilter($filter, $fields);
    }

    /**
     * Add slug and slug_type to array of records
     *
     * @param array $items
     *
     * @return array
     */
    public function addSlugsToItems(
        array $items = [],
    ): array {
        if (empty($items)) {
            return [];
        }

        foreach ($items as $idx => $item) {
            $items[$idx] = $this->addSlugToItem($item);
        }

        return $items;
    }

    /**
     * Prepare filter by slug
     *
     * @param string|int $slug
     * @param int $slugType
     *
     * @return array<string, int|string>
     */
    public function prepareSlugFilter(string|int $slug, int $slugType): array
    {
        $filter = [];

        switch ($slugType) {
            case self::SLUG_TYPE_FIELD:
                $filter[$this->primarySlugField] = (string)$slug;
                break;
            case self::SLUG_TYPE_ID:
                $filter['id'] = $slug;
                break;
        }

        return $filter;
    }

    /**
     * Prepare list of fields to retrieve
     *
     * @param string[] $fields
     *
     * @return string[]
     */
    public function prepareSelectFields(array $fields): array
    {
        if (!in_array('*', $fields)) {
            if (isset($this->fields['id']) && !in_array('id', $fields)) {
                $fields[] = 'id';
            }
            if (
                !empty($this->primarySlugField) &&
                isset($this->fields[$this->primarySlugField]) &&
                !in_array($this->primarySlugField, $fields)
            ) {
                $fields[] = $this->primarySlugField;
            }
        }

        return $fields;
    }

    /**
     * Get record ID by slug and slug_type
     *
     * @param int|string|null $slug
     * @param int|null $slugType
     *
     * @return string|null
     */
    public function getItemId(int|string|null $slug, ?int $slugType): ?string
    {
        if (empty($slug) && empty($slugType)) {
            return null;
        }

        if (!empty($slug) && empty($slugType)) {
            return $this->getItemIdBySlug($slug);
        }

        $filter = $this->prepareSlugFilter($slug, $slugType);

        if (empty($filter)) {
            return null;
        }

        $item = $this->getItemByFilter($filter);

        if (empty($item)) {
            return null;
        }

        return $item['id'];
    }

    /**
     * Get record ID by slug
     *
     * @param int|string|null $slug
     *
     * @return string|null
     */
    public function getItemIdBySlug(int|string|null $slug): int|string|null
    {
        if (empty($slug)) {
            return null;
        }

        $item = [];

        if (!empty($this->primarySlugField) && isset($this->fields[$this->primarySlugField])) {
            $item = $this->getItemByFilter([$this->primarySlugField => $slug]);
        }

        if (empty($item)) {
            $idType = $this->fields['id']['type'];
            $slug = $idType == IQueryBuilder::PARAM_INT ? (int)$slug : $slug;
            $item = $this->getItemByFilter(['id' => $slug]);
        }

        return $item['id'] ?? null;
    }

    /**
     * Generate md5 id for new record
     *
     * @param string $dateTime
     * @param array $dataToSave
     *
     * @return string
     */
    public function prepareMd5Hash(string $dateTime, array $dataToSave = []): string
    {
        $stringForHash = $dateTime;

        foreach ($this->hashFields as $field) {
            $hashFieldValue = $dataToSave[$field] ?? '';
            $stringForHash  .= "_{$hashFieldValue}";
        }

        return BaseService::makeMd5Hash($stringForHash);
    }

    /**
     * Update record for migrations (to be removed)
     */
    public function updateOld(array $data, int $id): void
    {
        $query = $this->db->getQueryBuilder();
        $query->update($this->table);

        foreach ($data as $field => $value) {
            if (!isset($this->fields[$field])) {
                continue;
            }
            $query->set($field, $query->createNamedParameter($value));
        }

        if (isset($this->fields['updated_at'])) {
            $query->set(
                'updated_at',
                $query->createNamedParameter(
                    (new \DateTimeImmutable()),
                    IQueryBuilder::PARAM_DATETIME_IMMUTABLE
                )
            );
        }

        $query->where($query->expr()->eq('id', $query->createNamedParameter($id)))
            ->executeStatement();
    }

    /**
     * Create/update record by filter
     *
     * @param array $data
     * @param array $filter
     *
     * @return string
     *
     */
    public function upsertByFilter(array $data = [], array $filter = []): string
    {
        $item = $this->getItemByFilter($filter);

        return empty($item) ? $this->addData($data) : $this->update($data, $item['id']);
    }

    /**
     * Prepare record fields
     *
     * @param array $data
     * @param array $modelFieldsWithPreparedValues
     *
     * @return array
     *
     */
    public function prepareItemsFields(array $data = [], array $modelFieldsWithPreparedValues = []): array
    {
        return array_map(function ($item) use ($modelFieldsWithPreparedValues) {
            return $this->prepareItemFields($item, $modelFieldsWithPreparedValues);
        }, $data);
    }

    /**
     * Prepare record field
     *
     * @param array $item
     * @param array $modelFieldsWithPreparedValues
     *
     * @return array
     *
     */
    public function prepareItemFields(array $item = [], array $modelFieldsWithPreparedValues = []): array
    {
        foreach ($modelFieldsWithPreparedValues as $field => $values) {
            if (!isset($item[$field])) {
                continue;
            }

            $item[$field] = $values[$item[$field]] ?? null;
        }

        return $item;
    }

    /**
     * Prepare data before sending to frontend
     *
     * @param array $data
     * @param int $entityId
     * @param bool $isList
     *
     * @return array
     *
     */
    public function prepareDataBeforeSend(array $data, int $entityId, bool $isList = false): array
    {
        $userId    = $this->userService->getCurrentUserId();
        $globalRoles = $this->userService->getUserGlobalRoles($userId);

        $filter = [
            'entity_id'      => $entityId,
            'global_role_id' => ['IN', $globalRoles],
        ];

        $permissions = (new RolesPermissions_Model())->getFieldsReadPermissions($entityId, $filter);

        if ($isList) {
            foreach ($data as $idx => $item) {
                foreach ($item as $field => $value) {
                    if ((isset($permissions[$field]) && !$permissions[$field]) || in_array(
                            $field,
                            $this->systemFields
                        )) {
                        unset($data[$idx][$field]);
                    }
                }
            }

            return $data;
        }

        foreach ($data as $field => $value) {
            if ((isset($permissions[$field]) && !$permissions[$field]) || in_array($field, $this->systemFields)) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Get allowed fields from model
     *
     * @return array
     *
     */
    public function getAvailableEntityFields(): array
    {
        $result = array_filter($this->fields, function($params) {
            return $params['show'] ?? false;
        });

        return !empty($result) ? array_keys($result) : array_keys($this->fields);
    }
}