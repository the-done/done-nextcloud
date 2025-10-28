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
      <VPageContent class="p-4">
        <VLoader v-if="isInitLoading === true" />
        <template v-else>
          <VEmptyState
            v-if="modelData && modelData.length === 0"
            :caption="
              contextTranslate('No data found for the selected period', context)
            "
          />
          <div class="flex flex-col gap-2" v-else>
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
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcListItemIcon,
  NcButton,
} from "@nextcloud/vue";

import FileChart from "vue-material-design-icons/FileChart.vue";

import { fetchUsersCommonStatistics } from "@/admin/entities/reports/api";
import { fetchProjects } from "@/common/entities/projects/api";
import { fetchTeams } from "@/admin/entities/teams/api";
import {
  fetchContractTypesDictionary,
  fetchDirectionsDictionary,
} from "@/admin/entities/dictionaries/api";

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
import { TimeTrackingItem } from "@/common/features/TimeTrackingItem";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import VEmptyState from "@/common/shared/components/VEmptyState/VEmptyState.vue";
import VExpandIconButton from "@/common/shared/components/VExpandIconButton/VExpandIconButton.vue";
import VLoader from "@/common/shared/components/VLoader/VLoader.vue";

import { timeTrackingPageMixin } from "@/common/shared/mixins/timeTrackingPageMixin";

import { redirectToUserStatistics } from "@/admin/shared/lib/helpers";
import { getJoinString } from "@/common/shared/lib/helpers";
import { initFilterDescriptor } from "@/common/shared/lib/filterHelpers";

import { LOCALSTORAGE_REPORT_STAFF_RANGE_TYPE } from "@/common/shared/lib/constants";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

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
  mixins: [timeTrackingPageMixin, contextualTranslationsMixin],
  data() {
    return {
      context: "admin/users",
      isInitLoading: false,
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
          placeholder: this.contextTranslate("Project", this.context),
        },
        {
          key: "teams",
          type: "select",
          multiple: true,
          options: [],
          value: [],
          fetchOptionsFunction: fetchTeams,
          placeholder: this.contextTranslate("Team", this.context),
        },
        {
          key: "contract_types",
          type: "select",
          multiple: true,
          options: [],
          value: [],
          fetchOptionsFunction: fetchContractTypesDictionary,
          placeholder: this.contextTranslate("Contract type", this.context),
        },
        {
          key: "directions",
          type: "select",
          multiple: true,
          options: [],
          value: [],
          fetchOptionsFunction: fetchDirectionsDictionary,
          placeholder: this.contextTranslate("Direction", this.context),
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
        return t("done", "No role in project");
      }

      return getJoinString(userRolesInProject, ", ");
    },
    handleExpandRow(row) {
      row.expanded = !row.expanded;
    },
    async handleFetchData(payload) {
      try {
        const filters = payload?.filters || {};
        const { date_from, date_to } = payload;

        const { data } = await fetchUsersCommonStatistics({
          date_from,
          date_to,
          ...filters,
        });

        this.modelData = this.transformDataForFront(data);
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

        this.initFetchDataWithFilters();
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
