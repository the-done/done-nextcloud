/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div class="gantt">
    <!-- Type legend -->
    <div v-if="vacationTypes.length > 0" class="gantt__legend">
      <div v-for="type in vacationTypes" :key="type.id" class="gantt__legend-item">
        <span class="gantt__legend-dot" :style="{ backgroundColor: type.color }"></span>
        <span class="gantt__legend-name">{{ type.name }}</span>
      </div>
      <div class="gantt__legend-item">
        <span class="gantt__legend-dot gantt__legend-dot--pending"></span>
        <span class="gantt__legend-name">{{ t('done', 'Pending') }}</span>
      </div>
    </div>

    <!-- Scrollable chart -->
    <div ref="scroll" class="gantt__scroll">

      <!-- Header row -->
      <div class="gantt__header-row">
        <!-- Sticky name cell -->
        <div ref="nameCol" class="gantt__name-col gantt__header-name-col">
          {{ t('done', 'Employee') }}
        </div>

        <!-- Timeline header: month row + optional day row -->
        <div class="gantt__timeline-col" :style="{ width: timelineWidth + 'px' }">
          <!-- Month name row (always shown) -->
          <div class="gantt__header-months">
            <div
              v-for="group in monthGroups"
              :key="group.key"
              class="gantt__month-label"
              :style="{ width: group.widthPx + 'px' }"
            >
              {{ group.label }}
            </div>
            <!-- Today marker aligned with month row -->
            <div
              v-if="todayLeftPx !== null"
              class="gantt__today-marker"
              :style="{ left: todayLeftPx + 'px' }"
            ></div>
          </div>

          <!-- Day numbers row (month view only) -->
          <div v-if="!isYearView" class="gantt__header-days">
            <div
              v-for="day in headerDays"
              :key="day.key"
              class="gantt__day-label"
              :class="{
                'gantt__day-label--weekend': day.isWeekend,
                'gantt__day-label--today': day.isToday,
              }"
              :style="{ width: dayPx + 'px' }"
            >
              {{ day.label }}
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="employees.length === 0" class="gantt__empty">
        {{ t('done', 'No vacations found for this period') }}
      </div>

      <!-- Employee rows -->
      <div
        v-for="employee in employees"
        :key="employee.user_id"
        class="gantt__row"
      >
        <!-- Name column -->
        <div class="gantt__name-col gantt__row-name-col" :title="employee.user_name">
          {{ employee.user_name }}
        </div>

        <!-- Timeline column -->
        <div class="gantt__row-timeline" :style="{ width: timelineWidth + 'px' }">
          <!-- Weekend backgrounds -->
          <template v-if="!isYearView">
            <template v-for="day in headerDays">
              <div
                v-if="day.isWeekend"
                :key="'bg-' + day.key"
                class="gantt__weekend-bg"
                :style="{ left: day.leftPx + 'px', width: dayPx + 'px' }"
              ></div>
            </template>
          </template>

          <!-- Month background bands (alternating) -->
          <div
            v-for="(group, idx) in monthGroups"
            :key="'mb-' + group.key"
            class="gantt__month-band"
            :class="{ 'gantt__month-band--alt': idx % 2 === 1 }"
            :style="{ left: group.leftPx + 'px', width: group.widthPx + 'px' }"
          ></div>

          <!-- Day grid lines (month view only) -->
          <div
            v-if="!isYearView"
            v-for="day in headerDays"
            :key="'gl-' + day.key"
            class="gantt__grid-line"
            :style="{ left: day.leftPx + 'px' }"
          ></div>

          <!-- Month boundary lines (always, stronger) -->
          <div
            v-for="group in monthGroups"
            :key="'ml-' + group.key"
            class="gantt__month-line"
            :style="{ left: group.leftPx + 'px' }"
          ></div>

          <!-- Today line -->
          <div
            v-if="todayLeftPx !== null"
            class="gantt__today-line"
            :style="{ left: todayLeftPx + 'px' }"
          ></div>

          <!-- Vacation bars are real links to the vacation card so they can be
               opened in a new tab (router-link → <a href>). -->
          <router-link
            v-for="vacation in employee.vacations"
            :key="vacation.id"
            class="gantt__bar gantt__bar--clickable"
            :class="{ 'gantt__bar--pending': vacation.status_id !== 'approved' }"
            :style="barStyle(vacation)"
            :to="{ name: 'vacations-request-card', params: { slug: vacation.id } }"
          >
            <span class="gantt__bar-label">{{ vacation.type_name }}</span>
          </router-link>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import { t } from "@nextcloud/l10n";
import {
  parse,
  parseISO,
  differenceInCalendarDays,
  eachDayOfInterval,
  eachMonthOfInterval,
  format,
  isWeekend,
  isToday,
  max,
  min,
} from "date-fns";
import { SUBMIT_DATE_FORMAT, MONTHS } from "@/shared/lib/constants/date";

export default {
  name: "VacationGantt",
  props: {
    employees: {
      type: Array,
      default: () => [],
    },
    dateFrom: {
      type: String,
      required: true,
    },
    dateTo: {
      type: String,
      required: true,
    },
    rangeType: {
      type: String,
      default: "month",
    },
  },
  data() {
    return {
      availableTimelineWidth: 0,
      hasScrolledToToday: false,
    };
  },
  computed: {
    isYearView() {
      return this.rangeType === "year";
    },
    dayPx() {
      if (this.isYearView) return 8;
      const minDayPx = 32;
      if (this.totalDays <= 0 || this.availableTimelineWidth <= 0) return minDayPx;
      const stretched = this.availableTimelineWidth / this.totalDays;
      return Math.max(minDayPx, stretched);
    },
    fromDate() {
      return parse(this.dateFrom, SUBMIT_DATE_FORMAT, new Date());
    },
    toDate() {
      return parse(this.dateTo, SUBMIT_DATE_FORMAT, new Date());
    },
    totalDays() {
      return differenceInCalendarDays(this.toDate, this.fromDate) + 1;
    },
    timelineWidth() {
      return this.totalDays * this.dayPx;
    },
    todayLeftPx() {
      const today = new Date();
      const offsetFromStart = differenceInCalendarDays(today, this.fromDate);
      const offsetToEnd     = differenceInCalendarDays(this.toDate, today);
      if (offsetFromStart < 0 || offsetToEnd < 0) return null;
      return offsetFromStart * this.dayPx + this.dayPx / 2;
    },
    headerDays() {
      const days = eachDayOfInterval({ start: this.fromDate, end: this.toDate });
      return days.map((day, i) => ({
        key: format(day, "yyyy-MM-dd"),
        label: format(day, "d"),
        leftPx: i * this.dayPx,
        isWeekend: isWeekend(day),
        isToday: isToday(day),
      }));
    },
    monthGroups() {
      const months = eachMonthOfInterval({ start: this.fromDate, end: this.toDate });
      return months.map((monthStart) => {
        const clampedStart = max([monthStart, this.fromDate]);
        const nextMonthStart = new Date(monthStart.getFullYear(), monthStart.getMonth() + 1, 1);
        const monthEnd = new Date(nextMonthStart.getTime() - 86400000);
        const clampedEnd = min([monthEnd, this.toDate]);

        const leftDays  = differenceInCalendarDays(clampedStart, this.fromDate);
        const widthDays = differenceInCalendarDays(clampedEnd, clampedStart) + 1;

        const monthName = MONTHS[monthStart.getMonth()] || "";

        return {
          key:     format(monthStart, "yyyy-MM"),
          label:   `${t("done", monthName)} ${monthStart.getFullYear()}`,
          leftPx:  leftDays * this.dayPx,
          widthPx: widthDays * this.dayPx,
        };
      });
    },
    vacationTypes() {
      const seen = new Map();
      for (const emp of this.employees) {
        for (const vac of emp.vacations) {
          if (!seen.has(vac.type_id)) {
            seen.set(vac.type_id, { id: vac.type_id, name: vac.type_name, color: vac.type_color });
          }
        }
      }
      return [...seen.values()];
    },
  },
  mounted() {
    this.measure();
    if (typeof ResizeObserver !== "undefined") {
      this.resizeObserver = new ResizeObserver(this.measure);
      this.resizeObserver.observe(this.$refs.scroll);
    }
    window.addEventListener("resize", this.measure);
    // Scroll the timeline to the current day when the chart first opens.
    this.$nextTick(this.scrollToToday);
  },
  beforeDestroy() {
    if (this.resizeObserver) this.resizeObserver.disconnect();
    window.removeEventListener("resize", this.measure);
  },
  watch: {
    employees() {
      // Data may arrive after mount — try once more to center on today.
      this.$nextTick(() => {
        this.measure();
        this.scrollToToday();
      });
    },
    rangeType() {
      // A deliberate view switch rebuilds the timeline — re-center on today.
      this.hasScrolledToToday = false;
      this.$nextTick(() => {
        this.measure();
        this.scrollToToday();
      });
    },
  },
  methods: {
    t,
    measure() {
      const scroll = this.$refs.scroll;
      const nameCol = this.$refs.nameCol;
      if (!scroll) return;
      const nameWidth = nameCol ? nameCol.getBoundingClientRect().width : 0;
      this.availableTimelineWidth = Math.max(0, scroll.clientWidth - nameWidth - 1);
    },
    scrollToToday() {
      if (this.hasScrolledToToday) return;

      const scroll = this.$refs.scroll;
      // todayLeftPx is null when "today" falls outside the visible period.
      if (!scroll || this.todayLeftPx === null) return;

      const nameCol = this.$refs.nameCol;
      const nameWidth = nameCol ? nameCol.getBoundingClientRect().width : 0;
      // Visible timeline width = scroll viewport minus the sticky name column.
      const usableWidth = Math.max(0, scroll.clientWidth - nameWidth);

      // Center today within the visible timeline area (clamped to valid range).
      const target = this.todayLeftPx - usableWidth / 2;
      const maxScroll = Math.max(0, scroll.scrollWidth - scroll.clientWidth);
      scroll.scrollLeft = Math.min(Math.max(0, target), maxScroll);

      this.hasScrolledToToday = true;
    },
    barStyle(vacation) {
      const startStr = String(vacation.date_start).slice(0, 10);
      const endStr   = String(vacation.date_end).slice(0, 10);

      const vacStart = parseISO(startStr);
      const vacEnd   = parseISO(endStr);

      const clampedStart = max([vacStart, this.fromDate]);
      const clampedEnd   = min([vacEnd, this.toDate]);

      const leftDays  = Math.max(0, differenceInCalendarDays(clampedStart, this.fromDate));
      const widthDays = Math.max(1, differenceInCalendarDays(clampedEnd, clampedStart) + 1);

      const leftPx  = leftDays * this.dayPx;
      const widthPx = Math.max(this.dayPx * 0.5, widthDays * this.dayPx - 4);

      return {
        left:            leftPx + "px",
        width:           widthPx + "px",
        top:             "8px",
        height:          "32px",
        backgroundColor: vacation.type_color || "#9E9E9E",
        opacity:         vacation.status_id !== "approved" ? 0.55 : 1,
      };
    },
  },
};
</script>

<style scoped>
.gantt {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
}

/* ── Legend ────────────────────────────────────────────────── */
.gantt__legend {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  padding: 10px 16px;
  border-bottom: 1px solid var(--color-border);
  flex-shrink: 0;
}

.gantt__legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.gantt__legend-dot {
  width: 12px;
  height: 12px;
  border-radius: 2px;
  flex-shrink: 0;
}

.gantt__legend-dot--pending {
  background-color: var(--color-text-maxcontrast);
  opacity: 0.55;
  border: 1px dashed var(--color-text-maxcontrast);
}

/* ── Scroll container ──────────────────────────────────────── */
.gantt__scroll {
  flex: 1;
  overflow: auto;
}

/* ── Shared: name column ───────────────────────────────────── */
.gantt__name-col {
  width: 18vw;
  min-width: 18vw;
  max-width: 18vw;
  flex-shrink: 0;
  position: sticky;
  left: 0;
  background: var(--color-main-background);
  border-right: 1px solid var(--color-border);
  padding: 0 12px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  z-index: 3;
  box-sizing: border-box;
}

/* ── Header row ────────────────────────────────────────────── */
.gantt__header-row {
  display: flex;
  align-items: stretch;
  width: max-content;
  position: sticky;
  top: 0;
  z-index: 10;
  background: var(--color-main-background);
  border-bottom: 2px solid var(--color-border);
}

.gantt__header-name-col {
  display: flex;
  align-items: center;
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  z-index: 11;
}

/* ── Timeline column (header) ──────────────────────────────── */
.gantt__timeline-col {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  border-right: 1px solid var(--color-border);
}

/* ── Month row in header ───────────────────────────────────── */
.gantt__header-months {
  position: relative;
  display: flex;
  flex-direction: row;
  height: 28px;
  flex-shrink: 0;
  border-bottom: 1px solid var(--color-border);
}

/* ── Day row in header ─────────────────────────────────────── */
.gantt__header-days {
  display: flex;
  flex-direction: row;
  height: 32px;
  flex-shrink: 0;
}

/* ── Month labels ──────────────────────────────────────────── */
.gantt__month-label {
  flex-shrink: 0;
  height: 28px;
  display: flex;
  align-items: center;
  padding-left: 8px;
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  border-right: 2px solid var(--color-border);
  white-space: nowrap;
  overflow: hidden;
  user-select: none;
  box-sizing: border-box;
}

/* ── Day labels ────────────────────────────────────────────── */
.gantt__day-label {
  flex-shrink: 0;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  color: var(--color-text-maxcontrast);
  user-select: none;
}

.gantt__day-label--weekend {
  color: var(--color-border-success);
  font-weight: 600;
}

.gantt__day-label--today {
  color: var(--color-primary-element);
  font-weight: 700;
}

/* ── Body rows ─────────────────────────────────────────────── */
.gantt__row {
  display: flex;
  width: max-content;
  border-bottom: 1px solid var(--color-border);
  height: 48px;
  align-items: stretch;
}

.gantt__row:hover {
  background-color: var(--color-background-hover);
}

.gantt__row:hover .gantt__name-col {
  background-color: var(--color-background-hover);
}

.gantt__row-name-col {
  display: flex;
  align-items: center;
  font-size: 13px;
  color: var(--color-main-text);
}

/* ── Row timeline ──────────────────────────────────────────── */
.gantt__row-timeline {
  position: relative;
  flex-shrink: 0;
  height: 48px;
  border-right: 1px solid var(--color-border);
}

/* ── Empty ─────────────────────────────────────────────────── */
.gantt__empty {
  padding: 48px;
  text-align: center;
  color: var(--color-text-maxcontrast);
  font-size: 14px;
}

/* ── Month background bands (alternating) ──────────────────── */
.gantt__month-band {
  position: absolute;
  top: 0;
  height: 48px;
  pointer-events: none;
}

.gantt__month-band--alt {
  background: var(--color-background-dark);
  opacity: 0.4;
}

/* ── Day grid lines (month view) ───────────────────────────── */
.gantt__grid-line {
  position: absolute;
  top: 0;
  height: 48px;
  width: 1px;
  background: var(--color-border);
  pointer-events: none;
}

/* ── Month boundary lines (stronger) ──────────────────────── */
.gantt__month-line {
  position: absolute;
  top: 0;
  height: 48px;
  width: 2px;
  background: var(--color-border-dark, var(--color-border));
  pointer-events: none;
}

/* ── Weekend background ────────────────────────────────────── */
.gantt__weekend-bg {
  position: absolute;
  top: 0;
  height: 48px;
  background: var(--color-warning);
  opacity: 0.06;
  pointer-events: none;
}

/* ── Today marker (in month header row) ────────────────────── */
.gantt__today-marker {
  position: absolute;
  top: 0;
  height: 28px;
  width: 2px;
  background: var(--color-primary-element);
  opacity: 0.7;
  pointer-events: none;
  transform: translateX(-50%);
}

/* ── Today line (in body rows) ─────────────────────────────── */
.gantt__today-line {
  position: absolute;
  top: 0;
  height: 48px;
  width: 2px;
  background: var(--color-primary-element);
  opacity: 0.7;
  z-index: 2;
  pointer-events: none;
  transform: translateX(-50%);
}

/* ── Vacation bars ─────────────────────────────────────────── */
.gantt__bar {
  position: absolute;
  border-radius: 4px;
  display: flex;
  align-items: center;
  padding: 0 6px;
  overflow: hidden;
  cursor: default;
  z-index: 1;
  transition: filter 0.1s;
  box-sizing: border-box;
}

.gantt__bar--clickable {
  cursor: pointer;
  text-decoration: none;
}

.gantt__bar:hover {
  filter: brightness(0.88);
  z-index: 3;
}

.gantt__bar--pending {
  border: 2px dashed rgba(0, 0, 0, 0.25);
}

.gantt__bar-label {
  font-size: 11px;
  font-weight: 500;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
  pointer-events: none;
}
</style>