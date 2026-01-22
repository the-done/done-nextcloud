/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

export const getters = {
  getTableBySource: (state) => (source) => state?.tableState[source],
  filterConditionsFetched: (state) => state?.conditions?.isFetched || false,
  filterConditionsList: (state) => state?.conditions?.list || null,
};
