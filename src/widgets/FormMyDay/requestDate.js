/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { toIsoDate } from "@/shared/lib/isoDate";

/**
 * The day to form suggestions for: the record date the user picked, or today when it is empty or invalid.
 *
 * @param {Date|string|null|undefined} value
 * @param {Date} [now]
 * @return {string} yyyy-mm-dd
 */
export function requestDate(value, now = new Date()) {
  return toIsoDate(value) || toIsoDate(now);
}
