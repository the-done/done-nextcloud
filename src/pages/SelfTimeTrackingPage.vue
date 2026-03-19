/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage class="overflow-hidden relative">
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('Time tracking', context)"
          :to="{ name: 'time-tracking-new' }"
          forceIconText
        >
          <template #icon>
            <ClockTimeEightOutline />
          </template>
        </NcBreadcrumb>
      </NcBreadcrumbs>
      <template #actions>
        <NcButton
          :aria-label="contextTranslate('Add record', context)"
          @click="handleClickCreate"
        >
          <template #icon>
            <Plus />
          </template>
          {{ contextTranslate("Add", context) }}
        </NcButton>
      </template>
    </VToolbar>
    <VPageLayout>
      <TimeTrackingAside
        :active-date="activeDate"
        :rangeType="activeRangeType"
        :filter-descriptor="filterDescriptor"
        :available-range-types="availableRangeTypes"
        :limit-first-date="limitFirstDate"
        @update:activeDate="handleUpdateActiveDate"
        @update:rangeType="handleUpdateActiveRangeType"
        @update:filter="handleUpdateFilter"
      >
        <template #controls>
          <div class="md:hidden">
            <NcButton
              :aria-label="contextTranslate('Today', context)"
              type="tertiary"
              @click="handleScrollToToday"
            >
              <template #icon>
                <CalendarToday />
              </template>
            </NcButton>
          </div>
        </template>
        <div
          class="hidden flex-[1_1_auto] px-4 pb-2 md:flex md:flex-col md:justify-end"
        >
          <NcButton
            :aria-label="contextTranslate('Today', context)"
            wide
            @click="handleScrollToToday"
          >
            <template #icon>
              <CalendarToday />
            </template>
            {{ contextTranslate("Today", context) }}
          </NcButton>
        </div>
      </TimeTrackingAside>
      {{ /** timeTrackingPageMixin
         *
         * handleUpdateActiveDate
         * handleUpdateActiveRangeType
         * handleUpdateFilter
         * */
      }}
      <VPageContent
        :wrapper-styles="{ scrollPaddingTop: 'calc(38px * 2)' }"
        class="relative"
      >
        <VLoader v-if="isLoading" absolute />
        <div class="flex-[1_1_auto] p-4">
          <TimeTrackingView
            :model-data="modelData"
            :totals="totals"
            :active-range-type="activeRangeType"
            :task-draggable="true"
            :highlight-item-slug="highlightItemSlug"
            @onClickDate="handleClickDate"
            @onCopy="handleCopy"
            @onDelete="handleDelete"
            @onDragEnd="handleDragEnd"
            @onClearHighlight="handleClearHighlight"
          />
        </div>
      </VPageContent>
    </VPageLayout>
    <TimeTrackingQuickForm
      :active="isFormActive"
      :date="formDate"
      @on-close="handleCloseForm"
      @on-submit-success="handleSubmitFormSuccess"
      @on-submit-and-continue-success="fetchDataWithFilters"
    />
  </VPage>
</template>

<script>
import { format, startOfYear } from "date-fns";
import { t } from "@nextcloud/l10n";
import { mapState } from "pinia";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcActions,
  NcActionButton,
  NcButton,
} from "@nextcloud/vue";

import ClockTimeEightOutline from "vue-material-design-icons/ClockTimeEightOutline.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import CalendarToday from "vue-material-design-icons/CalendarToday.vue";

import {
  VPage,
  VPageLayout,
  VPageContent,
  TimeTrackingView,
  TimeTrackingAside,
  TimeTrackingQuickForm,
  TimeTrackingFilter,
} from "@/widgets";
import { VToolbar, VScrollArea, VLoader } from "@/shared/components";

import { fetchUserStatistics } from "@/entities/statistics/api";
import {
  deleteUserTimeInfo,
  editReportSortMultiple,
} from "@/entities/timeInfo/api";
import { fetchUserProjectsForReport } from "@/entities/users/api";

import { usePermissionStore } from "@/app/store/permission";

import { timeTrackingPageMixin } from "@/shared/lib/mixins/timeTrackingPageMixin";
import { abortControllerMixin } from "@/shared/lib/mixins/abortControllerMixin";

import { transformDotsToDashDate } from "@/shared/lib/helpers/date";
import { handleRestErrors } from "@/shared/lib/helpers/errors";
import { initFilterDescriptor } from "@/shared/lib/helpers/filter";

import {
  LOCALSTORAGE_SELF_STATISTICS_ACTIVE_DATE,
  LOCALSTORAGE_SELF_STATISTICS_RANGE_TYPE,
} from "@/shared/lib/constants/localStorage";

const getFormatedTodayDate = () => format(new Date(), "yyyy-MM-dd");

export default {
  name: "SelfTimeTrackingPage",
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    NcButton,
    ClockTimeEightOutline,
    Plus,
    CalendarToday,
    VPage,
    VPageLayout,
    VPageContent,
    TimeTrackingView,
    TimeTrackingAside,
    TimeTrackingQuickForm,
    TimeTrackingFilter,
    VToolbar,
    VScrollArea,
    VLoader,
  },
  mixins: [
    timeTrackingPageMixin,
    contextualTranslationsMixin,
    abortControllerMixin,
  ],
  data: () => ({
    context: "user/time-tracking",
    modelData: [],
    totals: {},
    firstReportDate: null,
    isLoading: false,
    isFormActive: false,
    formDate: getFormatedTodayDate(),
    localStorageActiveDateKey: LOCALSTORAGE_SELF_STATISTICS_ACTIVE_DATE,
    localStorageActiveRangeTypeKey: LOCALSTORAGE_SELF_STATISTICS_RANGE_TYPE,
    filterDescriptor: initFilterDescriptor([
      {
        key: "projects",
        type: "select",
        multiple: true,
        options: [],
        value: [],
        fetchOptionsFunction: fetchUserProjectsForReport,
        placeholder: t("done", "Project"),
      },
    ]),
    highlightItemSlug: null,

    /** timeTrackingPageMixin
     *
     * activeRangeType: "month",
     * activeDate: new Date()
     * firstReportDate: null
     */
  }),
  computed: {
    ...mapState(usePermissionStore, ["isOfficer"]),
    availableRangeTypes() {
      if (this.isOfficer) {
        return [];
      }

      return ["month", "week"];
    },
    limitFirstDate() {
      if (this.firstReportDate) {
        return startOfYear(this.firstReportDate);
      }

      return startOfYear(new Date());
    },
  },
  methods: {
    handleClickCreate() {
      this.formDate = getFormatedTodayDate();
      this.isFormActive = true;
    },
    handleClickDate({ date }) {
      this.formDate = date;
      this.isFormActive = true;
    },
    handleCloseForm() {
      this.isFormActive = false;
    },
    async handleFetchData(payload) {
      this.isLoading = true;

      try {
        const filters = payload?.filters || {};
        const { date_from, date_to, callback } = payload;

        this.resetAbortController();

        const { data, totals, firstReportDate } = await fetchUserStatistics({
          date_from,
          date_to,
          signal: this.abortController.signal,
          ...filters,
        });

        this.modelData = data;
        this.totals = totals;
        this.firstReportDate = firstReportDate || null;

        if (callback) {
          callback();
        }

        this.isLoading = false;
      } catch (e) {
        this.handleCatchAbortControllerError(e);
      }
    },
    handleCopy({ comment, date, description, minutes, project_id, task_link }) {
      this.$router.push({
        name: "time-tracking-new",
        query: {
          comment,
          date,
          description,
          minutes,
          project_id,
          task_link,
        },
      });
    },
    async handleDelete({ slug, slug_type }) {
      if (
        !confirm(
          this.contextTranslate("Are you sure you want to delete the record?")
        )
      ) {
        return;
      }

      try {
        await deleteUserTimeInfo({ slug, slug_type });

        this.fetchDataWithFilters();
      } catch (e) {
        console.error(e);
      }
    },
    async handleDragEnd({ list }) {
      try {
        const payload = list.map((item, index) => ({
          slug: item.id,
          sort: index + 1,
        }));

        await editReportSortMultiple(payload);
      } catch (e) {
        console.error(e);

        handleRestErrors(e);
      }
    },
    scrollToDate(date) {
      const scrollToElementSelector = `#day-${date}`;
      const $dayElement = document.querySelector(scrollToElementSelector);

      if (!$dayElement) {
        return;
      }

      $dayElement.scrollIntoView({ behavior: "smooth", block: "start" });
    },
    async handleScrollToToday() {
      this.activeDate = new Date();

      this.setFilterQuery(); // timeTrackingPageMixin

      await this.fetchDataWithFilters(); // timeTrackingPageMixin

      this.$nextTick(() => {
        const date = getFormatedTodayDate();

        this.scrollToDate(date);
      });
    },
    async handleSubmitFormSuccess({ payload, response }) {
      const { date } = payload;

      const searchDate = transformDotsToDashDate(date);

      this.activeDate = new Date(searchDate);

      this.saveLocalStorageActiveDate(); // timeTrackingPageMixin

      await this.fetchDataWithFilters();

      this.scrollToDate(searchDate);

      if (response?.data?.slug) {
        this.highlightItemSlug = response.data.slug;
      }
    },
    handleClearHighlight() {
      setTimeout(() => {
        this.highlightItemSlug = null;
      }, 1500);
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

      if (
        this.isOfficer === false &&
        ["year", "quarter"].includes(localActiveRangeType) === true
      ) {
        this.activeRangeType = "month";

        return;
      }

      this.activeRangeType = localActiveRangeType;
    },
    async init() {
      const {
        query: { searchDate, newItemSlug },
      } = this.$route;

      const localActiveDate = localStorage.getItem(
        this.localStorageActiveDateKey
      );

      if (searchDate) {
        this.activeDate = new Date(searchDate);

        this.saveLocalStorageActiveDate(); // timeTrackingPageMixin
      } else if (localActiveDate) {
        this.activeDate = new Date(localActiveDate);
      }

      this.setActiveRangeTypeFromLocalStorage();

      await this.initFetchDataWithFilters(); // timeTrackingPageMixin

      if (!searchDate) {
        return;
      }

      this.$nextTick(() => {
        if (newItemSlug) {
          this.highlightItemSlug = newItemSlug;
        }

        this.scrollToDate(searchDate);
      });
    },
  },
  mounted() {
    this.init();
  },
};
</script>

<style scoped>
.scroll-area {
  scroll-padding-top: "200px";
}
</style>
