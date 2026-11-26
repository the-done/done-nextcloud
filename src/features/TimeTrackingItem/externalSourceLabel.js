/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

/**
 * Badge text for a record linked to an external system: the source name before the
 * first ":" (a source may carry a qualifier such as "name:import"), capitalized.
 *
 * @param {string|null|undefined} source
 * @return {string}
 */
export function externalSourceLabel(source) {
  const name = String(source || "").split(":")[0];

  return name ? name.charAt(0).toUpperCase() + name.slice(1) : "";
}
