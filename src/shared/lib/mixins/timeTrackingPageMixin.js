/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import {
  format,
  startOfYear,
  endOfYear,
  startOfQuarter,
  endOfQuarter,
  startOfMonth,
  endOfMonth,
  isMonday,
  isSunday,
  previousMonday,
  nextSunday,
  isBefore,
} from "date-fns";

import { SUBMIT_DATE_FORMAT } from "@/shared/lib/constants/date";

const ACTIVE_RANGE_TYPES = {
  year: "year",
  quarter: "quarter",
  month: "month",
  week: "week",
};

export const timeTrackingPageMixin = {
  data: () => ({
    activeRangeType: "month",
    activeDate: new Date(),
  }),
  methods: {
    getSerializedFilters() {
      const result = this.filterDescriptor.reduce((accum, item) => {
        const { key, value, multiple } = item;

        if (multiple === true) {
          if (!value || value.length === 0) {
            return {
              ...accum,
              [key]: undefined,
            };
          }

          return {
            ...accum,
            [key]: value.map((item) =>
              typeof item === "object" ? item.id : item
            ),
          };
        }

        if (!value) {
          return {
            ...accum,
            [key]: undefined,
          };
        }

        return {
          ...accum,
          [key]: typeof value === "object" ? value.id : value,
        };
      }, {});

      return result;
    },
    getSubmitRange() {
      switch (this.activeRangeType) {
        case ACTIVE_RANGE_TYPES["year"]: {
          const dateFrom = startOfYear(this.activeDate);
          const dateTo = endOfYear(this.activeDate);

          return {
            date_from: format(dateFrom, SUBMIT_DATE_FORMAT),
            date_to: format(dateTo, SUBMIT_DATE_FORMAT),
          };
        }
        case ACTIVE_RANGE_TYPES["quarter"]: {
          const dateFrom = startOfQuarter(this.activeDate);
          const dateTo = endOfQuarter(this.activeDate);

          return {
            date_from: format(dateFrom, SUBMIT_DATE_FORMAT),
            date_to: format(dateTo, SUBMIT_DATE_FORMAT),
          };
        }
        case ACTIVE_RANGE_TYPES["month"]: {
          const dateFrom = startOfMonth(this.activeDate);
          const dateTo = endOfMonth(this.activeDate);

          return {
            date_from: format(dateFrom, SUBMIT_DATE_FORMAT),
            date_to: format(dateTo, SUBMIT_DATE_FORMAT),
          };
        }
        case ACTIVE_RANGE_TYPES["week"]: {
          const dateFrom = isMonday(this.activeDate)
            ? this.activeDate
            : previousMonday(this.activeDate);
          const dateTo = isSunday(this.activeDate)
            ? this.activeDate
            : nextSunday(this.activeDate);

          return {
            date_from: format(dateFrom, SUBMIT_DATE_FORMAT),
            date_to: format(dateTo, SUBMIT_DATE_FORMAT),
          };
        }
        default: {
          const dateFrom = new Date();
          const dateTo = new Date();

          return {
            date_from: format(dateFrom, SUBMIT_DATE_FORMAT),
            date_to: format(dateTo, SUBMIT_DATE_FORMAT),
          };
        }
      }
    },
    saveLocalStorageActiveDate() {
      const activeDateKey = this.localStorageActiveDateKey;

      if (!activeDateKey) {
        return;
      }

      localStorage.setItem(activeDateKey, this.activeDate);
    },
    saveLocalStorageActiveRangeType() {
      const activeRangeTypeKey = this.localStorageActiveRangeTypeKey;

      if (activeRangeTypeKey) {
        localStorage.setItem(activeRangeTypeKey, this.activeRangeType);
      }
    },
    setActiveRangeTypeFromLocalStorage() {
      const activeRangeTypeKey = this.localStorageActiveRangeTypeKey;

      if (!activeRangeTypeKey) {
        return;
      }

      const localActiveRangeType = localStorage.getItem(activeRangeTypeKey);

      if (!localActiveRangeType) {
        return;
      }

      this.activeRangeType = localActiveRangeType;
    },
    setFilterQuery() {
      const query = this.$route.query;
      const filters = this.getSerializedFilters();

      this.$router.push({
        query: {
          ...query,
          ...filters,
          active_range_type: this.activeRangeType,
          active_date: format(this.activeDate, "yyyy-MM-dd"),
        },
      });
    },
    getQueryFilterValues() {
      const query = this.$route.query;

      return this.filterDescriptor.reduce((accum, item) => {
        const { key, multiple } = item;
        const value = query[key];

        if (!value) {
          return accum;
        }

        if (multiple === true) {
          if (Array.isArray(value) === true) {
            return {
              ...accum,
              [key]: value,
            };
          } else {
            return {
              ...accum,
              [key]: [value],
            };
          }
        } else {
          return {
            ...accum,
            [key]: value,
          };
        }
      }, {});
    },
    setDateFromQuery() {
      const { active_date, active_range_type } = this.$route.query;
      const activeRangeTypes = Object.values(ACTIVE_RANGE_TYPES);

      if (active_date) {
        try {
          this.activeDate = new Date(active_date);
        } catch (e) {
          console.error(e);
        }
      }

      if (active_range_type && activeRangeTypes.includes(active_range_type)) {
        this.activeRangeType = active_range_type;
      }
    },
    setFilterValuesFromQuery() {
      const filters = this.getQueryFilterValues();

      this.filterDescriptor.forEach((item) => {
        const { key, multiple } = item;
        const value = filters[key];

        if (value) {
          if (multiple === true) {
            if (Array.isArray(value) === true) {
              item.value = value;
            } else {
              item.value = [value];
            }
          } else {
            item.value = value;
          }
        }
      });
    },
    async fetchDataWithFilters(payload = {}) {
      const { date_from, date_to } = this.getSubmitRange();
      const filters = this.getSerializedFilters();

      await this.handleFetchData({ date_from, date_to, filters, ...payload });
    },
    async handleUpdateActiveDate(value) {
      if (this.limitFirstDate && isBefore(value, this.limitFirstDate)) {
        this.activeDate = this.limitFirstDate;
      } else {
        this.activeDate = value;
      }

      this.setFilterQuery();

      await this.fetchDataWithFilters();
    },
    async handleUpdateActiveRangeType(value) {
      this.activeRangeType = value;

      this.saveLocalStorageActiveRangeType();

      this.setFilterQuery();

      await this.fetchDataWithFilters();
    },
    async handleUpdateFilter() {
      this.setFilterQuery();

      await this.fetchDataWithFilters();
    },
    async initFetchDataWithFilters(payload) {
      this.setDateFromQuery();
      this.setFilterValuesFromQuery();

      await this.fetchDataWithFilters(payload);
    },
    async init() {
      try {
        const query = this.$route.query;

        if (!query.active_range_type) {
          this.setActiveRangeTypeFromLocalStorage();
        }

        await this.initFetchDataWithFilters();
      } catch (e) {
        console.error(e);
      } finally {
        this.isInitLoading = false;
      }
    },
  },
};
