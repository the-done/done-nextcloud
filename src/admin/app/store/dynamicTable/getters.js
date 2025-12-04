/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

export const getters = {
  filterConditionsFetched: (state) => state?.conditions?.isFetched || false,
  filterConditionsList: (state) => state?.conditions?.list || null,
};
