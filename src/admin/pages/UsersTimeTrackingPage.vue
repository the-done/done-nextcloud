/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage class="overflow-hidden">
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('Employees', context)"
          :to="{ name: 'staff-table' }"
          forceIconText
        >
          <template #icon>
            <AccountMultiple />
          </template>
        </NcBreadcrumb>
        <NcBreadcrumb
          :name="contextTranslate('Time tracking', context)"
          :to="{ name: 'staff-table' }"
        />
        <NcBreadcrumb :name="pageTitle" />
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
      <VPageContent :style="{ scrollPaddingTop: '42px' }" class="relative">
        <VLoader v-if="isLoading" absolute />
        <div class="flex-[1_1_auto] p-4">
          <TimeTrackingView
            :model-data="modelData"
            :totals="totals"
            :active-range-type="activeRangeType"
            :day-disabled="true"
            :task-disabled="true"
          />
        </div>
      </VPageContent>
    </VPageLayout>
  </VPage>
</template>

<script>
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcActions,
  NcActionButton,
} from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";

import AccountMultiple from "vue-material-design-icons/AccountMultiple.vue";

import { fetchUserPublicDataBySlug } from "@/common/entities/users/api";
import { fetchUserStatisticsBySlug } from "@/common/entities/statistics/api";
import { fetchProjects } from "@/common/entities/projects/api";

import { VPage, VPageLayout, VPageContent } from "@/common/widgets/VPage";
import { TimeTrackingView } from "@/common/widgets/TimeTrackingView";
import { TimeTrackingAside } from "@/common/widgets/TimeTrackingAside";
import { TimeTrackingFilter } from "@/common/widgets/TimeTrackingFilter";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import VLoader from "@/common/shared/components/VLoader/VLoader.vue";

import { timeTrackingPageMixin } from "@/common/shared/mixins/timeTrackingPageMixin";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";
import { abortControllerMixin } from "@/admin/shared/lib/mixins/abortControllerMixin";

import { getJoinString } from "@/common/shared/lib/helpers";
import { initFilterDescriptor } from "@/common/shared/lib/filterHelpers";

import { LOCALSTORAGE_USER_STATISTICS_RANGE_TYPE } from "@/common/shared/lib/constants";

export default {
  name: "UsersTablePage",
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    AccountMultiple,
    VPage,
    VPageLayout,
    VPageContent,
    TimeTrackingView,
    TimeTrackingAside,
    TimeTrackingFilter,
    VToolbar,
    VLoader,
  },
  mixins: [
    timeTrackingPageMixin,
    contextualTranslationsMixin,
    abortControllerMixin,
  ],
  data: () => ({
    context: "user/time-tracking",
    isLoading: false,
    modelData: [],
    totals: {},
    userData: null,
    localStorageActiveRangeTypeKey: LOCALSTORAGE_USER_STATISTICS_RANGE_TYPE,
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
     * activeDate: new Date(),
     */
  }),
  computed: {
    slug() {
      return this.$route.params.slug;
    },
    pageTitle() {
      if (this.userData === null) {
        return "";
      }
      const { lastname, name, middle_name, pname } = this.userData;

      const fullName = getJoinString([lastname, name, middle_name]);
      const position = pname?.toLowerCase();

      if (position) {
        return `${fullName}, ${position}`;
      }

      return fullName;
    },
  },
  methods: {
    t,
    async handleFetchData(payload) {
      this.isLoading = true;

      try {
        const filters = payload?.filters || {};
        const { date_from, date_to } = payload;

        this.resetAbortController();

        const { data, totals } = await fetchUserStatisticsBySlug({
          date_from,
          date_to,
          slug: this.slug,
          signal: this.abortController.signal,
          ...filters,
        });

        this.modelData = data;
        this.totals = totals;
        this.isLoading = false;
      } catch (e) {
        this.handleCatchAbortControllerError(e);
      }
    },
    async fetchUserData() {
      try {
        const { data } = await fetchUserPublicDataBySlug({ slug: this.slug });

        this.userData = data;
      } catch (e) {
        console.log(e);
      }
    },
    init() {
      const {
        query: {
          activeDate: queryActiveDate,
          activeRangeType: queryActiveRangeType,
        },
      } = this.$route;

      if (queryActiveDate) {
        this.activeDate = new Date(queryActiveDate);
      }

      if (queryActiveRangeType) {
        this.activeRangeType = queryActiveRangeType;
      } else {
        this.setActiveRangeTypeFromLocalStorage();
      }

      this.fetchUserData();
      this.initFetchDataWithFilters(); // timeTrackingPageMixin
    },
  },
  mounted() {
    this.init();
  },
};
</script>
