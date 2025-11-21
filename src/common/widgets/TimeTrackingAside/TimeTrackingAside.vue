/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div
    data-component-id="TimeTrackingAside"
    :class="[
      'flex-[0_0_auto] flex flex-col w-full',
      'overflow-auto shadow-md z-500',
      'md:w-auto md:max-w-[340px] md:min-w-[300px] md:border-r md:border-(--color-border) md:shadow-none',
    ]"
  >
    <div class="flex flex-col gap-2 px-4 py-2">
      <div class="flex items-center justify-between gap-2">
        <NcButton
          aria-label="Back"
          class="flex-[0_0_auto]"
          @click="() => handleChangeDate('sub')"
        >
          <template #icon>
            <ChevronLeft :size="20" />
          </template>
        </NcButton>
        <div class="v-time-tracking-aside-datepicker relative w-full">
          <NcButton aria-label="Date" :wide="true" @click="openDatepicker">
            {{ activeDateLabel }}
          </NcButton>
          <NcDateTimePicker
            :open.sync="showDatepicker"
            :model-value="activeDate"
            @update:modelValue="handleUpdateActiveDate"
          />
        </div>
        <NcButton
          aria-label="Forward"
          class="flex-[0_0_auto]"
          @click="() => handleChangeDate('add')"
        >
          <template #icon>
            <ChevronRight :size="20" />
          </template>
        </NcButton>
        <NcActions class="flex-[0_0_auto]">
          <template #icon>
            <component :is="activeRangeIcon" />
          </template>
          <NcActionButton
            v-for="(item, key) in dateRanges"
            :key="key"
            :aria-label="item.label"
            :model-value="rangeType"
            :value="key"
            type="radio"
            @click="() => handleUpdateRangeType(key)"
          >
            <template #icon v-if="item.icon">
              <component :is="item.icon" />
            </template>
            {{ item.label }}
          </NcActionButton>
        </NcActions>
        <div class="md:hidden">
          <NcButton
            v-if="filterDescriptor?.length"
            type="tertiary"
            aria-label="Open filters"
            class="flex-[0_0_auto]"
            @click="() => handleToggleFilter()"
          >
            <template #icon>
              <FilterCog :size="20" />
            </template>
          </NcButton>
        </div>
        <slot name="controls" />
      </div>
    </div>
    <TimeTrackingFilter
      v-if="filterDescriptor?.length"
      :active.sync="filterIsActive"
      :descriptor="filterDescriptor"
      @update:filter="handleUpdateFilter"
    />
    <slot />
  </div>
</template>

<script>
import {
  add,
  sub,
  addQuarters,
  subQuarters,
  getYear,
  getQuarter,
  getWeek,
  getMonth,
} from "date-fns";
import {
  NcActions,
  NcActionButton,
  NcButton,
  NcDateTimePicker,
} from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import FilterMinusOutline from "vue-material-design-icons/FilterMinusOutline.vue";
import FilterPlusOutline from "vue-material-design-icons/FilterPlusOutline.vue";
import ChevronLeft from "vue-material-design-icons/ChevronLeft.vue";
import ChevronRight from "vue-material-design-icons/ChevronRight.vue";
import ViewComfy from "vue-material-design-icons/ViewComfy.vue";
import ViewGrid from "vue-material-design-icons/ViewGrid.vue";
import ViewModule from "vue-material-design-icons/ViewModule.vue";
import ViewDay from "vue-material-design-icons/ViewDay.vue";
import DotsHorizontal from "vue-material-design-icons/DotsHorizontal.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import FilterCog from "vue-material-design-icons/FilterCog.vue";

import { TimeTrackingFilter } from "@/common/widgets/TimeTrackingFilter";

import { TimeTrackingItem } from "@/common/features/TimeTrackingItem";

import VForm from "@/common/shared/components/VForm/VForm.vue";
import VDatePicker from "@/common/shared/components/VDatePicker/VDatePicker.vue";

import { MONTHS } from "@/common/shared/lib/constants";

export default {
  name: "TimeTrackingAside",
  components: {
    NcActions,
    NcActionButton,
    NcButton,
    NcDateTimePicker,
    FilterMinusOutline,
    FilterPlusOutline,
    ChevronLeft,
    ChevronRight,
    ViewComfy,
    ViewModule,
    ViewGrid,
    ViewDay,
    DotsHorizontal,
    Plus,
    FilterCog,
    TimeTrackingFilter,
    TimeTrackingItem,
    VForm,
    VDatePicker,
  },
  emits: ["update:activeDate", "update:rangeType", "update:filter"],
  mixins: [contextualTranslationsMixin],
  props: {
    activeDate: {
      type: Date,
      default: new Date(),
    },
    rangeType: {
      type: String,
      default: "month",
    },
    filterDescriptor: {
      type: Array,
      default: () => [],
    },
  },
  data: () => ({
    context: "user/time-tracking",
    dateRanges: {
      year: {
        label: t("done", "Year"),
        icon: ViewComfy,
      },
      quarter: {
        label: t("done", "Quarter"),
        icon: ViewModule,
      },
      month: {
        label: t("done", "Month"),
        icon: ViewGrid,
      },
      week: {
        label: t("done", "Week"),
        icon: ViewDay,
      },
    },
    isExpanded: true,
    showDatepicker: false,
    filterIsActive: false,
  }),
  computed: {
    activeRangeIcon() {
      const activeRangeType = this.dateRanges[this.rangeType];

      if (activeRangeType) {
        return activeRangeType.icon;
      }

      return DotsHorizontal;
    },
    activeDateLabel() {
      switch (this.rangeType) {
        case "year": {
          return getYear(this.activeDate);
        }
        case "quarter": {
          const quarter = getQuarter(this.activeDate);
          const year = getYear(this.activeDate);

          return t("done", "{quarter} quarter {year}", { quarter, year });
        }
        case "month": {
          const month = getMonth(this.activeDate);
          const textMonth = t("done", MONTHS[month]);
          const year = getYear(this.activeDate);

          return `${textMonth} ${year}`;
        }
        case "week": {
          const week = getWeek(this.activeDate);
          const year = getYear(this.activeDate);

          return t("done", "{week} week {year} year", { week, year });
        }
      }
    },
  },
  methods: {
    t,
    openDatepicker() {
      this.showDatepicker = true;
    },
    handleUpdateActiveDate(value) {
      this.$emit("update:activeDate", value);
    },
    handleUpdateRangeType(value) {
      this.$emit("update:rangeType", value);
    },
    getNextDate(transformType) {
      switch (this.rangeType) {
        case "year": {
          const date =
            transformType === "add"
              ? add(this.activeDate, { years: 1 })
              : sub(this.activeDate, { years: 1 });

          return date;
        }
        case "quarter": {
          const date =
            transformType === "add"
              ? addQuarters(this.activeDate, 1)
              : subQuarters(this.activeDate, 1);

          return date;
        }
        case "month": {
          const date =
            transformType === "add"
              ? add(this.activeDate, { months: 1 })
              : sub(this.activeDate, { months: 1 });

          return date;
        }
        case "week": {
          const date =
            transformType === "add"
              ? add(this.activeDate, { weeks: 1 })
              : sub(this.activeDate, { weeks: 1 });

          return date;
        }

        default: {
          console.log("Type not allowed.");

          return this.value;
        }
      }
    },
    handleChangeDate(transformType) {
      switch (this.rangeType) {
        case "year": {
          const nextDate = this.getNextDate(transformType);

          this.handleUpdateActiveDate(nextDate);

          break;
        }
        case "quarter": {
          const nextDate = this.getNextDate(transformType);

          this.handleUpdateActiveDate(nextDate);

          break;
        }
        case "month": {
          const nextDate = this.getNextDate(transformType);

          this.handleUpdateActiveDate(nextDate);

          break;
        }
        case "week": {
          const nextDate = this.getNextDate(transformType);

          this.handleUpdateActiveDate(nextDate);

          break;
        }

        default: {
          console.log("Range not allowed.");
        }
      }
    },
    handleToggleFilter() {
      this.filterIsActive = !this.filterIsActive;
    },
    handleUpdateFilter() {
      this.$emit("update:filter");
    },
  },
};
</script>

<style scoped>
.v-time-tracking-aside-datepicker:deep(.mx-input-wrapper) {
  display: none;
}

.v-time-tracking-aside-datepicker:deep(.mx-datepicker) {
  display: block;
  width: 0;
}
</style>
