/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const fetchUsers = async () => {
  const { data } = await axios.post("/getUsersData");

  return { data };
};

export const fetchUsersTableData = async ({ need_deleted = false } = {}) => {
  const { data } = await axios.post("/getUsersTableData", { need_deleted });

  return { data };
};

export const fetchUserPermissions = async () => {
  const { data } = await axios.post("/getPermissions");

  return data;
};

export const fetchAvailableModules = async () => {
  const { data } = await axios.post("/getAvailableModules");

  return { data };
};

export const fetchUserProjects = async () => {
  const { data } = await axios.post("/getUserProjects");

  return { data };
};

export const fetchUserProjectsForReport = async () => {
  const { data } = await axios.post("/getUserProjectsForReport");

  return { data };
};

export const fetchUserBySlug = async ({ slug, slug_type }) => {
  const { data } = await axios.post("/getUsersData", { slug, slug_type });

  return { data };
};

export const fetchUserPublicDataBySlug = async ({ slug, slug_type }) => {
  const { data } = await axios.post("/getUserPublicData", { slug, slug_type });

  return { data };
};

export const fetchUsersForProject = async ({ slug, slug_type }) => {
  const { data } = await axios.post("/getUsersForProject", { slug, slug_type });

  return { data };
};

export const fetchUserDirections = async ({ slug, slug_type }) => {
  const { data } = await axios.post("/getUserInDirection", { slug, slug_type });

  return { data };
};

export const addUserToDirection = async ({ user, direction }) => {
  const { data } = await axios.post("/addUserToDirection", { user, direction });

  return { data };
};

export const deleteUserFromDirection = async ({ slug }) => {
  const { data } = await axios.post("/deleteUserFromDirection", { slug });

  return { data };
};

export const createUser = async (payload) => {
  const response = await axios.post("/saveUser", { data: payload });

  return response;
};

export const updateUser = async ({ slug, slug_type, data }) => {
  const response = await axios.post("/saveUser", {
    slug,
    slug_type,
    data,
  });

  return response;
};

export const deleteUser = async ({ slug, slug_type }) => {
  const response = await axios.post("/deleteUser", { slug, slug_type });

  return response;
};

export const restoreUser = async ({ slug }) => {
  const response = await axios.post("/restoreUser", { slug });

  return response;
};
