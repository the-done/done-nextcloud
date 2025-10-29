/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { defineStore } from "pinia";

import { state } from "./state";
import { getters } from "./getters";

export const useDynamicTableStore = defineStore("dynamicTable", {
  state,
  getters,
});
