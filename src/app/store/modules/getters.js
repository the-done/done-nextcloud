/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

export const getters = {
  checkModule: (state) => (module) => {
    const { list } = state;

    if (!list) {
      return undefined;
    }

    // When demo mode is hidden in user settings, demo modules are treated as
    // absent so their navigation items disappear entirely.
    if (state.demoHidden === true && (state.demoList || []).includes(module)) {
      return false;
    }

    return list.includes(module);
  },
  moduleExist() {
    return (key) => {
      return this.checkModule(key);
    };
  },
  moduleIsDemo() {
    return (key) => {
      if (this.demoHidden === true) {
        return false;
      }

      return (this.demoList || []).includes(key);
    };
  },
  moduleIsReal() {
    return (key) => {
      return this.checkModule(key) === true && !(this.demoList || []).includes(key);
    };
  },
};