/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

export const actions = {
  toggleAiChat() {
    this.isChatActive = !this.isChatActive;
  },
  toggleAiChatSidebarMode() {
    this.isSidebarMode = !this.isSidebarMode;
  },
  setHealtCheck(value) {
    this.isHealthChecked = value;
  },
  setToken(value) {
    this.token = value;
  },
};
