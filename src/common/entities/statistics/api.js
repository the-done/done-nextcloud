/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const fetchUserStatistics = async ({
  date_from,
  date_to,
  projects,
  signal,
}) => {
  const {
    data: { data, totals, firstReportDate },
  } = await axios.post(
    "/getUserStatistics",
    {
      date_from,
      date_to,
      projects,
    },
    {
      signal,
    }
  );

  return { data, totals, firstReportDate };
};

export const fetchUserStatisticsBySlug = async ({
  date_from,
  date_to,
  slug,
  slug_type,
  projects,
  signal,
}) => {
  const {
    data: { data, totals },
  } = await axios.post(
    "/getStatisticsByUser",
    {
      date_from,
      date_to,
      slug,
      slug_type,
      projects,
    },
    {
      signal,
    }
  );

  return { data, totals };
};
