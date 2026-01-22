/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */
import { format } from "date-fns";

export const redirectToUserStatistics = ({
  slug,
  activeDate,
  activeRangeType,
  $router,
}) => {
  const date = format(activeDate, "yyyy-MM-dd");

  $router.push({
    name: "staff-statistics",
    params: { slug },
    query: {
      activeDate: date,
      activeRangeType,
    },
  });
};

export const redirectNotFoundPage = ($router) =>
  $router.push({ name: "error-404" });
