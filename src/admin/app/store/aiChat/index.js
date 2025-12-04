/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { defineStore } from "pinia";

import { state } from "./state";
import { getters } from "./getters";
import { actions } from "./actions";

export const useAiChatStore = defineStore("aiChat", {
  state,
  getters,
  actions,
});
