/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

export const capitalizeFirstLetter = (value) => {
  return String(value).charAt(0).toUpperCase() + String(value).slice(1);
};

export const getJoinString = (array = [], separator = " ") => {
  const result = array.filter((item) => Boolean(item));

  return result.join(separator);
};

export const declOfNum = (number, titles) => {
  const cases = [2, 0, 1, 1, 1, 2];

  return titles[
    number % 100 > 4 && number % 100 < 20
      ? 2
      : cases[number % 10 < 5 ? number % 10 : 5]
  ];
};
