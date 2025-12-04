/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const fetchGlobalRolesPermissions = async ({ source }) => {
  const { data } = await axios.post("/getGlobalRolesPermissions", { source });

  return { data };
};

export const saveGlobalRolesPermissions = async ({
  role,
  entity,
  field,
  can_view,
  can_read,
  can_write,
  can_delete,
  can_view_add_info,
}) => {
  const { data } = await axios.post("/saveGlobalRolesPermissions", {
    role,
    entity,
    field,
    can_view,
    can_read,
    can_write,
    can_delete,
    can_view_add_info,
  });

  return { data };
};

export const editGlobalRolesPermissions = async ({
  slug,
  can_view,
  can_read,
  can_write,
  can_delete,
  can_view_add_info,
}) => {
  const { data } = await axios.post("/editGlobalRolesPermissions", {
    slug,
    can_view,
    can_read,
    can_write,
    can_delete,
    can_view_add_info,
  });

  return { data };
};
