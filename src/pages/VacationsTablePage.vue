/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs v-if="breadcrumbs?.length > 0">
        <NcBreadcrumb
            v-for="(item, index) in breadcrumbs"
            :key="index"
            :name="contextTranslate(item.title, context)"
            :to="item.path"
            :forceIconText="index === 0"
        >
          <template v-if="item.icon" #icon>
            <component v-if="item.icon" :is="item.icon" />
            <CheckDecagram v-else />
          </template>
        </NcBreadcrumb>
      </NcBreadcrumbs>
      <div class="vacations-page__actions"
           v-if="activeTab === 'requests'"
      >
        <NcButton
            type="primary"
            :wide="true"
            :disabled="!hasAgreementScheme"
            @click="openAddDialog"
        >
          <template #icon>
            <Plus :size="20" />
          </template>
          {{ contextTranslate("New request", context) }}
        </NcButton>
      </div>
    </VToolbar>

    <VPageLayout>
      <!-- Sidebar with filters -->
      <TimeTrackingAside
        v-if="activeTab !== 'balance'"
        :active-date="activeDate"
        :range-type="activeRangeType"
        :filter-descriptor="filterDescriptor"
        :available-range-types="['month', 'year']"
        @update:activeDate="handleUpdateActiveDate"
        @update:rangeType="handleUpdateActiveRangeType"
        @update:filter="handleUpdateFilter"
      >
      </TimeTrackingAside>

      <!-- Main content -->
      <VPageContent class="relative">
        <VLoader v-if="isLoading" absolute />

        <template v-if="isInitLoading === false">
          <div v-if="!hasAgreementScheme" class="vacations-page__no-scheme-warning">
            <NcNoteCard type="warning">
              {{ contextTranslate("There is no suitable agreement scheme for vacations. Creating new vacation requests is disabled.", context) }}
              <RouterLink
                :to="{ name: 'settings-agreement-schemes' }"
                v-if="isApprover"
                class="v-link v-link--underline"
              >
                {{ contextTranslate("Configure in settings", context) }}
              </RouterLink>
            </NcNoteCard>
          </div>

          <!-- Section title + actions row -->
          <div class="vacations-page__tabs-row py-2 px-3">
            <!-- Current year, shown on the balance tab, left-aligned. -->
            <div v-if="activeTab === 'balance'" class="vacations-page__year">
              {{ currentYear }}
            </div>
            <div class="vacations-page__actions">
              <NcButton
                type="tertiary"
                @click="shareLink"
              >
                <template #icon>
                  <LinkVariant :size="20" />
                </template>
                {{ contextTranslate("Share link", context) }}
              </NcButton>
            </div>
          </div>

          <!-- Requests tab content -->
          <template v-if="activeTab === 'requests'">
            <VEmptyState
              v-if="!hasData"
              :caption="contextTranslate('No data found for the selected period', context)"
              class="m-4"
            />

            <!-- Table -->
            <VacationTable
              v-else
              :data="tableData"
              :projects="projects"
              :current-user-id="currentUserId"
              class="m-4"
              @view="openDetailsDialog"
              @history="openHistoryDialog"
              @edit="openEditDialog"
              @cancel="handleCancelVacation"
            />
          </template>

          <!-- Balance tab content -->
          <template v-else-if="activeTab === 'balance'">
            <div v-if="isLoadingBalance" class="vacations-page__loader">
              <VLoader />
            </div>
            <template v-else>
              <VacationBalance
                :balances="myBalance"
                class="m-4"
              />
              <VacationUpcoming
                :vacations="upcomingPaidVacations"
                class="mx-4 mb-4"
              />
            </template>
          </template>

          <!-- Gantt tab content (approvers only) -->
          <template v-else-if="activeTab === 'gantt'">
            <div v-if="isLoadingGantt || !ganttDateFrom" class="vacations-page__loader">
              <VLoader />
            </div>
            <VacationGantt
              v-else
              :employees="ganttEmployees"
              :date-from="ganttDateFrom"
              :date-to="ganttDateTo"
              :range-type="activeRangeType"
              class="vacations-page__gantt"
            />
          </template>
        </template>
      </VPageContent>
    </VPageLayout>

    <!-- Add/Edit Dialog -->
    <VacationFormDialog
      :open="formDialogOpen"
      :vacation="selectedVacation"
      :vacation-types="vacationTypes"
      :projects="projects"
      :employees="employees"
      :is-approver="isApprover"
      :current-user-id="currentUserId"
      @close="closeFormDialog"
      @success="handleFormSuccess"
    />

    <!-- Details Dialog -->
    <VacationDetailsDialog
      :open="detailsDialogOpen"
      :vacation="selectedVacation"
      :projects="projects"
      @close="closeDetailsDialog"
    />

    <!-- History Dialog -->
    <VacationHistoryDialog
      :open="historyDialogOpen"
      :vacation-id="selectedVacationId"
      @close="closeHistoryDialog"
    />
  </VPage>
</template>

<script>
import { t } from "@nextcloud/l10n";
import {
  NcBreadcrumb, NcBreadcrumbs,
  NcButton,
  NcNoteCard,
} from "@nextcloud/vue";

import PalmTree from "vue-material-design-icons/PalmTree.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import LinkVariant from "vue-material-design-icons/LinkVariant.vue";
import Cog from "vue-material-design-icons/Cog.vue";

import {
  VPage,
  VPageLayout,
  VPageContent,
  TimeTrackingAside,
} from "@/widgets";
import {
  VacationTable,
  VacationBalance,
  VacationUpcoming,
  VacationGantt,
  VacationFormDialog,
  VacationDetailsDialog,
  VacationHistoryDialog,
} from "@/features";
import { VToolbar, VLoader, VTextArea, VEmptyState, VAside } from "@/shared/components";

import {
  fetchVacationsTableData,
  fetchVacationTypes,
  fetchRemainingDays,
  fetchUpcomingPaidVacations,
  fetchVacationsForGantt,
  cancelVacation,
} from "@/entities/vacations/api";
import { fetchEmployeesOptionsForVacations } from "@/entities/vacations/api";
import { fetchProjectsOptionsForVacations } from "@/entities/vacations/api";
import { fetchUserProjectsForReport } from "@/entities/users/api";
import { fetchHasActiveScheme } from "@/entities/agreement/api";

import { usePermissionStore } from "@/app/store/permission";

import { timeTrackingPageMixin } from "@/shared/lib/mixins/timeTrackingPageMixin";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { initFilterDescriptor } from "@/shared/lib/helpers/filter";
import { VACATION_STATUS } from "@/entities/vacations/constants";

import { LOCALSTORAGE_VACATIONS_RANGE_TYPE } from "@/shared/lib/constants/localStorage";
import BookCog from "vue-material-design-icons/BookCog.vue";
import FileChart from "vue-material-design-icons/FileChart.vue";
import {mapState} from "pinia";
import CheckDecagram from "vue-material-design-icons/CheckDecagram.vue";
import {fetchVacationStatuses} from "../entities/vacations/api";

export default {
  name: "VacationsTablePage",
  mixins: [timeTrackingPageMixin, contextualTranslationsMixin],
  components: {
    CheckDecagram,
    FileChart,
    NcBreadcrumbs, BookCog,
    NcBreadcrumb,
    NcButton,
    NcNoteCard,
    VAside,
    PalmTree,
    Plus,
    LinkVariant,
    Cog,
    VPage,
    VPageLayout,
    VPageContent,
    TimeTrackingAside,
    VToolbar,
    VLoader,
    VTextArea,
    VEmptyState,
    VacationTable,
    VacationBalance,
    VacationUpcoming,
    VacationGantt,
    VacationFormDialog,
    VacationDetailsDialog,
    VacationHistoryDialog,
  },
  props: {
    additionalProps: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      context: "vacations",
      isLoading: false,
      isInitLoading: true,
      isLoadingBalance: false,
      tableData: {},
      vacationTypes: [],
      projects: [],
      employees: [],
      myBalance: {},
      upcomingPaidVacations: [],
      ganttEmployees: [],
      isLoadingGantt: false,
      ganttDateFrom: "",
      ganttDateTo: "",
      activeTab: this.additionalProps?.activeTab || "balance",
      hasAgreementScheme: true,

      // Range type storage key
      localStorageActiveRangeTypeKey: LOCALSTORAGE_VACATIONS_RANGE_TYPE,

      // Filter descriptor
      filterDescriptor: [],

      // Form dialog
      formDialogOpen: false,
      selectedVacation: null,

      // Details dialog
      detailsDialogOpen: false,

      // History dialog
      historyDialogOpen: false,
      selectedVacationId: null,
    };
  },
  computed: {
    ...mapState(usePermissionStore, ["isApprover", "currentUserId"]),
    hasData() {
      return Object.keys(this.tableData).length > 0;
    },
    activeTabLabel() {
      const labels = {
        balance: this.contextTranslate("My balance", this.context),
        requests: this.contextTranslate("Requests", this.context),
        gantt: this.contextTranslate("Schedule", this.context),
      };
      return labels[this.activeTab] || "";
    },
    breadcrumbs() {
      return this.additionalProps.breadcrumbs;
    },
    currentYear() {
      return new Date().getFullYear();
    },
  },
  watch: {
    activeTab(newTab) {
      if (newTab === "balance" && Object.keys(this.myBalance).length === 0) {
        this.loadBalanceTab();
      }
      if (newTab === "gantt" && this.ganttDateFrom) {
        this.loadGanttData(this.ganttDateFrom, this.ganttDateTo, this.getSerializedFilters());
      }
    },
    "additionalProps.activeTab": {
      handler(newTab) {
        if (newTab && newTab !== this.activeTab) {
          this.activeTab = newTab;
        }
      },
    },
  },
  methods: {
    // Initialize filter descriptor based on approver status
    // Note: This method should be called AFTER loading vacationTypes, projects, employees
    initFilterDescriptor() {
      const filters = [
        {
          key: "vacation_type_ids",
          type: "select",
          multiple: true,
          fetchOptionsFunction: async () => {
            const { data } = await fetchVacationTypes();
            return { data: data || [] };
          },
          value: [],
          placeholder: t("done", "Vacation type"),
        },
        {
          key: "project_ids",
          type: "select",
          multiple: true,
          fetchOptionsFunction: async () => {
            const { data } = await fetchProjectsOptionsForVacations();
            return { data: data || [] };
          },
          value: [],
          placeholder: t("done", "Project"),
        },
        {
          key: "vacation_status",
          type: "select",
          multiple: false,
          fetchOptionsFunction: async () => {
            const { data } = await fetchVacationStatuses();
            return { data: data || [] };
          },
          value: null,
          placeholder: t("done", "Status"),
        },
        {
          key: "employee_ids",
          type: "select",
          multiple: true,
          fetchOptionsFunction: async () => {
            const { data } = await fetchEmployeesOptionsForVacations();
            return { data: data || [] };
          },
          // Seed the current user so the implicit "my requests" default is
          // visible in the control (the backend scopes to the current user
          // when no employee is sent; this surfaces that selection). A URL that
          // carries employee_ids still wins via setFilterValuesFromQuery.
          value: this.currentUserId ? [this.currentUserId] : [],
          placeholder: t("done", "Employee"),
          userSelect: true,
        }
      ];

      this.filterDescriptor = initFilterDescriptor(filters);
    },

    // Check if current user is an approver
    async checkApproverStatus() {
      try {
        //const response = await checkIsApprover();
        this.isApprover = true;
        this.currentUserId = null;
      } catch (e) {
        console.error("Failed to check approver status:", e);
        this.isApprover = false;
      }
    },

    // Data loading
    async handleFetchData(payload) {
      try {
        this.isLoading = true;

        const { date_from, date_to, filters = {} } = payload;

        this.ganttDateFrom = date_from;
        this.ganttDateTo   = date_to;

        const params = {
          date_from,
          date_to,
          ...filters,
        };

        const { data } = await fetchVacationsTableData(params);
        this.tableData = data || {};
        this.loadBalanceTab(new Date(date_from).getFullYear());

        if (this.activeTab === "gantt") {
          this.loadGanttData(date_from, date_to, filters);
        }
      } catch (e) {
        console.error("Failed to fetch vacations:", e);
        this.tableData = {};
      } finally {
        this.isLoading = false;
      }
    },

    async loadGanttData(dateFrom, dateTo, filters = {}) {
      try {
        this.isLoadingGantt = true;
        const { data } = await fetchVacationsForGantt({
          date_from: dateFrom,
          date_to: dateTo,
          ...filters,
        });
        this.ganttEmployees = data || [];
      } catch (e) {
        console.error("Failed to load gantt data:", e);
        this.ganttEmployees = [];
      } finally {
        this.isLoadingGantt = false;
      }
    },

    async loadVacationTypes() {
      try {
        const { data } = await fetchVacationTypes();
        this.vacationTypes = data || [];
      } catch (e) {
        console.error("Failed to fetch vacation types:", e);
        this.vacationTypes = [];
        redirectNotFoundPage(this.$router);
      }
    },

    async loadProjects() {
      try {
        const { data } = this.isApprover
          ? await fetchProjectsOptionsForVacations()
          : await fetchUserProjectsForReport();
        this.projects = data || [];
      } catch (e) {
        console.error("Failed to fetch projects:", e);
        this.projects = [];
      }
    },

    async loadEmployees() {
      try {
        const { data } = await fetchEmployeesOptionsForVacations();
        this.employees = data || [];
      } catch (e) {
        console.error("Failed to fetch employees:", e);
        this.employees = [];
      }
    },

    async loadHasAgreementScheme() {
      try {
        const { data } = await fetchHasActiveScheme("vacation");
        this.hasAgreementScheme = Boolean(data?.has_active_scheme);
      } catch (e) {
        console.error("Failed to check agreement scheme:", e);
        this.hasAgreementScheme = true;
      }
    },

    async loadBalanceTab(year = new Date().getFullYear()) {
      try {
        this.isLoadingBalance = true;
        const [balanceResult, upcomingResult] = await Promise.all([
          fetchRemainingDays({ year }),
          fetchUpcomingPaidVacations(),
        ]);
        this.myBalance = balanceResult.data || {};
        this.upcomingPaidVacations = upcomingResult.data || [];
      } catch (e) {
        console.error("Failed to fetch balance:", e);
        this.myBalance = {};
        this.upcomingPaidVacations = [];
      } finally {
        this.isLoadingBalance = false;
      }
    },

    // Share link
    shareLink() {
      const url = window.location.href;
      navigator.clipboard.writeText(url).then(() => {
        this.$notify({
          title: this.contextTranslate("Link copied", this.context),
          type: "success",
        });
      }).catch(() => {
        this.$notify({
          title: this.contextTranslate("Failed to copy link", this.context),
          type: "error",
        });
      });
    },

    // Form dialog
    openAddDialog() {
      this.selectedVacation = null;
      this.formDialogOpen = true;
    },

    openEditDialog(vacation) {
      this.selectedVacation = vacation;
      this.formDialogOpen = true;
    },

    closeFormDialog() {
      this.formDialogOpen = false;
      this.selectedVacation = null;
    },

    handleFormSuccess() {
      this.fetchDataWithFilters();
      if (this.activeTab === "balance") {
        this.loadBalanceTab();
      }
    },

    async handleCancelVacation(vacation) {
      const confirmed = window.confirm(
        this.contextTranslate("Cancel this vacation request? It will stay in history but no longer require approval.", this.context),
      );
      if (!confirmed) return;

      try {
        await cancelVacation(vacation.id);
        await this.fetchDataWithFilters();
        if (this.activeTab === "balance") {
          this.loadBalanceTab();
        }
      } catch (e) {
        console.error("Failed to cancel vacation:", e);
      }
    },

    // Details dialog
    openDetailsDialog(vacation) {
      this.selectedVacation = vacation;
      this.detailsDialogOpen = true;
    },

    closeDetailsDialog() {
      this.detailsDialogOpen = false;
      this.selectedVacation = null;
    },

    // History dialog
    openHistoryDialog(vacation) {
      this.selectedVacationId = vacation.id;
      this.historyDialogOpen = true;
    },

    closeHistoryDialog() {
      this.historyDialogOpen = false;
      this.selectedVacationId = null;
    },

    // Override init from mixin to load additional data
    async init() {
      try {
        // Load dictionaries in parallel
        await Promise.all([
          this.loadVacationTypes(),
          this.loadProjects(),
          this.loadEmployees(),
          this.loadHasAgreementScheme(),
        ]);

        // Initialize filter descriptor AFTER loading data (so options are populated)
        this.initFilterDescriptor();

        // Set range type from localStorage if not in query
        const query = this.$route.query;
        if (!query.active_range_type) {
          this.setActiveRangeTypeFromLocalStorage();
        }

        // Fetch data with filters from query
        await this.initFetchDataWithFilters();

        // Surface the default current-user employee filter in the URL too, so the
        // active selection is shareable/bookmarkable. Only when the URL did not
        // already carry an explicit employee filter (avoids a redundant push).
        if (this.currentUserId && !this.$route.query.employee_ids) {
          this.setFilterQuery();
        }
      } catch (e) {
        console.error("Failed to initialize:", e);
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
.vacations-page__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 16px 20px;
  border-bottom: 1px solid var(--color-border);
  background-color: var(--color-main-background);
}

.vacations-page__no-scheme-warning {
  margin: 8px 16px 16px;
}

.vacations-page__no-scheme-warning :deep(.notecard) {
  margin: 0 !important;
}

.vacations-page__title-block {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.vacations-page__title {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: var(--color-main-text);
}

.vacations-page__subtitle {
  margin: 0;
  font-size: 14px;
  color: var(--color-text-maxcontrast);
}

.vacations-page__sidebar-actions {
  padding: 8px 16px;
  border-top: 1px solid var(--color-border);
}

.vacations-page__tabs-row {
  display: flex;
  justify-content: end;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  border-bottom: 1px solid var(--color-border);
}

.vacations-page__section-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--color-main-text);
}

/* Pushes the year to the far left while the actions stay on the right. */
.vacations-page__year {
  margin-right: auto;
  font-size: 18px;
  font-weight: 600;
  color: var(--color-main-text);
}

.vacations-page__actions {
  display: flex;
  gap: 8px;
}

.vacations-page__loader {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
}

.vacations-page__gantt {
  flex: 1;
  min-height: 0;
  height: 100%;
}

.vacations-page__action-sidebar {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 16px;
}

.vacations-page__action-sidebar-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

@media (max-width: 768px) {
  .vacations-page__header {
    flex-direction: column;
    gap: 16px;
  }

  .vacations-page__tabs-row {
    flex-direction: column;
    align-items: stretch;
  }

  .vacations-page__actions {
    justify-content: center;
  }
}
</style>
