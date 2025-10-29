/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

export const initFilterDescriptor = (descriptor) => {
  return descriptor.map((item) => {
    if (item.fetchOptionsFunction !== undefined) {
      return {
        ...item,
        initiated: false,
      };
    }

    return item;
  });
};
