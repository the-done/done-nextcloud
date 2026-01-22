/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

export const isEmptyValue = (value) => {
  return (
    ["", null, undefined, "{}"].includes(value) === true ||
    (Array.isArray(value) === true && value.length === 0) ||
    (typeof value === "object" &&
      !Array.isArray(value) &&
      Object.keys(value).length === 0)
  );
};
