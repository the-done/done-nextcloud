/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage data-component-id="VacationsReportPage">
    <VLoader v-if="isInitLoading" absolute />

    <DynamicTable v-else :source="source" @on-fetch="handleFetchData">
      <!-- Breadcrumbs and the year switcher sit on the same line as the table
           settings button (rendered in DynamicTable's VToolbar #actions slot). -->
      <template #toolbar-left>
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
          <NcBreadcrumb :name="contextTranslate('Vacations report', context)" />
        </NcBreadcrumbs>
      </template>

      <template #toolbar-controls>
        <!--
          Compact year switcher. Uses timeTrackingPageMixin methods
          (handleUpdateActiveDate triggers fetchDataWithFilters → handleFetchData).
        -->
        <div class="vacations-report__year-switch">
          <NcButton
            :aria-label="contextTranslate('Previous year', context)"
            @click="() => shiftYear(-1)"
          >
            <template #icon>
              <ChevronLeft :size="20" />
            </template>
          </NcButton>
          <span class="vacations-report__year-label">
            {{ activeDate.getFullYear() }}
          </span>
          <NcButton
            :aria-label="contextTranslate('Next year', context)"
            @click="() => shiftYear(1)"
          >
            <template #icon>
              <ChevronRight :size="20" />
            </template>
          </NcButton>
        </div>
        <!-- Excel/CSV export of the report for the selected year. -->
        <ExportButton
          :source="source"
          context-name="vacations"
          :filters="{ year: activeDate.getFullYear() }"
        />
      </template>

      <template #controls="{ row }">
        <NcActions :inline="1">
          <NcActionButton
            :force-name="true"
            :aria-label="contextTranslate('Details', context)"
            @click="() => openEmployeeDetails(row)"
          >
            <template #icon>
              <EyeOutline />
            </template>
            {{ contextTranslate("Details", context) }}
          </NcActionButton>
        </NcActions>
      </template>

      <!-- Custom cell renderers (multiline cells + status badge). -->
      <!-- The full name is a real link (router-link → <a href>) so it can be
           opened in a new tab. row.id is the user id; the staff card resolves
           the slug→id via fallback. -->
      <template #user_name="{ row, value }">
        <span class="vacations-report__user">
          <router-link
            class="vacations-report__user-fio vacations-report__link"
            :to="{ name: 'staff-preview', params: { slug: row.id } }"
          >
            {{ splitLines(value)[0] }}
          </router-link>
          <span
            v-if="splitLines(value)[1]"
            class="vacations-report__user-position"
          >
            {{ splitLines(value).slice(1).join(", ") }}
          </span>
        </span>
      </template>
      <!-- Each project badge is a real link to its project card (id resolved by
           name). Projects with no resolvable id render as plain badges. -->
      <template #projects="{ value }">
        <div class="vacations-report__badges">
          <template v-for="(project, idx) in splitLines(value)">
            <router-link
              v-if="projectIdByName[project]"
              :key="idx"
              class="vacations-report__badge vacations-report__badge--link"
              :to="{ name: 'project-preview', params: { slug: projectIdByName[project] } }"
            >
              {{ project }}
            </router-link>
            <span v-else :key="idx" class="vacations-report__badge">
              {{ project }}
            </span>
          </template>
        </div>
      </template>
      <template #nearest_vacation="{ value }">
        <span class="vacations-report__cell-multiline">{{ value }}</span>
      </template>
      <template #mandatory_status="{ value }">
        <span
          v-if="value"
          class="vacations-report__mandatory-cell"
          :class="`vacations-report__mandatory-cell--${mandatoryStatusClass(value)}`"
        >
          {{ value }}
        </span>
      </template>
    </DynamicTable>

    <!-- "Details": all vacations of the employee, newest to oldest. -->
    <VAside :value="detailsOpen" :width="540" @input="closeEmployeeDetails">
      <template #title>
        {{ selectedEmployeeName || contextTranslate("Employee vacations", context) }}
      </template>

      <!--
        Balance is a separate sticky bar under the name. It is the first child
        of the scroll container and pinned with position: sticky; top: 0, so it
        stays in place while the vacation list scrolls.
      -->
      <div
        v-if="detailsBalance.length"
        class="vacations-report__balance-bar"
      >
        <span
          v-for="b in detailsBalance"
          :key="b.type_id"
          class="vacations-report__balance-chip"
        >
          <span
            class="vacations-report__balance-dot"
            :style="{ backgroundColor: b.type_color }"
          />
          <span class="vacations-report__balance-name">{{ b.type_name }}:</span>
          <span class="vacations-report__balance-value">{{ b.summary }}</span>
        </span>
      </div>

      <div class="vacations-report__details">
        <VLoader v-if="detailsLoading" />

        <div
          v-else-if="!detailsList.length"
          class="vacations-report__details-empty"
        >
          {{ contextTranslate("No vacations found for this employee.", context) }}
        </div>

        <ul v-else class="vacations-report__details-list">
          <li
            v-for="item in detailsList"
            :key="item.id"
            class="vacations-report__details-item"
          >
            <div class="vacations-report__details-row">
              <span class="vacations-report__details-dates">
                {{ formatDate(item.date_start) }} — {{ formatDate(item.date_end) }}
              </span>
              <span
                class="vacations-report__details-status"
                :class="`vacations-report__details-status--${item.status_id}`"
              >
                {{ statusLabel(item.status_id) }}
              </span>
            </div>
            <div class="vacations-report__details-meta">
              <span>{{ item.type_name }}</span>
              <span>·</span>
              <span>{{ item.days_count }} {{ contextTranslate("days", context) }}</span>
              <template v-if="item.project_name">
                <span>·</span>
                <span>{{ item.project_name }}</span>
              </template>
            </div>
            <div v-if="item.comment" class="vacations-report__details-comment">
              {{ item.comment }}
            </div>
          </li>
        </ul>
      </div>
    </VAside>
  </VPage>
</template>

<script>
import { format, parseISO } from "date-fns";
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcActions,
  NcActionButton,
  NcButton,
} from "@nextcloud/vue";

import FileChart from "vue-material-design-icons/FileChart.vue";
import EyeOutline from "vue-material-design-icons/EyeOutline.vue";
import ChevronLeft from "vue-material-design-icons/ChevronLeft.vue";
import ChevronRight from "vue-material-design-icons/ChevronRight.vue";

import { VPage, DynamicTable, ExportButton } from "@/widgets";
import { VAside, VLoader } from "@/shared/components";

import {
  fetchVacationsReportTableData,
  fetchEmployeeVacationsList,
  fetchRemainingDays,
  fetchVacationReportSeparator,
  fetchProjectsOptionsForVacationsReport,
} from "@/entities/vacations/api";

import { dynamicTableMixin } from "@/shared/lib/mixins/dynamicTableMixin";
import { timeTrackingPageMixin } from "@/shared/lib/mixins/timeTrackingPageMixin";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { MAP_DYNAMIC_TABLE_SOURCES } from "@/entities/dynamicTables/constants";
import { LOCALSTORAGE_REPORT_VACATIONS_RANGE_TYPE } from "@/shared/lib/constants/localStorage";

export default {
  name: "VacationsReportPage",
  mixins: [timeTrackingPageMixin, dynamicTableMixin, contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    NcButton,
    FileChart,
    EyeOutline,
    ChevronLeft,
    ChevronRight,
    VPage,
    DynamicTable,
    ExportButton,
    VAside,
    VLoader,
  },
  data() {
    return {
      context: "vacations",
      isInitLoading: true,
      isLoading: false,
      // Defaults to the current year; the mixin overrides it from query/localStorage.
      activeRangeType: "year",
      localStorageActiveRangeTypeKey: LOCALSTORAGE_REPORT_VACATIONS_RANGE_TYPE,
      // No extra side filters — DynamicTable has its own built-in ones.
      filterDescriptor: [],

      // "Used / limit" separator from settings (defaults to "/").
      reportSeparator: "/",

      // Project name → id map for linking project badges to their cards.
      projectIdByName: {},

      // "Details" side panel
      detailsOpen: false,
      detailsLoading: false,
      detailsList: [],
      detailsBalance: [],
      selectedEmployeeName: "",
    };
  },
  computed: {
    source() {
      return MAP_DYNAMIC_TABLE_SOURCES["vacationReport"];
    },
  },
  methods: {
    // Splits a multiline value (the backend joins with "\n") into an array of
    // non-empty lines — used for rendering badges in the projects column.
    splitLines(value) {
      if (!value) return [];
      return String(value)
        .split("\n")
        .map((s) => s.trim())
        .filter(Boolean);
    },
    // Shifts the year forward/backward via the mixin's handleUpdateActiveDate,
    // which in turn triggers the data reload.
    shiftYear(delta) {
      const next = new Date(this.activeDate);
      next.setFullYear(next.getFullYear() + delta);
      this.handleUpdateActiveDate(next);
    },
    // handleFetchData has a single contract for two callers:
    //  - timeTrackingPageMixin (on date/range/filter change — with payload)
    //  - DynamicTable on-fetch (on sort/filter/column-visibility change — no payload)
    // The year is always taken from activeDate.
    async handleFetchData() {
      try {
        this.setTableLoading(true);
        const year = this.activeDate.getFullYear();
        const { data } = await fetchVacationsReportTableData({ year });
        this.initDynamicTable(data);
      } catch (e) {
        console.error("Failed to load vacations report:", e);
      } finally {
        this.setTableLoading(false);
      }
    },
    // The backend stores the localized label as the mandatory_status value (so
    // the Excel/CSV export shows human text). Map the label back to a CSS
    // modifier for the badge color, matching against the same translated keys.
    mandatoryStatusClass(value) {
      const byLabel = {
        [this.contextTranslate("Mandatory leave taken", this.context)]: "completed",
        [this.contextTranslate("Mandatory leave planned", this.context)]: "planned",
        [this.contextTranslate("Mandatory leave not planned", this.context)]: "not_planned",
      };
      return byLabel[value] || "not_planned";
    },
    statusLabel(statusId) {
      const labels = {
        pending: this.contextTranslate("Pending", this.context),
        approved: this.contextTranslate("Approved", this.context),
        rejected: this.contextTranslate("Rejected", this.context),
        returned: this.contextTranslate("Returned", this.context),
        canceled: this.contextTranslate("Canceled", this.context),
      };
      return labels[statusId] || statusId;
    },
    formatDate(iso) {
      try {
        return format(parseISO(String(iso).slice(0, 10)), "dd.MM.yyyy");
      } catch (e) {
        return iso;
      }
    },
    async openEmployeeDetails(row) {
      // row.id is the internal Done user id; the first user_name line is the full name.
      this.selectedEmployeeName = String(row.user_name || "").split("\n")[0] || "";
      this.detailsOpen = true;
      this.detailsLoading = true;
      this.detailsList = [];
      this.detailsBalance = [];

      const year = this.activeDate.getFullYear();

      try {
        const [listResult, balanceResult] = await Promise.all([
          fetchEmployeeVacationsList(row.id),
          fetchRemainingDays({ user_id: row.id, year }),
        ]);
        this.detailsList = listResult.data || [];
        this.detailsBalance = this.buildBalanceSummary(balanceResult.data);
      } catch (e) {
        console.error("Failed to load employee details:", e);
        this.detailsList = [];
        this.detailsBalance = [];
      } finally {
        this.detailsLoading = false;
      }
    },
    // Turns the getRemainingDays response (object keyed by type_id) into a
    // compact list of balance chips. Only types with a limit or usage are shown.
    buildBalanceSummary(balanceByType) {
      if (!balanceByType || typeof balanceByType !== "object") return [];
      return Object.values(balanceByType)
        .filter((b) => Number(b.limit) > 0 || Number(b.used) > 0)
        .map((b) => {
          const used = Number(b.used) || 0;
          const limit = Number(b.limit) || 0;
          return {
            type_id: b.type_id,
            type_name: b.type_name,
            type_color: b.type_color || "#9E9E9E",
            summary: limit > 0 ? `${used} ${this.reportSeparator} ${limit}` : String(used),
          };
        });
    },
    closeEmployeeDetails() {
      this.detailsOpen = false;
      this.detailsList = [];
      this.detailsBalance = [];
      this.selectedEmployeeName = "";
    },
    async loadSeparator() {
      try {
        const { data } = await fetchVacationReportSeparator();
        this.reportSeparator = data?.separator || "/";
      } catch (e) {
        console.error("Failed to load report separator:", e);
      }
    },
    // Builds the project name → id map used to link project badges to their
    // cards (the report cell only carries project names).
    async loadProjectsMap() {
      try {
        const { data } = await fetchProjectsOptionsForVacationsReport();
        const map = {};
        (data || []).forEach((p) => {
          if (p?.name && p?.id) {
            map[p.name] = p.id;
          }
        });
        this.projectIdByName = map;
      } catch (e) {
        console.error("Failed to load projects map:", e);
        this.projectIdByName = {};
      }
    },
  },
  mounted() {
    // timeTrackingPageMixin.init() sets the date from query/localStorage and calls handleFetchData.
    this.init();
    this.loadSeparator();
    this.loadProjectsMap();
  },
};
</script>

<style scoped>
.vacations-report__year-switch {
  display: flex;
  align-items: center;
  gap: 6px;
}

.vacations-report__year-label {
  min-width: 64px;
  text-align: center;
  font-weight: 600;
  font-size: 14px;
  color: var(--color-main-text);
}

.vacations-report__cell-multiline {
  white-space: pre-line;
  display: block;
  line-height: 1.35;
}

.vacations-report__balance-bar {
  position: sticky;
  top: 0;
  z-index: 1;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  padding: 10px 16px;
  background-color: var(--color-main-background);
  border-bottom: 1px solid var(--color-border);
}

.vacations-report__balance-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 12px;
  background-color: var(--color-background-dark);
  font-size: 12px;
  font-weight: 400;
  line-height: 1.4;
  white-space: nowrap;
}

.vacations-report__balance-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.vacations-report__balance-name {
  color: var(--color-text-maxcontrast);
}

.vacations-report__balance-value {
  font-weight: 600;
  color: var(--color-main-text);
}

.vacations-report__user {
  display: flex;
  flex-direction: column;
  line-height: 1.35;
}

.vacations-report__user-fio {
  font-weight: 700;
}

.vacations-report__link {
  cursor: pointer;
  color: var(--color-main-text);
  text-decoration: none;
}

.vacations-report__link:hover {
  color: var(--color-primary-element);
  text-decoration: underline;
}

.vacations-report__badge--link {
  cursor: pointer;
  color: var(--color-main-text);
  text-decoration: none;
}

.vacations-report__badge--link:hover {
  background-color: var(--color-primary-element);
  color: var(--color-primary-element-text);
}

.vacations-report__user-position {
  font-style: italic;
  color: var(--color-text-maxcontrast);
}

.vacations-report__badges {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.vacations-report__badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 12px;
  background-color: var(--color-background-dark);
  color: var(--color-main-text);
  font-size: 12px;
  line-height: 1.4;
  white-space: nowrap;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
}

.vacations-report__mandatory-cell {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.vacations-report__mandatory-cell--completed {
  background-color: rgba(76, 175, 80, 0.18);
  color: #2e7d32;
}

.vacations-report__mandatory-cell--planned {
  background-color: rgba(33, 150, 243, 0.18);
  color: #1565c0;
}

.vacations-report__mandatory-cell--not_planned {
  background-color: rgba(255, 152, 0, 0.18);
  color: #e65100;
}

.vacations-report__details {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.vacations-report__details-empty {
  text-align: center;
  color: var(--color-text-maxcontrast);
  padding: 24px 0;
}

.vacations-report__details-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.vacations-report__details-item {
  border: 1px solid var(--color-border);
  border-radius: 6px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.vacations-report__details-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.vacations-report__details-dates {
  font-weight: 600;
  color: var(--color-main-text);
}

.vacations-report__details-status {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 12px;
  background-color: var(--color-background-dark);
  color: var(--color-text-maxcontrast);
}

.vacations-report__details-status--approved {
  background-color: rgba(76, 175, 80, 0.18);
  color: #2e7d32;
}

.vacations-report__details-status--pending {
  background-color: rgba(255, 193, 7, 0.22);
  color: #ad6500;
}

.vacations-report__details-status--rejected {
  background-color: rgba(244, 67, 54, 0.18);
  color: #c62828;
}

.vacations-report__details-status--returned {
  background-color: rgba(33, 150, 243, 0.18);
  color: #1565c0;
}

.vacations-report__details-status--canceled {
  background-color: rgba(158, 158, 158, 0.18);
  color: #424242;
}

.vacations-report__details-meta {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  color: var(--color-text-maxcontrast);
  font-size: 13px;
}

.vacations-report__details-comment {
  margin-top: 6px;
  font-style: italic;
  color: var(--color-text-maxcontrast);
}
</style>
