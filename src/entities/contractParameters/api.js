/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const fetchContractParameters = async () =>
  await axios.post("/module/finances", {
    method: "getContractParameters",
  });

export const fetchContractParametersTableData = async () =>
  await axios.post("/module/finances", {
    method: "getContractsParametersTableData",
  });

export const fetchContractParameterBySlug = async (slug) =>
  await axios.post("/module/finances", {
    method: "getContractParameter",
    slug,
  });

export const createContractParameter = async ({ name, type_id }) =>
  await axios.post("/module/finances", {
    data: { name, type_id },
    method: "addContractParameter",
  });

export const updateContractParameter = async ({ slug, data }) =>
  await axios.post("/module/finances", {
    slug,
    data,
    method: "editContractParameter",
  });

export const deleteContractParameterBySlug = async (slug) =>
  await axios.post("/module/finances", {
    method: "deleteContractParameter",
    slug,
  });
