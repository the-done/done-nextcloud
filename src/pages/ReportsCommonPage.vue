/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage data-component-id="ReportsCommonPage">
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('Reports', context)"
          :to="{ name: 'report-home' }"
          forceIconText
        >
          <template #icon>
            <FileChart />
          </template>
        </NcBreadcrumb>
        <NcBreadcrumb :name="contextTranslate('Common', context)" />
      </NcBreadcrumbs>
    </VToolbar>
    <VPageLayout>
      <TimeTrackingAside
        :active-date="activeDate"
        :rangeType="activeRangeType"
        :filter-descriptor="filterDescriptor"
        @update:activeDate="handleUpdateActiveDate"
        @update:rangeType="handleUpdateActiveRangeType"
        @update:filter="handleUpdateFilter"
      />
      {{ /** timeTrackingPageMixin
         * 
         * handleUpdateActiveDate
         * handleUpdateActiveRangeType
         * handleUpdateFilter
         * */
      }}
      <VPageContent class="relative">
        <VLoader v-if="isLoading" absolute />
        <template v-if="isInitLoading === false">
          <VEmptyState
            v-if="modelData && modelData.length === 0"
            :caption="
              contextTranslate('No data found for the selected period', context)
            "
          />
          <template v-else>
            <div
              v-for="(project, index) in modelData"
              :key="project.project_id"
              :class="{
                'p-4': true,
                'border-t border-(--color-border)': index > 0,
              }"
            >
              <div
                :class="[
                  'sticky top-0 left-0 w-full bg-(--color-main-background) z-100',
                  'flex gap-4 items-center',
                  'h-(--time-tracking-row-height) nowrap',
                ]"
              >
                <ReportActionsProjectItem
                  :model-data="project"
                  chip-variant="primary"
                />
              </div>
              <div
                v-if="project.users && project.users.length > 0"
                class="flex flex-col"
              >
                <ReportActionsUserItem
                  v-for="user in project.users"
                  :key="user.user_slug"
                  :model-data="user"
                  :active-date="activeDate"
                  :active-range-type="activeRangeType"
                />
              </div>
            </div>
          </template>
        </template>
      </VPageContent>
    </VPageLayout>
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";

import FileChart from "vue-material-design-icons/FileChart.vue";

import { fetchCommonStatistics } from "@/entities/reports/api";
import { fetchProjects } from "@/entities/projects/api";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
  VPageAsideNavigation,
  TimeTrackingAside,
  TimeTrackingFilter,
} from "@/widgets";

import { ReportActionsUserItem, ReportActionsProjectItem } from "@/features";
import { VToolbar, VEmptyState, VLoader } from "@/shared/components";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { abortControllerMixin } from "@/shared/lib/mixins/abortControllerMixin";
import { timeTrackingPageMixin } from "@/shared/lib/mixins/timeTrackingPageMixin";

import { initFilterDescriptor } from "@/shared/lib/helpers/filter";

import { LOCALSTORAGE_REPORT_COMMON_RANGE_TYPE } from "@/shared/lib/constants/localStorage";

export default {
  name: "ReportsCommonPage",
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    FileChart,
    VPage,
    VPageLayout,
    VPageContent,
    VPagePadding,
    VPageAsideNavigation,
    TimeTrackingAside,
    TimeTrackingFilter,
    ReportActionsUserItem,
    ReportActionsProjectItem,
    VToolbar,
    VEmptyState,
    VLoader,
  },
  mixins: [
    timeTrackingPageMixin,
    contextualTranslationsMixin,
    abortControllerMixin,
  ],
  data() {
    return {
      isLoading: false,
      isInitLoading: true,
      modelData: [],
      localStorageActiveRangeTypeKey: LOCALSTORAGE_REPORT_COMMON_RANGE_TYPE,
      filterDescriptor: initFilterDescriptor([
        {
          key: "projects",
          type: "select",
          multiple: true,
          options: [],
          value: [],
          fetchOptionsFunction: fetchProjects,
          placeholder: t("done", "Project"),
        },
      ]),
      /* timeTrackingPageMixin
       *
       * activeRangeType: "month",
       * activeDate: new Date()
       */
    };
  },
  methods: {
    async handleFetchData(payload) {
      this.isLoading = true;

      try {
        const filters = payload?.filters || {};
        const { date_from, date_to } = payload;

        this.resetAbortController();

        const { data } = await fetchCommonStatistics({
          date_from,
          date_to,
          signal: this.abortController.signal,
          ...filters,
        });

        this.modelData = data;
        this.isLoading = false;
      } catch (e) {
        this.handleCatchAbortControllerError(e);
      }
    },
  },
  mounted() {
    this.init();
  },
};
</script>
