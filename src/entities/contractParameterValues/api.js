/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const fetchContractParameterValues = async (slug) =>
  await axios.post("/module/finances", {
    method: "getContractParameterValues",
    slug,
  });

export const saveContractParameterValue = async ({
  contract_id,
  contract_parameter_id,
  value,
}) =>
  await axios.post("/module/finances", {
    data: {
      contract_id,
      contract_parameter_id,
      value,
    },
    method: "saveContractParameterValue",
  });

export const deleteContractParameterValue = async (slug) =>
  await axios.post("/module/finances", {
    method: "deleteContractParameterValue",
    slug,
  });
