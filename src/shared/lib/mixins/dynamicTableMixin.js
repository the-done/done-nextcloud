/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { mapState, mapActions } from "pinia";

import { useDynamicTableStore } from "@/app/store/dynamicTable";

export const dynamicTableMixin = {
  data() {
    return {
      tableIsLoading: false,
      allColumnsOrdering: [],
      tableData: [],
      settings: {},
    };
  },
  computed: {
    ...mapState(useDynamicTableStore, ["getTableBySource"]),
    tableStoreData() {
      return this.getTableBySource(this.source);
    },
  },
  methods: {
    ...mapActions(useDynamicTableStore, [
      "setTableViewMode",
      "getTableLocalStorageName",
    ]),
    initViewMode() {
      try {
        if (!this.source || this.tableStoreData?.viewMode) {
          return;
        }

        const localStorageSettingsName = this.getTableLocalStorageName(
          this.source,
          "settings",
        );

        const localStorageSettings = localStorage.getItem(
          localStorageSettingsName,
        );

        if (!localStorageSettings) {
          this.setTableViewMode(this.source, "table");

          return;
        }

        const parsedSettings = JSON.parse(localStorageSettings);

        if (parsedSettings.viewMode) {
          this.setTableViewMode(this.source, parsedSettings.viewMode);

          return;
        }

        this.setTableViewMode(this.source, "table");
      } catch (e) {
        console.log(e);
      }
    },
    initDynamicTable({ allColumnsOrdering, data, settings }) {
      this.allColumnsOrdering = allColumnsOrdering;
      this.tableData = data;
      this.settings = settings;

      this.initViewMode();
    },
  },
};
