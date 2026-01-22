/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const fetchContractsTableData = async () =>
  await axios.post("/module/finances", {
    method: "getContractsTableData",
  });

export const fetchContractBySlug = async ({ slug }) =>
  await axios.post("/module/finances", {
    method: "getContractData",
    slug,
  });

export const createContract = async (payload) =>
  await axios.post("/module/finances", {
    data: payload,
    method: "addContract",
  });

export const updateContract = async ({ slug, data }) =>
  await axios.post("/module/finances", {
    slug,
    data,
    method: "editContract",
  });

export const deleteContractBySlug = async ({ slug }) =>
  await axios.post("/module/finances", {
    method: "deleteContract",
    slug,
  });
