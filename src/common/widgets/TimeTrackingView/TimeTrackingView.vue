/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div>
    <!-- YEAR -->
    <div v-for="{ id: yearId, children: quarters } in modelData" :key="yearId">
      <div
        v-if="isTitleVisible('year')"
        :class="{
          [classNames.row]: true,
          [classNames.sticky]: activeRangeType === 'year',
          'top-0': activeRangeType === 'year',
        }"
      >
        <div class="text-3xl font-semibold">
          {{ yearId }}
        </div>
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
            :class="{
              [classNames.row]: true,
              [classNames.sticky]: activeRangeType === 'quarter',
              'top-0': activeRangeType === 'quarter',
            }"
          >
            <div class="text-2xl font-semibold">
              {{ getQuarterTitle(quarterId) }}
            </div>
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
                :class="{
                  [classNames.row]: true,
                  [classNames.sticky]: ['year', 'quarter', 'month'].includes(
                    activeRangeType
                  ),
                  'top-0': activeRangeType === 'month',
                  'top-(--time-tracking-row-height)': [
                    'year',
                    'quarter',
                  ].includes(activeRangeType),
                }"
              >
                <div class="text-xl font-semibold">
                  {{ getMonthTitle(monthId) }}
                </div>
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
                    :class="{
                      [classNames.row]: true,
                      [classNames.sticky]: activeRangeType === 'week',
                      'top-0': activeRangeType === 'week',
                    }"
                  >
                    <div class="text-lg font-semibold">
                      {{ getWeekTitle(weekId) }}
                    </div>
                    <VTimeChip
                      :value="getHoursById(yearId, 'week', weekId)"
                      :variant="activeRangeType === 'week' ? 'primary' : ''"
                    />
                  </div>

                  <!-- DAYS -->
                  <template v-if="days && days.length > 0">
                    <div
                      v-for="day in days"
                      :key="day.id"
                      :ref="
                        (el) => observeDayElement(el, day.id, monthId, yearId)
                      "
                    >
                      <div
                        :id="getDayId(day.id, monthId, yearId)"
                        :class="classNames.row"
                      >
                        <div
                          v-if="isTitleVisible('day')"
                          :class="{
                            'text-base': true,
                            'cursor-pointer hover:text-(--color-primary-element)':
                              dayDisabled === false,
                            'text-(--color-placeholder-dark)': isWeekend(
                              day.day_name
                            ),
                          }"
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
                      <template v-if="isDayVisible(day.id, monthId, yearId)">
                        <Draggable
                          v-if="taskDraggable"
                          v-model="day.children"
                          :animation="150"
                          handle="[data-handle]"
                          ghost-class="opacity-0"
                          class="flex flex-col gap-2"
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
                        <div v-else class="flex flex-col gap-2">
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
                      </template>
                      <!-- <div v-else class="flex flex-col gap-2">
                        <div 
                          v-for="n in getPlaceholderCount(day.children)" 
                          :key="n"
                          class="h-12 bg-gray-100 dark:bg-gray-700 animate-pulse rounded"
                        >
                          {{ /** Placeholder */ }}
                        </div>
                      </div> -->
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
import { t } from "@nextcloud/l10n";
import Draggable from "vuedraggable";

import { TimeTrackingItem } from "@/common/features/TimeTrackingItem";

import VTimeChip from "@/common/shared/components/VTimeChip/VTimeChip.vue";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";
import { visibilityMixin } from "@/admin/shared/lib/mixins/visibilityMixin";
import { minutesToHours, isEmptyValue } from "@/common/shared/lib/helpers";

import { MONTHS } from "@/common/shared/lib/constants";

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
  mixins: [contextualTranslationsMixin, visibilityMixin],
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
      classNames: {
        row: "flex gap-4 items-center h-(--time-tracking-row-height) nowrap",
        sticky: "sticky left-0 w-full z-100 bg-(--color-main-background)",
      },
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
    getDayElementId(dayId, monthId, yearId) {
      return `day-container-${yearId}-${monthId}-${dayId}`;
    },
    observeDayElement(element, dayId, monthId, yearId) {
      if (!element) {
        return;
      }

      const dayElementId = this.getDayElementId(dayId, monthId, yearId);

      this.$nextTick(() => {
        this.observeElement(element, dayElementId);
      });
    },
    isDayVisible(dayId, monthId, yearId) {
      const dayElementId = this.getDayElementId(dayId, monthId, yearId);

      return this.isItemVisible(dayElementId); // visibilityMixin
    },
    /* getPlaceholderCount(children) {
      // Placeholders count based on items
      if (!children || !Array.isArray(children)) {
        return 1; // Minimum 1 placeholder
      }

      return Math.max(1, children.length);
    }, */
  },
};
</script>
