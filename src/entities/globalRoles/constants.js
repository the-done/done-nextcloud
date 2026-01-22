/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

const GLOBAL_ROLES = {
  admin: { id: 1, name: "Administrator" },
  officer: { id: 2, name: "Director" },
  head: { id: 3, name: "Manager" },
  curator: { id: 4, name: "Curator" },
  staff: { id: 5, name: "Employee" },
};

export const MAP_GLOBAL_ROLES_TO_PERMISSION = {
  [GLOBAL_ROLES.admin.id]: "canAddAdmin",
  [GLOBAL_ROLES.officer.id]: "canAddOfficer",
  [GLOBAL_ROLES.head.id]: "canAddHead",
  [GLOBAL_ROLES.curator.id]: "canAddCurator",
  [GLOBAL_ROLES.staff.id]: null,
};
