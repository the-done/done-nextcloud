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
} from "date-fns";

import { SUBMIT_DATE_FORMAT } from "@/common/shared/lib/constants";

export const timeTrackingPageMixin = {
  data: () => ({
    activeRangeType: "month",
    activeDate: new Date(),
  }),
  computed: {
    activeFilter() {
      return this.getSerializedFilters();
    },
  },
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
        case "year": {
          const dateFrom = startOfYear(this.activeDate);
          const dateTo = endOfYear(this.activeDate);

          return {
            date_from: format(dateFrom, SUBMIT_DATE_FORMAT),
            date_to: format(dateTo, SUBMIT_DATE_FORMAT),
          };
        }
        case "quarter": {
          const dateFrom = startOfQuarter(this.activeDate);
          const dateTo = endOfQuarter(this.activeDate);

          return {
            date_from: format(dateFrom, SUBMIT_DATE_FORMAT),
            date_to: format(dateTo, SUBMIT_DATE_FORMAT),
          };
        }
        case "month": {
          const dateFrom = startOfMonth(this.activeDate);
          const dateTo = endOfMonth(this.activeDate);

          return {
            date_from: format(dateFrom, SUBMIT_DATE_FORMAT),
            date_to: format(dateTo, SUBMIT_DATE_FORMAT),
          };
        }
        case "week": {
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
    async fetchDataWithFilters(payload = {}) {
      const { date_from, date_to } = this.getSubmitRange();
      const filters = this.getSerializedFilters();

      await this.handleFetchData({ date_from, date_to, filters, ...payload });
    },
    async handleUpdateActiveDate(value) {
      this.activeDate = value;

      await this.fetchDataWithFilters();
    },
    async handleUpdateActiveRangeType(value) {
      this.activeRangeType = value;

      this.saveLocalStorageActiveRangeType();

      await this.fetchDataWithFilters();
    },
    setFilterQuery() {
      const query = this.$route.query;
      const filters = this.getSerializedFilters();

      this.$router.push({
        query: { ...query, ...filters },
      });
    },
    async handleUpdateFilter() {
      this.setFilterQuery();

      await this.fetchDataWithFilters();
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
    async initFetchDataWithFilters(payload) {
      this.setFilterValuesFromQuery();

      await this.fetchDataWithFilters(payload);
    },
  },
};
