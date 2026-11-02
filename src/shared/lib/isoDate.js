/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

/**
 * Formats a date as 'Y-m-d' using its local calendar day (no UTC shift).
 * A string is assumed to be already formatted and is returned as is.
 *
 * @param {Date|string|null} date
 * @returns {string}
 */
export function toIsoDate(date) {
  if (!date) {
    return '';
  }
  if (typeof date === 'string') {
    return date;
  }
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
    return '';
  }

  const year = String(date.getFullYear()).padStart(4, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

/**
 * Parses a 'Y-m-d' string into a Date at local midnight.
 *
 * @param {string} value
 * @returns {Date|null}
 */
export function fromIsoDate(value) {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
  if (!match) {
    return null;
  }

  return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
}
