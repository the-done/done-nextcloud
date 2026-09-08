/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

export const state = () => {
  return {
    list: null,
    demoList: [],
    // Whether the user hid demo modules in their settings (persisted in DB,
    // delivered by getAvailableModules). Kept in the store so toggling it
    // updates the navigation live.
    demoHidden: false,
    isAdmin: false,
    isFetched: false
  };
};
