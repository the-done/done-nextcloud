<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Modules\Agreement\Model;

use OCA\Done\Models\BaseModel;
use OCA\Done\Models\Dictionaries\GlobalRolesModel;
use OCA\Done\Models\UserModel;
use OCA\Done\Models\UsersGlobalRolesModel;
use OCA\Done\Service\BaseService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class AgreementSchemeApproversModel.
 *
 * Approvers assigned to agreement scheme lines (can be users or roles).
 */
class AgreementSchemeApproversModel extends BaseModel
{
    public string $table = 'done_agr_sch_approvers';
    public string $modelTitle = 'Agreement Scheme Approvers';
    public string $modelName = 'agreementSchemeApprovers';
    public string $dbTableComment = 'Agreement scheme approvers: users or roles assigned to scheme lines.';

    protected array $hashFields = [
        'line_id',
        'approver_type',
        'user_id',
        'role_id',
    ];

    public array $fields = [
        'id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'required'   => true,
            'db_comment' => 'Unique identifier for an approver assignment',
        ],
        'line_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Line',
            'required'   => true,
            'link'       => AgreementSchemeLinesModel::class,
            'db_comment' => 'Parent line ID. References oc_done_agr_scheme_lines.id',
        ],
        'approver_type' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Approver type',
            'required'   => true,
            'db_comment' => 'Type of approver: "user" or "role"',
        ],
        'user_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'User',
            'required'   => false,
            'link'       => UserModel::class,
            'db_comment' => 'User ID if approver_type is "user". References oc_done_users_data.id',
        ],
        'role_id' => [
            'type'       => IQueryBuilder::PARAM_INT,
            'title'      => 'Role',
            'required'   => false,
            'link'       => GlobalRolesModel::class,
            'db_comment' => 'Role ID if approver_type is "role". References oc_done_global_roles.id',
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

    // Approver types
    public const TYPE_USER = 'user';
    public const TYPE_ROLE = 'role';

    /**
     * Get approver type options for frontend.
     */
    public function getApproverTypeOptions(): array
    {
        return [
            ['id' => self::TYPE_USER, 'name' => $this->translateService->getTranslate('User')],
            ['id' => self::TYPE_ROLE, 'name' => $this->translateService->getTranslate('Role')],
        ];
    }

    /**
     * Get approvers for a line.
     */
    public function getApproversForLine(string $lineId): array
    {
        return $this->getListByFilter(['line_id' => $lineId]);
    }

    /**
     * Add user approver to a line.
     */
    public function addUserApprover(string $lineId, string $userId): ?string
    {
        // Check if already exists
        $existing = $this->getItemByFilter([
            'line_id'       => $lineId,
            'approver_type' => self::TYPE_USER,
            'user_id'       => $userId,
        ]);

        if (!empty($existing)) {
            return null;
        }

        return $this->addData([
            'line_id'       => $lineId,
            'approver_type' => self::TYPE_USER,
            'user_id'       => $userId,
        ]);
    }

    /**
     * Add role approver to a line.
     */
    public function addRoleApprover(string $lineId, int $roleId): ?string
    {
        // Check if already exists
        $existing = $this->getItemByFilter([
            'line_id'       => $lineId,
            'approver_type' => self::TYPE_ROLE,
            'role_id'       => $roleId,
        ]);

        if (!empty($existing)) {
            return null;
        }

        return $this->addData([
            'line_id'       => $lineId,
            'approver_type' => self::TYPE_ROLE,
            'role_id'       => $roleId,
        ]);
    }

    /**
     * Get all user IDs who can approve for a line (resolves roles to users).
     */
    public function getApproverUserIds(string $lineId): array
    {
        $approvers = $this->getApproversForLine($lineId);
        $userIds = [];

        $userModel = new UserModel();

        foreach ($approvers as $approver) {
            if ($approver['approver_type'] === self::TYPE_USER) {
                $userIds[] = $approver['user_id'];
            } elseif ($approver['approver_type'] === self::TYPE_ROLE) {
                // Get all users with this role
                $usersWithRole = $userModel->getListByFilter([
                    'global_role_id' => $approver['role_id'],
                ], ['id']);

                foreach ($usersWithRole as $user) {
                    $userIds[] = $user['id'];
                }
            }
        }

        return array_unique($userIds);
    }

    /**
     * Get all user IDs who can approve for a lines
     */
    public function getApproverUserIdsByLines(array $linesIds = []): array
    {
        if (empty($linesIds)) {
            return [];
        }

        $usersGlobalRolesModel = new UsersGlobalRolesModel();

        $approversWithTypeUser = $this->getListByFilter(
            [
                'line_id'       => ['IN', $linesIds, IQueryBuilder::PARAM_STR_ARRAY],
                'approver_type' => self::TYPE_USER,
            ]
        );

        $approversWithTypeRole = $this->getListByFilter(
            [
                'line_id'       => ['IN', $linesIds, IQueryBuilder::PARAM_STR_ARRAY],
                'approver_type' => self::TYPE_ROLE,
            ]
        );

        $rolesIds = BaseService::getField($approversWithTypeRole, 'role_id', true);
        // Group users by role_id (group = true): a role can have many users, so a
        // plain index would keep only one user per role.
        $usersByRole = !empty($rolesIds) ? BaseService::makeHash(
            $usersGlobalRolesModel->getListByFilter(
                ['role_id' => ['IN', $rolesIds, IQueryBuilder::PARAM_STR_ARRAY]]
            ),
            'role_id',
            true
        ) : [];

        $userIds = [];

        foreach ($approversWithTypeUser as $approver) {
            $userIds[$approver['line_id']][$approver['user_id']] = $approver['user_id'];
        }

        foreach ($approversWithTypeRole as $approver) {
            $usersWithRole = $usersByRole[$approver['role_id']] ?? [];

            foreach ($usersWithRole as $user) {
                $userIds[$approver['line_id']][$user['user_id']] = $user['user_id'];
            }
        }

        return $userIds;
    }

    public function isApprover(string $userId): bool
    {
        $userGlobalRoles = $this->userService->getUserGlobalRoles($userId);

        $isUserApprover = !empty($this->getItemByFilter(['user_id' => $userId, 'approver_type' => self::TYPE_USER]));
        $isRoleApprover = !empty($this->getItemByFilter(
            [
                'role_id'       => ['IN', $userGlobalRoles, IQueryBuilder::PARAM_STR_ARRAY],
                'approver_type' => self::TYPE_ROLE,
            ]
        )
        );

        return $isUserApprover || $isRoleApprover;
    }
}
