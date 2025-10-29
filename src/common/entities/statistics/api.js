/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const fetchUserStatistics = async ({ date_from, date_to, projects }) => {
  const {
    data: { data, totals },
  } = await axios.post("/getUserStatistics", {
    date_from,
    date_to,
    projects,
  });

  return { data, totals };
};

export const fetchUserStatisticsBySlug = async ({
  date_from,
  date_to,
  slug,
  slug_type,
  projects,
}) => {
  const {
    data: { data, totals },
  } = await axios.post("/getStatisticsByUser", {
    date_from,
    date_to,
    slug,
    slug_type,
    projects,
  });

  return { data, totals };
};
