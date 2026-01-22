/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { LOCALSTORAGE_TABLE_PREFIXES } from "@/shared/lib/constants/localStorage";

export const actions = {
  setTableState(source, newState) {
    const currentState = this.tableState[source];

    if (currentState) {
      const nextState = {
        ...currentState,
        ...newState,
      };

      this.tableState = {
        ...this.tableState,
        [source]: nextState,
      };

      return;
    }

    this.tableState = {
      ...this.tableState,
      [source]: {
        ...newState,
      },
    };
  },
  getTableLocalStorageName(source, key) {
    const prefix = LOCALSTORAGE_TABLE_PREFIXES[key];

    return `${prefix}_${source}`;
  },
  setTableLocalStorageData(source, key, payload) {
    try {
      const tableSettingsLocalStorageName = this.getTableLocalStorageName(
        source,
        key,
      );

      const tableLocalStorageSettings = localStorage.getItem(
        tableSettingsLocalStorageName,
      );

      if (tableLocalStorageSettings) {
        const parsed = JSON.parse(tableLocalStorageSettings);
        const nextValue = {
          ...parsed,
          ...payload,
        };

        localStorage.setItem(
          tableSettingsLocalStorageName,
          JSON.stringify(nextValue),
        );

        return;
      }

      localStorage.setItem(
        tableSettingsLocalStorageName,
        JSON.stringify(payload),
      );
    } catch (e) {
      console.log(e);
    }
  },
  setLocalStorageSettings(source, payload) {
    this.setTableLocalStorageData(source, "settings", payload);
  },
  setTableViewMode(source, viewMode = "table") {
    this.setTableState(source, { viewMode });
    this.setLocalStorageSettings(source, { viewMode });
  },
};
