<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Agreement\Model;

use OCA\Done\Models\BaseModel;
use OCA\Done\Models\UserModel;
use OCA\Done\Modules\Projects\Models\ProjectModel;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class AgreementSchemeAssignmentsModel.
 *
 * Assignments of agreement schemes to specific entities (user, project, etc.).
 * Allows overriding the default scheme for specific cases.
 */
class AgreementSchemeAssignmentsModel extends BaseModel
{
    public string $table = 'done_agr_sch_assgmts';
    public string $modelTitle = 'Agreement Scheme Assignments';
    public string $modelName = 'agreementSchemeAssignments';
    public string $dbTableComment = 'Agreement scheme assignments: links schemes to specific entities for custom agreement flows.';

    protected array $hashFields = [
        'scheme_id',
        'entity_type',
        'sub_entity_type',
        'sub_entity_id',
        'priority',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'required'   => true,
            'db_comment' => 'Unique identifier for a scheme assignment',
        ],
        'scheme_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Scheme',
            'required'   => true,
            'link'       => AgreementSchemesModel::class,
            'db_comment' => 'Agreement scheme ID. References oc_done_agr_schemes.id',
        ],
        'entity_type' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Entity type',
            'required'   => true,
            'db_comment' => 'Type of main entity (e.g., vacation)',
        ],
        'sub_entity_type' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Sub-entity type',
            'required'   => false,
            'db_comment' => 'Type of sub-entity for targeting (e.g., user, project)',
        ],
        'sub_entity_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Sub-entity ID',
            'required'   => false,
            'db_comment' => 'ID of the sub-entity (user_id, project_id, etc.)',
        ],
        'priority' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Priority',
            'required'   => false,
            'db_comment' => 'Priority for assignment resolution (higher = more specific)',
        ],
        'created_by' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Created by',
            'required'   => false,
            'link'       => UserModel::class,
            'db_comment' => 'User who created the assignment. References oc_done_users_data.id',
        ],
        'created_at' => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC',
        ],
        'deleted' => [
            'type'       => IQueryBuilder::PARAM_BOOL,
            'title'      => 'Deleted',
            'db_comment' => 'Soft delete flag (1=deleted, 0=active)',
        ],
    ];

    // Sub-entity types
    public const SUB_ENTITY_USER = 'user';
    public const SUB_ENTITY_PROJECT = 'project';

    // Priority levels
    public const PRIORITY_DEFAULT = 0;
    public const PRIORITY_PROJECT = 10;
    public const PRIORITY_USER = 20;

    /**
     * Find the most specific scheme assignment for given context.
     * Resolution is done in a single query joined with the schemes table so only
     * assignments pointing to an active, non-deleted scheme are considered.
     * Matches user-specific before project-specific via priority DESC ordering.
     *
     * @param string      $entityType Main entity type (e.g., 'vacation')
     * @param null|string $userId     User ID (if applicable)
     * @param null|string $projectId  Project ID (if applicable)
     *
     * @return null|array Assignment record (with joined scheme) or null
     */
    public function findAssignment(string $entityType, ?string $userId = null, ?string $projectId = null): ?array
    {
        if ($userId === null && $projectId === null) {
            return null;
        }

        $schemesModel = new AgreementSchemesModel();

        $qb = $this->db->getQueryBuilder();
        $qb->select('a.*')
            ->from($this->table, 'a')
            ->innerJoin(
                'a',
                $schemesModel->table,
                's',
                $qb->expr()->eq('a.scheme_id', 's.id')
            )
            ->where($qb->expr()->eq('a.deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->andWhere($qb->expr()->eq('s.deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->andWhere($qb->expr()->eq('s.is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->andWhere($qb->expr()->eq('a.entity_type', $qb->createNamedParameter($entityType)));

        $subEntityConditions = [];

        if ($userId !== null) {
            $subEntityConditions[] = $qb->expr()->andX(
                $qb->expr()->eq('a.sub_entity_type', $qb->createNamedParameter(self::SUB_ENTITY_USER)),
                $qb->expr()->eq('a.sub_entity_id', $qb->createNamedParameter($userId))
            );
        }

        if ($projectId !== null) {
            $subEntityConditions[] = $qb->expr()->andX(
                $qb->expr()->eq('a.sub_entity_type', $qb->createNamedParameter(self::SUB_ENTITY_PROJECT)),
                $qb->expr()->eq('a.sub_entity_id', $qb->createNamedParameter($projectId))
            );
        }

        $qb->andWhere($qb->expr()->orX(...$subEntityConditions))
            ->orderBy('a.priority', 'DESC')
            ->addOrderBy('a.created_at', 'DESC')
            ->setMaxResults(1);

        $result = $qb->executeQuery()->fetch();

        return $result ?: null;
    }

    /**
     * Get scheme for given context (with fallback to default).
     */
    public function getSchemeForContext(string $entityType, ?string $userId = null, ?string $projectId = null): ?array
    {
        $schemesModel = new AgreementSchemesModel();

        // Try to find specific assignment (already checked scheme active/not-deleted)
        $assignment = $this->findAssignment($entityType, $userId, $projectId);

        if (!empty($assignment)) {
            $scheme = $schemesModel->getItem($assignment['scheme_id']);

            if (!empty($scheme)) {
                return $scheme;
            }
        }

        // Fall back to default scheme
        return $schemesModel->getDefaultScheme($entityType);
    }

    /**
     * Create a new scheme assignment.
     *
     * @param string      $schemeId      Scheme ID
     * @param string      $entityType    Main entity type (e.g., 'vacation')
     * @param string      $subEntityType Sub-entity type ('user' or 'project')
     * @param string      $subEntityId   Sub-entity ID
     * @param null|string $createdBy     User ID who creates the assignment
     *
     * @return null|array Created assignment or null
     */
    public function createAssignment(
        string $schemeId,
        string $entityType,
        string $subEntityType,
        string $subEntityId,
        ?string $createdBy = null
    ): ?array {
        // Determine priority based on sub-entity type
        $priority = match ($subEntityType) {
            self::SUB_ENTITY_USER    => self::PRIORITY_USER,
            self::SUB_ENTITY_PROJECT => self::PRIORITY_PROJECT,
            default                  => self::PRIORITY_DEFAULT,
        };

        // Check if assignment already exists
        $existing = $this->getItemByFilter([
            'entity_type'     => $entityType,
            'sub_entity_type' => $subEntityType,
            'sub_entity_id'   => $subEntityId,
        ]);

        if (!empty($existing)) {
            // Update existing assignment
            $this->update([
                'scheme_id' => $schemeId,
                'priority'  => $priority,
            ], $existing['id']);

            return $this->getItem($existing['id']);
        }

        // Create new assignment
        $data = [
            'scheme_id'       => $schemeId,
            'entity_type'     => $entityType,
            'sub_entity_type' => $subEntityType,
            'sub_entity_id'   => $subEntityId,
            'priority'        => $priority,
            'created_by'      => $createdBy,
        ];

        $id = $this->addData($data);

        return $id ? $this->getItem($id) : null;
    }

    /**
     * Delete an assignment.
     *
     * @param string $assignmentId Assignment ID
     */
    public function deleteAssignment(string $assignmentId): void
    {
        $this->delete($assignmentId);
    }

    /**
     * Delete assignment by sub-entity.
     *
     * @param string $entityType    Main entity type
     * @param string $subEntityType Sub-entity type
     * @param string $subEntityId   Sub-entity ID
     *
     * @return bool Success
     */
    public function deleteAssignmentBySubEntity(
        string $entityType,
        string $subEntityType,
        string $subEntityId
    ): bool {
        $assignment = $this->getItemByFilter([
            'entity_type'     => $entityType,
            'sub_entity_type' => $subEntityType,
            'sub_entity_id'   => $subEntityId,
        ]);

        if (!empty($assignment)) {
            $this->delete($assignment['id']);
        }

        return true;
    }

    /**
     * Get all assignments for a scheme.
     *
     * @param string $schemeId Scheme ID
     *
     * @return array List of assignments
     *
     * @throws Exception
     */
    public function getAssignmentsForScheme(string $schemeId): array
    {
        return $this->getListByFilter(['scheme_id' => $schemeId]);
    }

    /**
     * Get all assignments for an entity type.
     *
     * @param string $entityType Entity type
     *
     * @return array List of assignments
     *
     * @throws Exception
     */
    public function getAssignmentsForEntityType(string $entityType): array
    {
        return $this->getListByFilter(['entity_type' => $entityType]);
    }

    /**
     * Get all assignments with enriched data (scheme name, sub-entity name).
     *
     * @param string $entityType Entity type
     *
     * @return array List of assignments with extra data
     */
    public function getEnrichedAssignments(string $entityType): array
    {
        $assignments = $this->getAssignmentsForEntityType($entityType);
        $schemesModel = new AgreementSchemesModel();

        // Enrich with scheme names
        foreach ($assignments as &$assignment) {
            $scheme = $schemesModel->getItem($assignment['scheme_id']);
            $assignment['scheme_name'] = $scheme['name'] ?? 'Unknown';

            // Enrich with sub-entity name (this would need to be extended for each sub-entity type)
            $assignment['sub_entity_name'] = $this->getSubEntityName(
                $assignment['sub_entity_type'],
                $assignment['sub_entity_id']
            );
        }

        return $assignments;
    }

    /**
     * Get sub-entity name for display.
     *
     * @param string $subEntityType Sub-entity type
     * @param string $subEntityId   Sub-entity ID
     *
     * @return string Name or ID if not found
     */
    protected function getSubEntityName(string $subEntityType, string $subEntityId): string
    {
        switch ($subEntityType) {
            case self::SUB_ENTITY_USER:
                $userModel = new UserModel();
                $user = $userModel->getItem($subEntityId);

                return $user['display_name'] ?? $user['user_id'] ?? $subEntityId;

            case self::SUB_ENTITY_PROJECT:
                // Import project model dynamically to avoid circular dependencies
                $projectsModel = new ProjectModel();
                $project = $projectsModel->getItem($subEntityId);

                return $project['name'] ?? $subEntityId;

            default:
                return $subEntityId;
        }
    }

    /**
     * Get sub-entity type options for UI.
     *
     * @return array List of sub-entity type options
     */
    public function getSubEntityTypeOptions(): array
    {
        return [
            ['id' => self::SUB_ENTITY_USER, 'name' => $this->translateService->getTranslate('User')],
            ['id' => self::SUB_ENTITY_PROJECT, 'name' => $this->translateService->getTranslate('Project')],
        ];
    }
}
