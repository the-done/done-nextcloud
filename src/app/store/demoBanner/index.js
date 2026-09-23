/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { defineStore } from "pinia";

import { state } from "./state";
import { actions } from "./actions";

export const useDemoBannerStore = defineStore("demoBanner", {
  state,
  actions,
});