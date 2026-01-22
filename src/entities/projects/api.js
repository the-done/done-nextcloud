/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const fetchProjects = async () => {
  const { data } = await axios.post("/module/projects" ,{
    method: 'getProjectsData'
  });

  return { data };
};

export const fetchProjectsTableData = async () => {
  const { data } = await axios.post("/module/projects", {
    method: 'getProjectsTableData'
  });

  return { data };
};

export const fetchProjectBySlug = async ({ slug, slug_type }) => {
  const { data } = await axios.post("/module/projects", {
    slug,
    slug_type,
    method: 'getProjectsData'
  });

  return { data };
};

export const fetchProjectPublicDataBySlug = async ({ slug, slug_type }) => {
  const { data } = await axios.post("/module/projects", {
    slug,
    slug_type,
    method: 'getProjectPublicData'
  });

  return { data };
};

export const createProject = async (payload) => {
  const { data } = await axios.post("/module/projects", {
    data: payload,
    method: 'saveProject'
  });

  return data;
};

export const updateProject = async ({ slug, slug_type, data }) => {
  const response = await axios.post("/module/projects", {
    slug,
    slug_type,
    data,
    method: 'saveProject'
  });

  return response;
};

export const deleteProject = async ({ slug, slug_type }) => {
  const response = await axios.post("/module/projects", {
    slug,
    slug_type,
    method: 'deleteProject'
  });

  return response;
};

export const getUsersInProject = async ({ slug, slug_type }) => {
  const { data } = await axios.post("/module/projects", {
    slug,
    slug_type,
    method: 'getUserRolesInProject'
  });

  return { data };
};

export const assignUserToProject = async ({ project, user, role }) => {
  const { data } = await axios.post("/module/projects", {
    project,
    user,
    role,
    method: 'saveUserRole'
  });

  return { data };
};

export const removeUserFromProject = async ({ slug }) => {
  const { data } = await axios.post("/module/projects", {
    slug,
    method: 'deleteUsersRoles'
  });

  return { data };
};

export const editUserRoleInProject = async ({ slug, user, project, role }) => {
  const { data } = await axios.post("/module/projects", {
    slug,
    user,
    project,
    role,
    method: 'editUserRoleInProject'
  });

  return { data };
};
