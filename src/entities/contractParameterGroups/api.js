/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const fetchContractParameterGroups = async () =>
  await axios.post("/module/finances", {
    method: "getContractParameterGroups",
  });

export const fetchContractParameterGroupsTableData = async () =>
  await axios.post("/module/finances", {
    method: "getContractParameterGroupsTableData",
  });

export const fetchContractParameterGroupBySlug = async (slug) =>
  await axios.post("/module/finances", {
    method: "getContractParameterGroup",
    slug,
  });

export const createContractParameterGroup = async ({ name, parameters }) =>
  await axios.post("/module/finances", {
    data: { name, parameters },
    method: "addContractParameterGroup",
  });

export const updateContractParameterGroup = async ({ slug, data }) =>
  await axios.post("/module/finances", {
    slug,
    data,
    method: "editContractParameterGroup",
  });

export const deleteContractParameterGroupBySlug = async (slug) =>
  await axios.post("/module/finances", {
    method: "deleteContractParameterGroup",
    slug,
  });

export const assignParameterToGroup = async ({ group_id, parameter_id }) =>
  await axios.post("/module/finances", {
    method: "assignParameterToGroup",
    group_id,
    parameter_id,
  });

export const removeParameterFromGroup = async ({ group_id, parameter_id }) =>
  await axios.post("/module/finances", {
    method: "removeParameterFromGroup",
    group_id,
    parameter_id,
  });

export const fetchGroupParameters = async (group_id) =>
  await axios.post("/module/finances", {
    method: "getGroupParameters",
    group_id,
  });
