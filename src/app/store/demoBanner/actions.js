/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

// How long the centered demo banner stays on screen before auto-hiding.
const AUTO_HIDE_MS = 6 * 1000;

// Kept outside the store state so the timer id never leaks into reactive data.
let hideTimer = null;

export const actions = {
  // Show the centered banner explaining that the attempted action needs the
  // full (Pro) version. Called from the axios interceptor when the backend
  // answers a demo write with a read-only marker.
  showDemoNotice(message) {
    this.message = message || "";
    this.visible = true;

    if (hideTimer !== null) {
      clearTimeout(hideTimer);
    }

    hideTimer = setTimeout(() => {
      this.visible = false;
      hideTimer = null;
    }, AUTO_HIDE_MS);
  },
  hideDemoNotice() {
    this.visible = false;

    if (hideTimer !== null) {
      clearTimeout(hideTimer);
      hideTimer = null;
    }
  },
};