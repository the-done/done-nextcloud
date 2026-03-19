/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { isObject } from "@/shared/lib/helpers/validation";

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
  getInitArray(value) {
    return value && Array.isArray(value) ? value : [];
  },
  getInitObject(value) {
    return isObject(value) ? value : {};
  },
  initDynamicTableState({
    source,
    data,
    allColumnsOrdering,
    settings,
    options,
  }) {
    this.setTableState(source, { data: this.getInitArray(data) });
    this.setTableState(source, {
      allColumnsOrdering: this.getInitArray(allColumnsOrdering),
    });
    this.setTableState(source, { settings: this.getInitObject(settings) });
    this.setTableState(source, { options: this.getInitObject(options) });
  },
  getLocalStorageName(source, key) {
    const prefix = LOCALSTORAGE_TABLE_PREFIXES[key];

    return `${prefix}_${source}`;
  },
  setLocalStorageData(source, key, payload) {
    try {
      const tableSettingsLocalStorageName = this.getLocalStorageName(
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
      console.error(e);
    }
  },
  setLocalStorageSettings(source, payload) {
    this.setLocalStorageData(source, "settings", payload);
  },
  setAllColumnsOrdering(source, value) {
    this.setTableState(source, { allColumnsOrdering: value });
  },
  setViewMode(source, viewMode = "table") {
    this.setTableState(source, { viewMode });
    this.setLocalStorageSettings(source, { viewMode });
  },
  setLoading(source, value) {
    this.setTableState(source, { isLoading: value });
  },
};
