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

// JSON POST keeps large selections/expanded rows out of the URL and avoids form input limits.
export const exportMatrix = async (params, expanded) => {
  const response = await axios.post(
    generateUrl("/apps/done/reports/table/export"), { ...params, expanded },
    // The app sets axios.baseURL to /apps/done/ajax; this core route lives outside it.
    { baseURL: "", responseType: "blob", timeout: 60000 }
  );
  // OCS authorization failures can use HTTP 200; never download an error envelope as a report.
  const types = { html: "text/html", pdf: "application/pdf",
    xlsx: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" };
  if (response.data.type.split(";")[0] !== types[params.format]
    || !response.headers["content-disposition"]?.includes("attachment")) {
    throw new Error("Invalid report attachment");
  }
  return response;
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
