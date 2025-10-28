<template>
  <div class="report-project-view">
    <div
      v-for="project in modelData"
      :key="project.id"
      class="report-project-view__project-block"
    >
      <!-- PROJECT -->
      <div
        v-if="isTitleVisible('project')"
        class="time-tracking-row time-tracking-row--lg time-tracking-row--sticky"
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
            :class="[
              'time-tracking-row time-tracking-row--md',
              activeRangeType === 'year' &&
                'time-tracking-row--sticky time-tracking-row--sticky-2',
            ]"
          >
            <VExpandIconButton
              :expanded="year.expanded"
              @on-click="() => handleExpandRow(year)"
            />
            <h2 class="time-tracking-title">
              {{ year.id }}
            </h2>
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
                :class="[
                  'time-tracking-row time-tracking-row--md',
                  activeRangeType === 'quarter' &&
                    'time-tracking-row--sticky time-tracking-row--sticky-2',
                ]"
              >
                <VExpandIconButton
                  :expanded="quarter.expanded"
                  @on-click="() => handleExpandRow(quarter)"
                />
                <h3 class="time-tracking-title">
                  {{ getQuarterTitle(quarter.id) }}
                </h3>
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
                    :class="[
                      'time-tracking-row time-tracking-row--md',
                      ['year', 'quarter', 'month'].includes(activeRangeType) ===
                        true &&
                        'time-tracking-row--sticky time-tracking-row--sticky-2',
                      ['year', 'quarter'].includes(activeRangeType) === true &&
                        'time-tracking-row--sticky-3',
                    ]"
                  >
                    <VExpandIconButton
                      :expanded="month.expanded"
                      @on-click="() => handleExpandRow(month)"
                    />
                    <h4 class="time-tracking-title">
                      {{ getMonthTitle(month.id) }}
                    </h4>
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
                      class="report-project-view__sub"
                    >
                      <div
                        v-if="isTitleVisible('week', week.isHiddenTitle)"
                        :class="[
                          'time-tracking-row time-tracking-row--bold',
                          activeRangeType === 'week' &&
                            'time-tracking-row--sticky time-tracking-row--sticky-2',
                        ]"
                      >
                        <VExpandIconButton
                          :expanded="week.expanded"
                          @on-click="() => handleExpandRow(week)"
                        />
                        <h4 class="time-tracking-title">
                          {{ getWeekTitle(week.id) }}
                        </h4>
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
                          class="report-project-view__sub"
                        >
                          <div
                            v-if="isTitleVisible('day')"
                            :id="getDayId(day.id, month.id, year.id)"
                            class="time-tracking-row"
                          >
                            <div class="report-project-view-expand">
                              <VExpandIconButton
                                v-if="day.children && day.children.length > 0"
                                :expanded="day.expanded"
                                @on-click="() => handleExpandRow(day)"
                              />
                            </div>
                            <div
                              :class="[
                                'time-tracking-title',
                                isWeekend(day.day_name) &&
                                  'time-tracking-title--muted',
                              ]"
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
                              class="report-project-view__sub"
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
                                class="time-tracking-list"
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
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

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
    context: 'admin/projects',
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
      
      return [t('done', 'Saturday'), t('done', 'Sunday')].includes(lowerCaseValue) === true;
    },
    getQuarterTitle(value) {
      switch (value) {
        case "1":
          return t('done', 'Q1');
        case "2":
          return t('done', 'Q2');
        case "3":
          return t('done', 'Q3');
        case "4":
          return t('done', 'Q4');
      }
    },
    getMonthTitle(monthId) {
      const index = Number(monthId) - 1;

      return t('done', MONTHS[index]);
    },
    getWeekTitle(weekId) {
      const [_year, weekNumber] = weekId.split("_");
      
      return t('done', '{n} week').replace('{n}', weekNumber);
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

<style scoped>
.report-project-view__project-block {
  padding: 16px;
}

.report-project-view__project-block + .report-project-view__project-block {
  border-top: 1px solid var(--color-border);
}

.report-project-view__sub {
  padding-left: 50px;
}

.report-project-view-expand {
  width: 34px;
}

.report-project-view-user {
  display: flex;
  gap: 16px;
  align-items: center;
}

.report-project-view-user__time {
  margin-top: 4px;
  align-self: flex-start;
}

.report-project-view-user-list {
  padding-left: 50px;
}
</style>
