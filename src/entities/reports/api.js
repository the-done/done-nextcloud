/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

export const fetchMatrix = (params, signal) => axios.post(
  "/module/reports", { ...params, method: "getMatrix" }, { signal }
);

export const fetchMatrixEntries = (params, signal) => axios.post(
  "/module/reports", { ...params, method: "getMatrixEntries" }, { signal }
);

export const fetchMatrixFilters = (signal) => axios.post(
  "/module/reports", { method: "getMatrixFilters" }, { signal }
);

export const matrixExportUrl = (params, expanded) => {
  const query = new URLSearchParams(params);
  expanded.forEach((id) => query.append("expanded[]", id));
  return `${generateUrl("/apps/done/reports/table/export")}?${query}`;
};

export const fetchCommonStatistics = async ({
  date_from,
  date_to,
  projects,
  signal,
}) => {
  const { data } = await axios.post(
    "/module/reports",
    {
      date_from,
      date_to,
      projects,
      method: "getCommonStatistics",
    },
    {
      signal,
    }
  );

  return { data };
};

export const fetchProjectsStatistics = async ({
  date_from,
  date_to,
  slug,
  signal,
}) => {
  const {
    data: { data, totals },
  } = await axios.post(
    "/module/reports",
    {
      date_from,
      date_to,
      slug,
      method: "getProjectsStatistics",
    },
    {
      signal,
    }
  );

  return { data, totals };
};

export const fetchUsersCommonStatistics = async ({
  date_from,
  date_to,
  projects,
  teams,
  contract_types,
  directions,
  signal,
}) => {
  const { data } = await axios.post(
    "/module/reports",
    {
      date_from,
      date_to,
      projects,
      teams,
      contract_types,
      directions,
      method: "getUsersCommonStatistics",
    },
    {
      signal,
    }
  );

  return { data };
};
