/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage data-id="ReportStaffPage">
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
        <NcBreadcrumb :name="contextTranslate('By employees', context)" />
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
          <div class="flex flex-col gap-2 p-4" v-else>
            <div v-for="user in modelData" :key="user.user_slug">
              <ReportActionsUserItem
                :model-data="user"
                :active-date="activeDate"
                :active-range-type="activeRangeType"
                :show-position="false"
                chip-variant="primary"
              >
                <template #actions>
                  <VExpandIconButton
                    :expanded="user.expanded"
                    @on-click="() => handleExpandRow(user)"
                  />
                </template>
              </ReportActionsUserItem>
              <div
                v-if="
                  user.projects &&
                  user.projects.length > 0 &&
                  user.expanded === true
                "
                class="flex flex-col gap-2 pl-[42px]"
              >
                <div
                  v-for="project in user.projects"
                  :key="project.project_id"
                  class="flex flex-col gap-2"
                >
                  <ReportActionsProjectItem :model-data="project">
                    <template #actions>
                      <VExpandIconButton
                        :expanded="project.expanded"
                        @on-click="() => handleExpandRow(project)"
                      />
                    </template>
                  </ReportActionsProjectItem>
                  <div
                    v-if="
                      project.reports &&
                      project.reports.length > 0 &&
                      project.expanded === true
                    "
                    class="pl-[42px] flex flex-col gap-2"
                  >
                    <TimeTrackingItem
                      v-for="report in project.reports"
                      :key="report.report_id"
                      :model-data="report"
                      :disabled="true"
                      :show-project="false"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </VPageContent>
    </VPageLayout>
  </VPage>
</template>

<script>
import { t } from "@nextcloud/l10n";
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcListItemIcon,
  NcButton,
} from "@nextcloud/vue";

import FileChart from "vue-material-design-icons/FileChart.vue";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
  VPageAsideNavigation,
  TimeTrackingAside,
  TimeTrackingFilter,
} from "@/widgets";

import {
  ReportActionsUserItem,
  ReportActionsProjectItem,
  TimeTrackingItem,
} from "@/features";

import {
  VToolbar,
  VEmptyState,
  VExpandIconButton,
  VLoader,
} from "@/shared/components";

import { fetchUsersCommonStatistics } from "@/entities/reports/api";
import { fetchProjects } from "@/entities/projects/api";
import { fetchTeams } from "@/entities/teams/api";
import {
  fetchContractTypesDictionary,
  fetchDirectionsDictionary,
} from "@/entities/dictionaries/api";

import { timeTrackingPageMixin } from "@/shared/lib/mixins/timeTrackingPageMixin";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { abortControllerMixin } from "@/shared/lib/mixins/abortControllerMixin";

import { redirectToUserStatistics } from "@/shared/lib/helpers/navigation";
import { getJoinString } from "@/shared/lib/helpers/string";
import { initFilterDescriptor } from "@/shared/lib/helpers/filter";

import { LOCALSTORAGE_REPORT_STAFF_RANGE_TYPE } from "@/shared/lib/constants/localStorage";

export default {
  name: "ReportsCommonPage",
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcListItemIcon,
    NcButton,
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
    TimeTrackingItem,
    VToolbar,
    VEmptyState,
    VExpandIconButton,
    VLoader,
  },
  mixins: [
    timeTrackingPageMixin,
    contextualTranslationsMixin,
    abortControllerMixin,
  ],
  data() {
    return {
      context: "admin/users",
      isLoading: false,
      isInitLoading: true,
      modelData: [],
      localStorageActiveRangeTypeKey: LOCALSTORAGE_REPORT_STAFF_RANGE_TYPE,
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
        {
          key: "teams",
          type: "select",
          multiple: true,
          options: [],
          value: [],
          fetchOptionsFunction: fetchTeams,
          placeholder: t("done", "Team"),
        },
        {
          key: "contract_types",
          type: "select",
          multiple: true,
          options: [],
          value: [],
          fetchOptionsFunction: fetchContractTypesDictionary,
          placeholder: t("done", "Contract type"),
        },
        {
          key: "directions",
          type: "select",
          multiple: true,
          options: [],
          value: [],
          fetchOptionsFunction: fetchDirectionsDictionary,
          placeholder: t("done", "Direction"),
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

    handleClickUserStatistics({ slug }) {
      redirectToUserStatistics({
        slug,
        activeDate: this.activeDate,
        activeRangeType: this.activeRangeType,
        $router: this.$router,
      });
    },
    transformDataForFront(data) {
      return data.reduce((accum, item) => {
        return [
          ...accum,
          {
            ...item,
            expanded: false,
            projects: item.projects?.length
              ? this.transformDataForFront(item.projects)
              : [],
          },
        ];
      }, []);
    },
    getUserRolesInProject(userRolesInProject) {
      if (!userRolesInProject || userRolesInProject.length === 0) {
        return this.contextTranslate("No role in project");
      }

      return getJoinString(userRolesInProject, ", ");
    },
    handleExpandRow(row) {
      row.expanded = !row.expanded;
    },
    async handleFetchData(payload) {
      this.isLoading = true;

      try {
        const filters = payload?.filters || {};
        const { date_from, date_to } = payload;

        this.resetAbortController();

        const { data } = await fetchUsersCommonStatistics({
          date_from,
          date_to,
          signal: this.abortController.signal,
          ...filters,
        });

        this.modelData = this.transformDataForFront(data);
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
