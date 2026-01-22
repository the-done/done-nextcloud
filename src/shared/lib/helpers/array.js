/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

export const transformSortFieldsForRest = (payload) =>
  payload.reduce(
    (accum, item, index) => ({
      ...accum,
      [item.key]: index,
    }),
    {}
  );

export const sortAlphabeticallyByKey = (array, key) => {
  return array.sort((a, b) => {
    const { [key]: aKey } = a;
    const { [key]: bKey } = b;

    if (aKey < bKey) {
      return -1;
    }
    if (aKey > bKey) {
      return 1;
    }

    return 0;
  });
};
