/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */
import { ru } from "date-fns/locale";
import { getJoinString } from "./string";

export const localizeDateFns = (func, ...props) => {
  return func(...props, { locale: ru });
};

export const transformDotsToDashDate = (value) => {
  const array = value.split(".");

  return getJoinString(array.reverse(), "-");
};

export const transformDashToDotsDate = (value) => {
  const array = value.split("-");

  return getJoinString(array, ".");
};
