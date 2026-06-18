/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div class="vacation-table">
    <!-- Header with count -->
    <div class="vacation-table__header">
      <h3 class="vacation-table__title">
        {{ contextTranslate("Request list", context) }}
      </h3>
      <span class="vacation-table__count">
        {{ contextTranslate("Showing {n1} of {n2} requests", context, { n1: totalCount, n2: totalCount }) }}
      </span>
    </div>

    <!-- Empty state -->
    <div v-if="!hasData" class="vacation-table__empty">
      <VEmptyState
        :title="contextTranslate('No data to display', context)"
        :description="contextTranslate('Try changing your filter settings', context)"
      />
    </div>

    <!-- Data grouped by months -->
    <div v-else class="vacation-table__content">
      <div
        v-for="monthKey in sortedMonths"
        :key="monthKey"
        class="vacation-table__month"
      >
        <!-- Month header -->
        <h4 class="vacation-table__month-title">
          {{ contextTranslate(getMonthKey(monthKey), context) }} {{ currentYear }}
        </h4>

        <!-- Table -->
        <div class="vacation-table__wrapper">
          <table class="v-table">
            <colgroup>
              <col class="vacation-table__col--employee" />
              <col class="vacation-table__col--project" />
              <col class="vacation-table__col--type" />
              <col class="vacation-table__col--dates" />
              <col class="vacation-table__col--days" />
              <col class="vacation-table__col--status" />
              <col class="vacation-table__col--actions" />
            </colgroup>
            <thead class="v-table-header">
              <tr>
                <th class="v-table-head">
                  <div class="v-table-head__content">
                    <span class="v-table-head__label">
                      {{ contextTranslate("Employee", context) }}
                    </span>
                  </div>
                </th>
                <th class="v-table-head">
                  <div class="v-table-head__content">
                    <span class="v-table-head__label">
                      {{ contextTranslate("Project", context) }}
                    </span>
                  </div>
                </th>
                <th class="v-table-head">
                  <div class="v-table-head__content">
                    <span class="v-table-head__label">
                      {{ contextTranslate("Type", context) }}
                    </span>
                  </div>
                </th>
                <th class="v-table-head">
                  <div class="v-table-head__content">
                    <span class="v-table-head__label">
                      {{ contextTranslate("Dates", context) }}
                    </span>
                  </div>
                </th>
                <th class="v-table-head">
                  <div class="v-table-head__content">
                    <span class="v-table-head__label">
                      {{ contextTranslate("Days", context) }}
                    </span>
                  </div>
                </th>
                <th class="v-table-head">
                  <div class="v-table-head__content">
                    <span class="v-table-head__label">
                      {{ contextTranslate("Status", context) }}
                    </span>
                  </div>
                </th>
                <th class="v-table-head">
                  <div class="v-table-head__content">
                    <span class="v-table-head__label">
                      {{ contextTranslate("Actions", context) }}
                    </span>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody
              v-for="employee in getMonthEmployees(monthKey)"
              :key="`${monthKey}-${employee.id}`"
              class="v-table-body vacation-table__employee-group"
            >
              <tr
                v-for="(vacation, vacationIndex) in employee.vacations"
                :key="vacation.id"
                class="v-table-row"
              >
                <td
                  v-if="vacationIndex === 0"
                  class="v-table-col vacation-table__employee-cell"
                  :rowspan="employee.vacations.length"
                >
                  <span class="vacation-table__employee-name">
                    {{ employee.name }}
                  </span>
                </td>
                <td class="v-table-col">
                  {{ getProjectName(vacation.project_id) }}
                </td>
                <td class="v-table-col">
                  <VacationTypeBadge
                    :name="vacation.type_name"
                    :color="vacation.type_color"
                  />
                </td>
                <td class="v-table-col">
                  {{ formatDateRange(vacation.date_start, vacation.date_end) }}
                </td>
                <td class="v-table-col">
                  {{ vacation.days_count }}
                </td>
                <td class="v-table-col">
                  <VacationStatusBadge :status-id="vacation.status_id" />
                </td>
                <td class="v-table-col">
                  <div class="vacation-table__actions">
                    <template v-if="canEdit(vacation)">
                      <NcButton
                        type="tertiary"
                        class="vacation-table__action--edit"
                        :aria-label="contextTranslate('Edit', context)"
                        @click="$emit('edit', vacation)"
                      >
                        <template #icon>
                          <Pencil :size="20" />
                        </template>
                      </NcButton>
                    </template>
                    <template v-if="canCancel(vacation)">
                      <NcButton
                        type="tertiary"
                        :aria-label="contextTranslate('Cancel request', context)"
                        :title="contextTranslate('Cancel request', context)"
                        @click="$emit('cancel', vacation)"
                      >
                        <template #icon>
                          <CloseCircleOutline :size="20" />
                        </template>
                      </NcButton>
                    </template>
                    <NcButton
                      type="tertiary"
                      @click="$emit('view', vacation)"
                      :aria-label="contextTranslate('View details', context)"
                    >
                      <template #icon>
                        <Eye :size="20" />
                      </template>
                    </NcButton>
                    <NcButton
                      type="tertiary"
                      @click="$emit('history', vacation)"
                      :aria-label="contextTranslate('View history', context)"
                    >
                      <template #icon>
                        <History :size="20" />
                      </template>
                    </NcButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { NcButton, NcActions, NcActionButton } from "@nextcloud/vue";
import Eye from "vue-material-design-icons/Eye.vue";
import History from "vue-material-design-icons/History.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";
import CloseCircleOutline from "vue-material-design-icons/CloseCircleOutline.vue";

import { VEmptyState } from "@/shared/components";
import { VacationStatusBadge } from "../VacationStatusBadge";
import { VacationTypeBadge } from "../VacationTypeBadge";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import {
  getMonthKey,
  formatDateDisplay,
  isApprovedStatus,
  canBeProcessed,
  VACATION_STATUS,
} from "@/entities/vacations/constants";

export default {
  name: "VacationTable",
  components: {
    NcButton,
    NcActions,
    NcActionButton,
    Eye,
    History,
    Pencil,
    CloseCircleOutline,
    VEmptyState,
    VacationStatusBadge,
    VacationTypeBadge,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    data: {
      type: Object,
      default: () => ({}),
    },
    projects: {
      type: Array,
      default: () => [],
    },
    currentUserId: {
      type: String,
      default: null,
    },
  },
  emits: ["view", "history", "edit", "cancel"],
  data: () => ({
    context: "vacations",
  }),
  computed: {
    hasData() {
      return Object.keys(this.data).length > 0;
    },
    sortedMonths() {
      return Object.keys(this.data).sort((a, b) => parseInt(a, 10) - parseInt(b, 10));
    },
    currentYear() {
      return new Date().getFullYear();
    },
    totalCount() {
      let count = 0;
      for (const monthKey of Object.keys(this.data)) {
        for (const employeeId of Object.keys(this.data[monthKey])) {
          count += this.data[monthKey][employeeId].list?.length || 0;
        }
      }
      return count;
    },
  },
  methods: {
    getMonthKey,
    formatDate(dateString) {
      return formatDateDisplay(dateString);
    },
    formatDateRange(dateStart, dateEnd) {
      const start = formatDateDisplay(dateStart);
      const end = formatDateDisplay(dateEnd);
      return `${start} — ${end}`;
    },
    isApproved(statusId) {
      return isApprovedStatus(statusId);
    },
    isRejected(statusId) {
      return statusId === VACATION_STATUS.REJECTED;
    },
    getProjectName(projectId) {
      if (!projectId) return "-";
      const project = this.projects.find(p => p.id === projectId);
      return project?.name || projectId;
    },
    canEdit(vacation) {
      // Owner is either the absent employee or the user who submitted the request.
      const isOwner = vacation.user_id === this.currentUserId
        || vacation.created_by === this.currentUserId;

      if (!isOwner) {
        return false;
      }

      // Editable while in flight (pending / returned-for-revision) and also when
      // already approved — saving an approved request restarts the approval flow.
      return canBeProcessed(vacation.status_id) || this.isApproved(vacation.status_id);
    },
    canCancel(vacation) {
      const isOwner = vacation.user_id === this.currentUserId
        || vacation.created_by === this.currentUserId;

      if (!isOwner) {
        return false;
      }

      // Cancel makes sense only for in-flight requests. Already-final statuses
      // (approved/rejected/canceled) cannot be canceled by the owner.
      return canBeProcessed(vacation.status_id);
    },
    getActionLabel(vacation) {
      if (this.isApproved(vacation.status_id)) {
        return this.contextTranslate("Approved", this.context);
      }
      if (this.isRejected(vacation.status_id)) {
        return this.contextTranslate("Rejected", this.context);
      }
      return "";
    },
    getMonthEmployees(monthKey) {
      const monthData = this.data[monthKey];
      if (!monthData) return [];

      const employees = [];
      for (const employeeId of Object.keys(monthData)) {
        const employeeData = monthData[employeeId];
        if (employeeData.list && employeeData.list.length > 0) {
          // Sort vacations by date_start
          const sortedVacations = [...employeeData.list].sort((a, b) => {
            const dateA = a.date_start?.split('.').reverse().join('-') || '';
            const dateB = b.date_start?.split('.').reverse().join('-') || '';
            return dateA.localeCompare(dateB);
          });

          employees.push({
            id: employeeId,
            name: employeeData.name,
            vacations: sortedVacations,
          });
        }
      }

      // Sort employees by name
      employees.sort((a, b) => a.name.localeCompare(b.name));

      return employees;
    },
  },
};
</script>

<style scoped>
.vacation-table {
  background-color: var(--color-main-background);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.vacation-table__header {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--color-border);
}

.vacation-table__title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--color-main-text);
}

.vacation-table__count {
  font-size: 13px;
  color: var(--color-text-maxcontrast);
}

.vacation-table__empty {
  padding: 48px 24px;
  text-align: center;
}

.vacation-table__content {
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  min-height: 0;
  flex: 1;
}

.vacation-table__month {
  border-bottom: 1px solid var(--color-border);
}

.vacation-table__month:last-child {
  border-bottom: none;
}

.vacation-table__month-title {
  margin: 0;
  padding: 12px 20px;
  font-size: 14px;
  font-weight: 600;
  color: var(--color-main-text);
  background-color: var(--color-background-dark);
}

.vacation-table__wrapper {
  overflow-x: auto;
}

.vacation-table__wrapper .v-table {
  table-layout: fixed;
  width: 100%;
  min-width: 800px;
}

.vacation-table__wrapper .v-table-col {
  padding: 8px 12px;
  word-wrap: break-word;
  overflow-wrap: break-word;
  white-space: normal;
}

/* Column widths in percentages for consistent layout */
.vacation-table__col--employee {
  width: 16%;
}

.vacation-table__col--project {
  width: 18%;
}

.vacation-table__col--type {
  width: 14%;
}

.vacation-table__col--dates {
  width: 18%;
}

.vacation-table__col--days {
  width: 8%;
}

.vacation-table__col--status {
  width: 12%;
}

.vacation-table__col--actions {
  width: 14%;
}

.vacation-table__employee-group {
  border-bottom: 1px solid var(--color-border);
}

.vacation-table__employee-group:last-child {
  border-bottom: none;
}

.vacation-table__employee-cell {
  vertical-align: top;
  background-color: var(--color-background-hover);
  white-space: normal;
  overflow: visible;
}

.vacation-table__employee-name {
  font-weight: 500;
  word-wrap: break-word;
  overflow-wrap: break-word;
}

.vacation-table__actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.vacation-table__approved-by {
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  white-space: nowrap;
}

.vacation-table__action--edit {
  color: var(--color-primary-element);
}

.vacation-table__action--edit:hover {
  background-color: rgba(0, 130, 201, 0.1);
}
</style>