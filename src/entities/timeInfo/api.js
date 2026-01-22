/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const fetchUserTimeInfo = async (slug) => {
  const { data } = await axios.post("/getUserTimeInfo", {
    slug,
  });

  return { data };
};

export const createUserTimeInfo = async (data) => {
  const response = await axios.post("/saveUserTimeInfo", { data });

  return response;
};

export const updateUserTimeInfo = async (slug, data) => {
  const response = await axios.post("/editUserTimeInfo", {
    slug,
    data,
  });

  return response;
};

export const deleteUserTimeInfo = async ({ slug, slug_type }) => {
  const response = await axios.post("/deleteUserTimeInfo", { slug, slug_type });

  return response;
};

export const editReportSort = async ({ slug, sort }) => {
  const response = await axios.post("/editReportSort", { slug, sort });

  return response;
};

export const editReportSortMultiple = async (payload) => {
  const response = await axios.post("/editReportSortMultiple", { data: payload });

  return response;
};
