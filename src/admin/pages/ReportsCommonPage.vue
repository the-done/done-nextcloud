/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage data-id="ReportsCommonPage">
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb :name="contextTranslate('Reports', context)" :to="{ name: 'report-home' }" forceIconText>
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
      <VPageContent>
        <template v-if="isInitLoading === false">
          <VEmptyState
            v-if="modelData && modelData.length === 0"
            :caption="contextTranslate('No data found for the selected period', context)"
          />
          <template v-else>
            <div
              v-for="project in modelData"
              :key="project.project_id"
              class="reports-common-page__block"
            >
              <div
                class="time-tracking-row time-tracking-row--md time-tracking-row--sticky"
              >
                <ReportActionsProjectItem
                  :model-data="project"
                  chip-variant="primary"
                />
              </div>
              <div
                v-if="project.users && project.users.length > 0"
                class="v-flex v-flex--column"
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

import FileChart from "vue-material-design-icons/FileChart.vue";

import { fetchCommonStatistics } from "@/admin/entities/reports/api";
import { fetchProjects } from "@/common/entities/projects/api";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
  VPageAsideNavigation,
} from "@/common/widgets/VPage";
import { TimeTrackingAside } from "@/common/widgets/TimeTrackingAside";
import { TimeTrackingFilter } from "@/common/widgets/TimeTrackingFilter";

import ReportActionsUserItem from "@/admin/features/ReportActionsUserItem/ReportActionsUserItem.vue";
import ReportActionsProjectItem from "@/admin/features/ReportActionsProjectItem/ReportActionsProjectItem.vue";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import VEmptyState from "@/common/shared/components/VEmptyState/VEmptyState.vue";

import { timeTrackingPageMixin } from "@/common/shared/mixins/timeTrackingPageMixin";

import { initFilterDescriptor } from "@/common/shared/lib/filterHelpers";

import { LOCALSTORAGE_REPORT_COMMON_RANGE_TYPE } from "@/common/shared/lib/constants";

import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

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
  },
  mixins: [timeTrackingPageMixin, contextualTranslationsMixin],
  data() {
    return {
      isInitLoading: false,
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
          placeholder: this.contextTranslate('Project', this.context),
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
    t,
    async handleFetchData(payload) {
      try {
        const filters = payload?.filters || {};
        const { date_from, date_to } = payload;

        const { data } = await fetchCommonStatistics({
          date_from,
          date_to,
          ...filters,
        });

        this.modelData = data;
      } catch (e) {
        console.log(e);
      }
    },
    async init() {
      this.isInitLoading = true;

      try {
        const localActiveRangeType = localStorage.getItem(
          this.localStorageActiveRangeTypeKey
        );

        if (localActiveRangeType) {
          this.activeRangeType = localActiveRangeType;
        }

        this.initFetchDataWithFilters(); // timeTrackingPageMixin
      } catch (e) {
        console.log(e);
      } finally {
        this.isInitLoading = false;
      }
    },
  },
  mounted() {
    this.init();
  },
};
</script>

<style scoped>
.reports-common-page__block {
  padding: 16px;
}

.reports-common-page__block + .reports-common-page__block {
  border-top: 1px solid var(--color-border);
}
</style>
