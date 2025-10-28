<?php

declare(strict_types=1);

namespace OCA\Done\Attribute;

use Attribute;

/**
 * Attribute for checking required user roles
 * 
 * Usage:
 * #[RequireRole(GlobalRoles_Model::OFFICER)]
 * #[RequireRole([GlobalRoles_Model::OFFICER, GlobalRoles_Model::HEAD])]
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class RequireRole
{
    /**
     * @param int|int[] $roles Required roles (single role or array of roles)
     */
    public function __construct(
        public readonly int|array $roles
    ) {
    }

    /**
     * Get array of required roles
     */
    public function getRequiredRoles(): array
    {
        if (is_array($this->roles)) {
            return $this->roles;
        }
        return [$this->roles];
    }
}
