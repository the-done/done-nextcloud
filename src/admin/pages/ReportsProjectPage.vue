/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage class="reports-project-page">
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
        <NcBreadcrumb :name="contextTranslate('By projects', context)" />
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
         * */
      }}
      <VPageContent class="relative">
        <VLoader v-if="isLoading" absolute />
        <template v-if="isInitLoading === false">
          <VEmptyState
            v-if="modelData && modelData.length === 0"
            :caption="
              contextTranslate(
                'No data found for the specified filters',
                context
              )
            "
          />
          <ReportProjectView
            v-else
            :model-data="modelData"
            :totals="totals"
            :active-date="activeDate"
            :active-range-type="activeRangeType"
          />
        </template>
      </VPageContent>
    </VPageLayout>
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb, NcListItemIcon } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";

import FileChart from "vue-material-design-icons/FileChart.vue";

import { fetchProjectsStatistics } from "@/admin/entities/reports/api";
import { fetchProjects } from "@/common/entities/projects/api";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPageAsideNavigation,
} from "@/common/widgets/VPage";
import { ReportProjectView } from "@/admin/widgets/ReportProjectView";
import { TimeTrackingAside } from "@/common/widgets/TimeTrackingAside";
import { TimeTrackingFilter } from "@/common/widgets/TimeTrackingFilter";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import VDropdown from "@/common/shared/components/VDropdown/VDropdown.vue";
import VLoader from "@/common/shared/components/VLoader/VLoader.vue";
import VEmptyState from "@/common/shared/components/VEmptyState/VEmptyState.vue";

import { timeTrackingPageMixin } from "@/common/shared/mixins/timeTrackingPageMixin";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";
import { abortControllerMixin } from "@/admin/shared/lib/mixins/abortControllerMixin";

import { initFilterDescriptor } from "@/common/shared/lib/filterHelpers";
import { findPathToNode } from "@/common/shared/lib/helpers";

import {
  LOCALSTORAGE_REPORT_PROJECT_RANGE_TYPE,
} from "@/common/shared/lib/constants";

export default {
  name: "ReportsProjectPage",
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcListItemIcon,
    VLoader,
    FileChart,
    VPage,
    VPageLayout,
    VPageContent,
    VPageAsideNavigation,
    TimeTrackingFilter,
    VEmptyState,
    ReportProjectView,
    TimeTrackingAside,
    VToolbar,
    VDropdown,
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
      totals: {},
      localStorageActiveRangeTypeKey: LOCALSTORAGE_REPORT_PROJECT_RANGE_TYPE,
      /* activeProject: null, */
      /* projectOptions: [], */
      filterDescriptor: initFilterDescriptor([
        {
          key: "slug",
          type: "select",
          options: [],
          value: undefined,
          fetchOptionsFunction: fetchProjects,
          placeholder: this.contextTranslate("Project", this.context),
        },
      ]),
      /* timeTrackingPageMixin
       *
       * activeRangeType: "month",
       * activeDate: new Date(),
       */
    };
  },
  methods: {
    t,
    transformDataForFront(data) {
      return data.map((item) => {
        if (item.children) {
          return {
            ...item,
            expanded: false,
            children: this.transformDataForFront(item.children),
          };
        }

        return {
          ...item,
          expanded: false,
        };
      });
    },
    expandNodes() {
      const result = this.modelData.reduce((accum, project) => {
        return [
          ...accum,
          ...findPathToNode([project], "type", this.activeRangeType),
        ];
      }, []);

      result.forEach((node) => {
        node.expanded = true;
      });
    },
    async handleFetchData(payload) {
      this.isLoading = true;

      try {
        const filters = payload?.filters || {};
        const { date_from, date_to } = payload;

        this.resetAbortController();

        const { data, totals } = await fetchProjectsStatistics({
          date_from,
          date_to,
          signal: this.abortController.signal,
          ...filters,
        });

        this.modelData = this.transformDataForFront(data);
        this.totals = totals;
        this.isLoading = false;

        const { slug } = filters;

        if (!slug) {
          return;
        }

        this.$nextTick(() => {
          this.expandNodes();
        });
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
