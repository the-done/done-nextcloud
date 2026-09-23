/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

export const state = () => {
  return {
    list: null,
    isOfficer: false,
    isFinance: false,
    isApprover: false,
    currentUserId: null,
    isFetched: false,
  };
};
