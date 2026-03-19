/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { mapState, mapActions } from "pinia";

import { useDynamicTableStore } from "@/app/store/dynamicTable";

export const dynamicTableMixin = {
  computed: {
    ...mapState(useDynamicTableStore, ["getTableBySource"]),
    tableState() {
      return this.getTableBySource(this.source);
    },
  },
  methods: {
    ...mapActions(useDynamicTableStore, [
      "initDynamicTableState",
      "setLoading",
      "setViewMode",
      "getLocalStorageName",
    ]),
    setTableLoading(value) {
      this.setLoading(this.source, value);
    },
    setTableViewMode(value) {
      this.setViewMode(this.source, value);
    },
    initViewMode() {
      try {
        if (!this.source || this.tableState?.viewMode) {
          return;
        }

        const localStorageSettingsName = this.getLocalStorageName(
          this.source,
          "settings",
        );

        const localStorageSettings = localStorage.getItem(
          localStorageSettingsName,
        );

        if (!localStorageSettings) {
          this.setTableViewMode("table");

          return;
        }

        const parsedSettings = JSON.parse(localStorageSettings);

        if (parsedSettings.viewMode) {
          this.setTableViewMode(parsedSettings.viewMode);

          return;
        }

        this.setTableViewMode("table");
      } catch (e) {
        console.error(e);
      }
    },
    initDynamicTable({ allColumnsOrdering, data, settings }) {
      const defaultOptions = {
        controls: true,
      };

      const options = this.tableOptions
        ? {
            ...defaultOptions,
            ...this.tableOptions,
          }
        : defaultOptions;

      this.initDynamicTableState({
        source: this.source,
        allColumnsOrdering,
        data,
        settings,
        options,
      });

      this.initViewMode();
    },
  },
};
