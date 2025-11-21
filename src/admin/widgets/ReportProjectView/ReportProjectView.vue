/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div>
    <div
      v-for="(project, index) in modelData"
      :key="project.id"
      :class="{
        'p-4': true,
        'border-t border-(--color-border)': index > 0,
      }"
    >
      <!-- PROJECT -->
      <div
        v-if="isTitleVisible('project')"
        :class="{
          [classNames.row]: true,
          [classNames.sticky]: true,
          'top-0 z-100': true,
        }"
      >
        <ReportActionsProjectItem
          :model-data="{
            ...project,
            project_id: project.id,
            total: getTimeById(project.id),
          }"
          chip-variant="primary"
        >
          <template #actions>
            <VExpandIconButton
              :expanded="project.expanded"
              @on-click="() => handleExpandRow(project)"
            />
          </template>
        </ReportActionsProjectItem>
      </div>

      <!-- YEAR -->
      <template
        v-if="
          project.expanded === true &&
          project.children &&
          project.children.length > 0
        "
      >
        <div v-for="year in project.children" :key="year.id">
          <div
            v-if="isTitleVisible('year')"
            :class="{
              [classNames.row]: true,
              [classNames.sticky]: activeRangeType === 'year',
              'top-(--time-tracking-row-height) z-50':
                activeRangeType === 'year',
            }"
          >
            <VExpandIconButton
              :expanded="year.expanded"
              @on-click="() => handleExpandRow(year)"
            />
            <div class="text-base font-medium">
              {{ year.id }}
            </div>
            <VTimeChip :value="getTimeById(project.id, year.id)" />
          </div>

          <!-- QUARTAL -->
          <template
            v-if="
              year.expanded === true &&
              year.children &&
              year.children.length > 0
            "
          >
            <div v-for="quarter in year.children" :key="quarter.id">
              <div
                v-if="isTitleVisible('quarter')"
                :class="{
                  [classNames.row]: true,
                  [classNames.sticky]: activeRangeType === 'quarter',
                  'top-(--time-tracking-row-height) z-50':
                    activeRangeType === 'quarter',
                }"
              >
                <VExpandIconButton
                  :expanded="quarter.expanded"
                  @on-click="() => handleExpandRow(quarter)"
                />
                <div class="text-base font-medium">
                  {{ getQuarterTitle(quarter.id) }}
                </div>
                <VTimeChip
                  :value="getTimeById(project.id, year.id, quarter.id)"
                />
              </div>

              <!-- MONTHS -->
              <template
                v-if="
                  quarter.expanded === true &&
                  quarter.children &&
                  quarter.children.length > 0
                "
              >
                <div v-for="month in quarter.children" :key="month.id">
                  <div
                    v-if="isTitleVisible('month')"
                    :class="{
                      [classNames.row]: true,
                      [classNames.sticky]: [
                        'year',
                        'quarter',
                        'month',
                      ].includes(activeRangeType),
                      'top-(--time-tracking-row-height) z-50':
                        activeRangeType === 'month',
                      'top-[calc(var(--time-tracking-row-height)*2)] z-30': [
                        'year',
                        'quarter',
                      ].includes(activeRangeType),
                    }"
                  >
                    <VExpandIconButton
                      :expanded="month.expanded"
                      @on-click="() => handleExpandRow(month)"
                    />
                    <div class="text-base font-medium">
                      {{ getMonthTitle(month.id) }}
                    </div>
                    <VTimeChip
                      :value="
                        getTimeById(project.id, year.id, quarter.id, month.id)
                      "
                    />
                  </div>

                  <!-- WEEKS -->
                  <template
                    v-if="
                      month.expanded === true &&
                      month.children &&
                      month.children.length > 0
                    "
                  >
                    <div
                      v-for="week in month.children"
                      :key="week.id"
                      class="pl-12"
                    >
                      <div
                        v-if="isTitleVisible('week', week.isHiddenTitle)"
                        :class="{
                          [classNames.row]: true,
                          [classNames.sticky]: activeRangeType === 'week',
                          'top-(--time-tracking-row-height) z-50':
                            activeRangeType === 'week',
                        }"
                      >
                        <VExpandIconButton
                          :expanded="week.expanded"
                          @on-click="() => handleExpandRow(week)"
                        />
                        <div class="text-base font-medium">
                          {{ getWeekTitle(week.id) }}
                        </div>
                        <VTimeChip
                          :value="
                            getTimeById(project.id, year.id, 'week', week.id)
                          "
                        />
                      </div>

                      <!-- DAYS -->
                      <template
                        v-if="
                          week.expanded === true &&
                          week.children &&
                          week.children.length > 0
                        "
                      >
                        <div
                          v-for="day in week.children"
                          :key="day.id"
                          class="pl-12"
                        >
                          <div
                            v-if="isTitleVisible('day')"
                            :id="getDayId(day.id, month.id, year.id)"
                            :class="classNames.row"
                          >
                            <div class="w-[34px]">
                              <VExpandIconButton
                                v-if="day.children && day.children.length > 0"
                                :expanded="day.expanded"
                                @on-click="() => handleExpandRow(day)"
                              />
                            </div>
                            <div
                              :class="{
                                'text-base': true,
                                'text-(--color-placeholder-dark)': isWeekend(
                                  day.day_name
                                ),
                              }"
                            >
                              {{
                                getDayTitle(
                                  day.id,
                                  month.id,
                                  year.id,
                                  day.day_name
                                )
                              }}
                            </div>
                            <VTimeChip
                              :value="
                                getTimeById(
                                  project.id,
                                  year.id,
                                  quarter.id,
                                  month.id,
                                  week.id,
                                  day.id
                                )
                              "
                            />
                          </div>

                          <!-- USER -->
                          <template
                            v-if="
                              day.expanded === true &&
                              day.children &&
                              day.children.length > 0
                            "
                          >
                            <div
                              v-for="user in day.children"
                              :key="user.user_id"
                              class="pl-12"
                            >
                              <ReportActionsUserItem
                                :model-data="user"
                                :active-date="activeDate"
                                :active-range-type="activeRangeType"
                              >
                                <template #actions>
                                  <VExpandIconButton
                                    :expanded="user.expanded"
                                    @on-click="() => handleExpandRow(user)"
                                  />
                                </template>
                              </ReportActionsUserItem>

                              <!-- USER REPORTS -->
                              <ul
                                v-if="
                                  user.expanded === true &&
                                  user.reports &&
                                  user.reports.length > 0
                                "
                                class="flex flex-col gap-2"
                              >
                                <TimeTrackingItem
                                  v-for="report in user.reports"
                                  :key="report.id"
                                  :model-data="report"
                                  :disabled="true"
                                  :show-project="false"
                                />
                              </ul>
                              <!-- /USER REPORTS -->
                            </div>
                          </template>
                          <!-- /USER -->
                        </div>
                      </template>
                      <!-- /DAYS -->
                    </div>
                  </template>
                  <!-- /WEEKS -->
                </div>
              </template>
              <!-- /MONTHS -->
            </div>
          </template>
          <!-- /QUARTAL -->
        </div>
      </template>
      <!-- /YEAR -->

      <!-- END PROJECT -->
    </div>
  </div>
</template>

<script>
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcActions,
  NcActionButton,
  NcListItemIcon,
} from "@nextcloud/vue";

import ReportActionsUserItem from "@/admin/features/ReportActionsUserItem/ReportActionsUserItem.vue";
import ReportActionsProjectItem from "@/admin/features/ReportActionsProjectItem/ReportActionsProjectItem.vue";
import { TimeTrackingItem } from "@/common/features/TimeTrackingItem";

import VTimeChip from "@/common/shared/components/VTimeChip/VTimeChip.vue";
import VExpandIconButton from "@/common/shared/components/VExpandIconButton/VExpandIconButton.vue";

import { getJoinString, isEmptyValue } from "@/common/shared/lib/helpers";

import { MONTHS } from "@/common/shared/lib/constants";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "ReportProjectView",
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    NcListItemIcon,
    ReportActionsUserItem,
    ReportActionsProjectItem,
    TimeTrackingItem,
    VTimeChip,
    VExpandIconButton,
  },
  mixins: [contextualTranslationsMixin],
  data: () => ({
    context: "admin/projects",
    classNames: {
      row: "flex gap-4 items-center h-(--time-tracking-row-height) nowrap",
      sticky: "sticky left-0 w-full bg-(--color-main-background)",
    },
  }),
  props: {
    modelData: {
      type: Array,
      default: () => [],
    },
    totals: {
      type: [Object, Array],
      default: () => ({}),
    },
    showTitles: {
      type: Array,
      default: () => ["project", "year", "quarter", "month", "week", "day"],
    },
    activeRangeType: {
      type: String,
      default: "",
    },
    activeDate: {
      type: Date,
      default: null,
    },
  },
  methods: {
    t,
    handleExpandRow(row) {
      row.expanded = !row.expanded;
    },
    isEmptyValue(value) {
      return isEmptyValue(value);
    },
    isTitleVisible(periodType, isHiddenTitle) {
      if (isHiddenTitle === true) {
        return false;
      }

      return this.showTitles.includes(periodType) === true;
    },
    isWeekend(value) {
      const lowerCaseValue = value.toLowerCase();

      return (
        [t("done", "Saturday"), t("done", "Sunday")].includes(
          lowerCaseValue
        ) === true
      );
    },
    getQuarterTitle(value) {
      switch (value) {
        case "1":
          return t("done", "Q1");
        case "2":
          return t("done", "Q2");
        case "3":
          return t("done", "Q3");
        case "4":
          return t("done", "Q4");
      }
    },
    getMonthTitle(monthId) {
      const index = Number(monthId) - 1;

      return t("done", MONTHS[index]);
    },
    getWeekTitle(weekId) {
      const [_year, weekNumber] = weekId.split("_");

      return t("done", "{n} week").replace("{n}", weekNumber);
    },
    getDayTitle(dayId, monthId, yearId, day_name) {
      const dayName = day_name.toLowerCase();

      return `${dayId}.${monthId}.${yearId} ${dayName}`;
    },
    getDayId(dayId, monthId, yearId) {
      const date = [yearId, monthId, dayId].join("-");

      return `day-${date}`;
    },
    getTimeById(...rest) {
      const key = rest.join("-");
      const time = this.totals[key];

      return time || "";
    },
    getUserSubname(userRolesInProject) {
      if (!userRolesInProject || userRolesInProject.length === 0) {
        return "No role in project";
      }

      return getJoinString(userRolesInProject, ", ");
    },
  },
};
</script>
