/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div class="time-tracking-view">
    <!-- YEAR -->
    <div v-for="{ id: yearId, children: quarters } in modelData" :key="yearId">
      <div
        v-if="isTitleVisible('year')"
        :class="[
          'time-tracking-row time-tracking-row--lg',
          activeRangeType === 'year' && 'time-tracking-row--sticky',
        ]"
      >
        <h2 class="time-tracking-title">
          {{ yearId }}
        </h2>
        <VTimeChip
          :value="getHoursById(yearId)"
          :variant="activeRangeType === 'year' ? 'primary' : ''"
        />
      </div>

      <!-- QUARTER -->
      <template v-if="quarters && quarters.length > 0">
        <div
          v-for="{ id: quarterId, children: months } in quarters"
          :key="quarterId"
        >
          <div
            v-if="isTitleVisible('quarter')"
            :class="[
              'time-tracking-row time-tracking-row--md',
              activeRangeType === 'quarter' && 'time-tracking-row--sticky',
            ]"
          >
            <h3 class="time-tracking-title">
              {{ getQuarterTitle(quarterId) }}
            </h3>
            <VTimeChip
              :value="getHoursById(yearId, quarterId)"
              :variant="activeRangeType === 'quarter' ? 'primary' : ''"
            />
          </div>

          <!-- MONTHS -->
          <template v-if="months && months.length > 0">
            <div
              v-for="{ id: monthId, children: weeks } in months"
              :key="monthId"
            >
              <div
                v-if="isTitleVisible('month')"
                :class="[
                  'time-tracking-row time-tracking-row--md',
                  ['year', 'quarter', 'month'].includes(activeRangeType) ===
                    true && 'time-tracking-row--sticky',
                  ['year', 'quarter'].includes(activeRangeType) === true &&
                    'time-tracking-row--sticky-2',
                ]"
              >
                <h4 class="time-tracking-title">
                  {{ getMonthTitle(monthId) }}
                </h4>
                <VTimeChip
                  :value="getHoursById(yearId, quarterId, monthId)"
                  :variant="activeRangeType === 'month' ? 'primary' : ''"
                />
              </div>

              <!-- WEEKS -->
              <template v-if="weeks && weeks.length > 0">
                <div
                  v-for="{ id: weekId, children: days, isHiddenTitle } in weeks"
                  :key="weekId"
                >
                  <div
                    v-if="isTitleVisible('week', isHiddenTitle)"
                    :class="[
                      'time-tracking-row time-tracking-row--bold',
                      activeRangeType === 'week' && 'time-tracking-row--sticky',
                    ]"
                  >
                    <h4 class="time-tracking-title">
                      {{ getWeekTitle(weekId) }}
                    </h4>
                    <VTimeChip
                      :value="getHoursById(yearId, 'week', weekId)"
                      :variant="activeRangeType === 'week' ? 'primary' : ''"
                    />
                  </div>

                  <!-- DAYS -->
                  <template v-if="days && days.length > 0">
                    <div v-for="day in days" :key="day.id">
                      <div
                        :id="getDayId(day.id, monthId, yearId)"
                        class="time-tracking-row"
                      >
                        <div
                          v-if="isTitleVisible('day')"
                          :class="[
                            'time-tracking-title',
                            dayDisabled === false &&
                              'time-tracking-title--hover',
                            isWeekend(day.day_name) &&
                              'time-tracking-title--muted',
                          ]"
                          @click="handleClickDay(day.id, monthId, yearId)"
                        >
                          {{
                            getDayTitle(day.id, monthId, yearId, day.day_name)
                          }}
                        </div>
                        <VTimeChip
                          :value="
                            getHoursById(
                              yearId,
                              quarterId,
                              monthId,
                              weekId,
                              day.id
                            )
                          "
                        />
                      </div>
                      <Draggable
                        v-if="taskDraggable"
                        v-model="day.children"
                        :animation="150"
                        handle="[data-handle]"
                        ghost-class="opacity-0"
                        class="time-tracking-list"
                        @start="handleDragStart"
                        @end="
                          (event) =>
                            handleDragEnd({
                              event,
                              list: day.children,
                            })
                        "
                      >
                        <TimeTrackingItem
                          v-for="item in day.children"
                          :key="item.id"
                          :model-data="item"
                          :disabled="taskDisabled"
                          :draggable="taskDraggable"
                          @onCopy="handleCopy"
                          @onDelete="handleDelete"
                        />
                      </Draggable>
                      <div v-else class="time-tracking-list">
                        <TimeTrackingItem
                          v-for="item in day.children"
                          :key="item.id"
                          :model-data="item"
                          :disabled="taskDisabled"
                          :draggable="taskDraggable"
                          @onCopy="handleCopy"
                          @onDelete="handleDelete"
                        />
                      </div>
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
      <!-- /QUARTER -->
    </div>
    <!-- /YEAR -->
  </div>
</template>

<script>
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcActions,
  NcActionButton,
} from "@nextcloud/vue";
import Draggable from "vuedraggable";

import { TimeTrackingItem } from "@/common/features/TimeTrackingItem";

import VTimeChip from "@/common/shared/components/VTimeChip/VTimeChip.vue";

import { minutesToHours, isEmptyValue } from "@/common/shared/lib/helpers";

import { MONTHS } from "@/common/shared/lib/constants";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "TimeTrackingView",
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    Draggable,
    TimeTrackingItem,
    VTimeChip,
  },
  emits: ["onClickDate", "onCopy", "onDelete", "onDragEnd"],
  mixins: [contextualTranslationsMixin],
  props: {
    modelData: {
      type: Array,
      default: () => [],
    },
    totals: {
      type: [Object, Array],
      default: () => ({}),
    },
    dayDisabled: {
      type: Boolean,
      default: false,
    },
    taskDisabled: {
      type: Boolean,
      default: false,
    },
    taskDraggable: {
      type: Boolean,
      default: false,
    },
    showTitles: {
      type: Array,
      default: () => ["year", "quarter", "month", "week", "day"],
    },
    activeRangeType: {
      type: String,
      default: "",
    },
  },
  data() {
    return {
      context: "user/time-tracking",
      dragEndTimeout: null,
    };
  },
  methods: {
    isEmptyValue(value) {
      return isEmptyValue(value);
    },
    isTitleVisible(periodType, isHiddenTitle) {
      if (isHiddenTitle === true) {
        return false;
      }

      return this.showTitles.includes(periodType) === true;
    },
    t,
    isWeekend(value) {
      const lowerCaseValue = value.toLowerCase();
      return [t("done", "saturday"), t("done", "sunday")].includes(
        lowerCaseValue
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
      return t("done", "{n} week", { n: weekNumber });
    },
    getDayTitle(dayId, monthId, yearId, day_name) {
      const dayName = day_name.toLowerCase();

      return `${dayId}.${monthId}.${yearId} ${dayName}`;
    },
    getDayId(dayId, monthId, yearId) {
      const date = [yearId, monthId, dayId].join("-");

      return `day-${date}`;
    },
    getHoursById(...rest) {
      const key = rest.join("-");
      const totalMinutes = this.totals[key];

      if (!totalMinutes) {
        return "";
      }

      const { stringTime } = minutesToHours(totalMinutes);

      return stringTime;
    },
    handleClickDay(dayId, monthId, yearId) {
      const date = `${yearId}-${monthId}-${dayId}`;

      this.$emit("onClickDate", { date });
    },
    handleCopy(modelData) {
      this.$emit("onCopy", modelData);
    },
    handleDelete({ slug, slug_type }) {
      this.$emit("onDelete", { slug, slug_type });
    },
    handleDragStart() {
      clearTimeout(this.dragEndTimeout);
    },
    handleDragEnd({ event, list }) {
      this.dragEndTimeout = setTimeout(() => {
        this.$emit("onDragEnd", { event, list });
      }, 1000);
    },
  },
};
</script>
