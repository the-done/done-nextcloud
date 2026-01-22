/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

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
