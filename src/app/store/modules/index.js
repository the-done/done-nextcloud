/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { defineStore } from "pinia";

import { state } from "./state";
import { getters } from "./getters";

export const useModulesStore = defineStore("modules", {
  state,
  getters,
  actions: {
    // Live update of the navigation after the user toggles the "hide demo
    // modules" setting. The value itself is persisted in the DB via the
    // custom-settings save flow; here we only mirror it into the store.
    setDemoHidden(hidden) {
      this.demoHidden = hidden === true;
    },
  },
});