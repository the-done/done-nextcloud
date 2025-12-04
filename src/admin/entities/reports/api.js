/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

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
