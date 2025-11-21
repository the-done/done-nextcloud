/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage class="user-time-tracking-page">
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('Employees', context)"
          :to="{ name: 'staff-table' }"
          forceIconText
        >
          <template #icon>
            <Account />
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

import Account from "vue-material-design-icons/Account.vue";

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

import { getJoinString } from "@/common/shared/lib/helpers";
import { initFilterDescriptor } from "@/common/shared/lib/filterHelpers";

import { LOCALSTORAGE_USER_STATISTICS_RANGE_TYPE } from "@/common/shared/lib/constants";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "UsersTablePage",
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    Account,
    VPage,
    VPageLayout,
    VPageContent,
    TimeTrackingView,
    TimeTrackingAside,
    TimeTrackingFilter,
    VToolbar,
    VLoader,
  },
  mixins: [timeTrackingPageMixin, contextualTranslationsMixin],
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

        const { data, totals } = await fetchUserStatisticsBySlug({
          date_from,
          date_to,
          slug: this.slug,
          ...filters,
        });

        this.modelData = data;
        this.totals = totals;
      } catch (e) {
        console.log(e);
      } finally {
        this.isLoading = false;
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

      const localActiveRangeType = localStorage.getItem(
        this.localStorageActiveRangeTypeKey
      );

      if (queryActiveDate) {
        this.activeDate = new Date(queryActiveDate);
      }

      if (queryActiveRangeType) {
        this.activeRangeType = queryActiveRangeType;
      } else if (localActiveRangeType) {
        this.activeRangeType = localActiveRangeType;
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

<style scoped>
.user-time-tracking-page {
  overflow: hidden;
}

.user-time-tracking-page__content {
  display: flex;
  height: 100%;
  border-top: 1px solid var(--color-border);
  overflow: hidden;
}

.user-time-tracking-page__main {
  flex: 1 1 auto;
  padding: 16px 32px;
}
</style>
