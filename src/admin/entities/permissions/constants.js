/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { MAP_ENTITY_SOURCE } from "@/common/shared/lib/constants";

export const MAP_ACTION_TITLE = {
  can_view: "View",
  can_read: "Reading",
  can_write: "Editing",
  can_delete: "Deleting",
  can_view_add_info: "Additional information",
};

export const GLOBAL_ROLE_PERMISSIONS_NAVIGATION = [
  {
    key: "global-role-permissions-user",
    label: "User card",
    to: {
      name: "global-role-permissions-edit",
      params: {
        source: MAP_ENTITY_SOURCE["user"],
      },
    },
    exact: false,
  },
  {
    key: "global-role-permissions-project",
    label: "Project card",
    to: {
      name: "global-role-permissions-edit",
      params: {
        source: MAP_ENTITY_SOURCE["project"],
      },
    },
    exact: false,
  },
];
