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

export const transformSortFieldsForRest = (payload) =>
  payload.reduce(
    (accum, item, index) => ({
      ...accum,
      [item.key]: index,
    }),
    {}
  );

export const getFileNameFromResponse = (response) => {
  const contentDisposition = response.headers["content-disposition"];

  if (contentDisposition) {
    const matches = contentDisposition.match(
      /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/
    );

    if (matches && matches[1]) {
      return matches[1].replace(/['"]/g, "");
    }
  }

  return null;
};

export const capitalizeFirstLetter = (value) => {
  return String(value).charAt(0).toUpperCase() + String(value).slice(1);
};
