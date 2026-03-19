/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const getGeneratedReports = async ({ date }) => {
  const { data } = await axios.post("/module/integrationwithextapps", {
    method: "getGeneratedReports",
    date,
  });

  return data;
};
