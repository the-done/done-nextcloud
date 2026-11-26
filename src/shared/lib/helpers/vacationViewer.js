/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

// Exact route names permit calendar details without opening unrelated sections.
export const isVacationViewerRoute = (name) => [
  "vacations-requests",
  "vacations-schedule",
  "vacations-request-card",
  "error-404",
].includes(name);
