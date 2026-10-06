<template>
  <section class="matrix-page" :class="{ 'is-fullscreen': isFullscreen }" @keydown.esc="closePanel">
    <VToolbar class="matrix-toolbar">
      <NcBreadcrumbs>
        <NcBreadcrumb :name="tr('Reports')" :to="{ name: 'report-home' }" forceIconText>
          <template #icon><FileChart /></template>
        </NcBreadcrumb>
        <NcBreadcrumb :name="tr('Table view')" />
      </NcBreadcrumbs>
      <template #actions>
        <NcActions class="matrix-export" :menu-name="tr('Download')" :open.sync="exportMenuOpen" force-menu
          :disabled="loading || !matrix || !matrix.rows.length">
          <template #icon><Download :size="20" /></template>
          <NcActionButton @click="exportView('xlsx')">Excel (.xlsx)</NcActionButton>
          <NcActionButton @click="exportView('pdf')">PDF</NcActionButton>
          <NcActionButton @click="exportView('html')">HTML</NcActionButton>
        </NcActions>
        <NcButton ref="fullscreenButton" variant="secondary" :aria-label="tr('Fullscreen (Esc to exit)')"
          :title="tr('Fullscreen (Esc to exit)')" @click="enterFullscreen">
          <template #icon><Fullscreen :size="20" /></template>
        </NcButton>
      </template>
    </VToolbar>

    <div class="matrix-controls-bar">
    <div id="matrix-display-options" class="matrix-controls">
      <div class="matrix-segment" :aria-label="tr('Group rows by')" role="group">
        <button :aria-pressed="mode === 'employees'" @click="change({ mode: 'employees' })">
          {{ tr('Employees') }}
        </button>
        <button :aria-pressed="mode === 'projects'" @click="change({ mode: 'projects' })">
          {{ tr('Projects') }}
        </button>
      </div>
      <div class="matrix-segment" :aria-label="tr('Period')" role="group">
        <button :aria-pressed="period === 'year'" @click="change({ period: 'year' })">{{ tr('Year') }}</button>
        <button :aria-pressed="period === 'month'" @click="change({ period: 'month' })">{{ tr('Month') }}</button>
        <button :aria-pressed="period === 'week'" @click="change({ period: 'week' })">{{ tr('Week') }}</button>
      </div>
      <div class="matrix-period">
        <button :disabled="loading || !matrix || !matrix.previous" :aria-label="tr('Previous period with data')"
          @click="change({ date: matrix.previous })"><ChevronLeft :size="20" /></button>
        <strong aria-live="polite">{{ periodTitle }}</strong>
        <button :disabled="loading || !matrix || !matrix.next" :aria-label="tr('Next period with data')"
          @click="change({ date: matrix.next })"><ChevronRight :size="20" /></button>
      </div>
      <button class="matrix-today" @click="change({ date: today() })">{{ tr('Today') }}</button>
      <label v-if="period === 'month'" class="matrix-checkbox">
        <input type="checkbox" :checked="byDates" @change="change({ by_dates: $event.target.checked ? '1' : '0' })">
        {{ tr('By dates') }}
      </label>
      <div class="matrix-meta-actions">
        <button :disabled="!matrix || loading" @click="toggleAll">
          <UnfoldLessHorizontal v-if="allExpanded" :size="18" />
          <UnfoldMoreHorizontal v-else :size="18" />
          {{ allExpanded ? tr('Collapse all') : tr('Expand all') }}
        </button>
        <button ref="filterButton" :aria-expanded="filtersOpen" aria-controls="matrix-filters"
          :class="{ 'matrix-filter-active': activeFilterCount }" @click="openFilters">
          <FilterVariant :size="18" />{{ tr('Filters') }}
          <span v-if="activeFilterCount" class="matrix-filter-badge">{{ activeFilterCount }}</span>
        </button>
      </div>
    </div>
    <button v-if="isFullscreen" class="matrix-fullscreen-exit"
      :aria-label="tr('Exit fullscreen')" @click="exitFullscreen"><Close :size="20" /></button>
    </div>
    <div v-if="error" class="matrix-error" role="alert">
      {{ error }} <button @click="load">{{ tr('Retry') }}</button>
    </div>
    <div v-if="loading" class="matrix-loading" role="status">{{ tr('Loading report…') }}</div>
    <div v-else-if="matrix && !matrix.rows.length" class="matrix-empty">
      <h2>{{ tr('No time entries in this period') }}</h2>
      <p>{{ tr('Use the arrows to find a period with data.') }}</p>
    </div>
    <div v-else-if="matrix" ref="scrollArea" class="matrix-scroll" tabindex="0"
      :class="{ 'is-scrollable': canDrag, 'is-dragging': drag,
        'has-hidden-left': hasHiddenLeft, 'has-hidden-right': hasHiddenRight }"
      :aria-label="tr('Time allocation table')" @scroll.passive="measureScroll"
      @pointerdown="startDrag" @pointermove="moveDrag" @pointerup="endDrag"
      @pointercancel="endDrag" @lostpointercapture="endDrag">
      <table class="matrix-table">
        <caption class="matrix-sr-only">{{ tr('Table view') }} — {{ periodTitle }} — {{ tr('Hours') }}</caption>
        <thead>
          <tr>
            <th scope="col" class="matrix-name" :aria-sort="sortState('name')">
              <button class="matrix-sort" @click="sortBy('name')">
                {{ mode === 'employees' ? tr('Employees') : tr('Projects') }}
                <component :is="sortIcon('name')" class="matrix-sort-icon" :size="14" />
              </button>
              <button v-if="isMobile" class="matrix-sort matrix-mobile-total" :aria-label="tr('Total')"
                @click="sortBy('total')">
                {{ tr('Total') }} <component :is="sortIcon('total')" :size="14" />
              </button>
            </th>
            <th v-for="(column, index) in matrix.columns" :key="column.start" scope="col"
              :class="{ weekend: isWeekend(column) }" :aria-sort="sortState('column:' + index)">
              <button class="matrix-sort" @click="sortBy('column:' + index)">
                {{ columnTitle(column) }}
                <component :is="sortIcon('column:' + index)" class="matrix-sort-icon" :size="14" />
              </button>
              <small v-if="columnSubtitle(column)">{{ columnSubtitle(column) }}</small>
            </th>
            <th scope="col" class="matrix-total" :aria-sort="sortState('total')">
              <button class="matrix-sort" @click="sortBy('total')">
                {{ tr('Total') }} <component :is="sortIcon('total')" class="matrix-sort-icon" :size="14" />
              </button>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in visibleRows" :key="row.id"
            :class="{ 'matrix-company': row.id === 'company', 'matrix-child': row.depth === 1,
              'matrix-selected': selected && selected.rowId === row.id }">
            <th scope="row" class="matrix-name">
              <div v-if="row.children" class="matrix-name-content">
                <button class="matrix-name-button" :aria-expanded="!!expanded[row.id]" @click="toggle(row.id)">
                  <ChevronRight class="matrix-chevron" :class="{ 'is-expanded': expanded[row.id] }" :size="18" />
                  <span class="matrix-row-name">{{ row.name }}</span>
                </button>
                <small>{{ row.children.length }}</small>
              </div>
              <span v-else class="matrix-name-static">
                <router-link v-if="rowProfile(row)" class="matrix-profile-link" :to="rowProfile(row)">
                  {{ row.name }}&nbsp;<span class="matrix-link-arrow" aria-hidden="true">↗</span>
                </router-link>
                <template v-else>{{ row.id === 'company' ? tr('Company total') : row.name }}</template>
              </span>
              <div v-if="isMobile" class="matrix-mobile-total">
                <button v-if="row.total" class="matrix-hours" :aria-label="cellLabel(row, matrix, row.total)"
                  @click="openDetails(row, matrix, 'total', $event)">{{ hours(row.total) }} {{ tr('h') }}</button>
                <span v-else>—</span>
              </div>
            </th>
            <td v-for="(minutes, index) in row.cells" :key="matrix.columns[index].start"
              :class="{ weekend: isWeekend(matrix.columns[index]), 'matrix-zero': !minutes }">
              <button v-if="minutes" class="matrix-hours"
                :class="{ selected: selected && selected.key === row.id + ':' + index }"
                :aria-label="cellLabel(row, matrix.columns[index], minutes)"
                @click="openDetails(row, matrix.columns[index], index, $event)">
                {{ hours(minutes) }}
              </button>
              <span v-else>—</span>
            </td>
            <td class="matrix-total">
              <button v-if="row.total" class="matrix-hours" :aria-label="cellLabel(row, matrix, row.total)"
                @click="openDetails(row, matrix, 'total', $event)">{{ hours(row.total) }}</button>
              <span v-else>—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <transition name="matrix-drawer">
      <aside v-if="filtersOpen || selected" ref="drawer" class="matrix-details"
        :class="{ 'matrix-filters': filtersOpen, 'has-filter-picker': filtersOpen && filterPicker }"
        :id="filtersOpen ? 'matrix-filters' : null"
        :aria-label="tr(filtersOpen ? 'Filters' : 'Time entry details')">
        <template v-if="filtersOpen">
        <header>
          <h2>{{ tr('Filters') }}</h2>
          <button ref="filterClose" class="matrix-close" :aria-label="tr('Close filters')" @click="closeFilters">
            <Close :size="20" />
          </button>
        </header>
        <div v-if="filterOptionsLoading" class="matrix-filter-summary" role="status">{{ tr('Loading filters…') }}</div>
        <div v-else-if="filterOptionsError" class="matrix-filter-summary" role="alert">
          {{ tr('Could not load filters.') }} <button @click="loadFilterOptions">{{ tr('Retry') }}</button>
        </div>
        <template v-else-if="filterOptions">
          <div class="matrix-filter-groups">
            <section v-for="group in filterGroups" :key="group.key" class="matrix-filter-group"
              :class="{ 'has-many-chips': filterSelections[group.key]?.length > 3 }" :aria-label="tr(group.label)">
              <div class="matrix-filter-group-heading">
                <h3>{{ tr(group.label) }}
                  <small v-if="filterSelections[group.key] !== null">{{ filterSelections[group.key].length }}</small>
                </h3>
                <button v-if="filterSelections[group.key] !== null" class="matrix-filter-reset"
                  @click="setFilter(group.key, null)">{{ tr('Clear') }}</button>
              </div>
              <div class="matrix-filter-chips">
                <button v-if="filterSelections[group.key] === null"
                  class="matrix-filter-chip matrix-filter-trigger matrix-filter-all"
                  :aria-expanded="filterPicker === group.key" aria-controls="matrix-filter-picker"
                  @click="openFilterPicker(group.key)">
                  {{ tr(group.key === 'project_ids' ? 'All projects' : 'All employees') }}
                </button>
                <button v-else class="matrix-filter-add matrix-filter-trigger matrix-filter-plus"
                  :aria-label="tr('Add filter') + ': ' + tr(group.label)" :title="tr('Add filter')"
                  :aria-expanded="filterPicker === group.key" aria-controls="matrix-filter-picker"
                  @click="openFilterPicker(group.key)"><Plus :size="18" /></button>
                <span v-if="filterSelections[group.key] !== null && !filterSelections[group.key].length"
                  class="matrix-filter-chip">
                  {{ tr('None selected') }}
                </span>
                <span v-for="option in selectedFilterOptions(group.key)" :key="option.id" class="matrix-filter-chip">
                  <span>{{ option.name }}</span>
                  <button :aria-label="tr('Remove filter') + ': ' + option.name"
                    @click="toggleFilter(group.key, option.id, false)"><Close :size="14" /></button>
                </span>
              </div>
            </section>
          </div>
          <div v-if="filterPicker" id="matrix-filter-picker" class="matrix-filter-picker">
            <div class="matrix-filter-picker-heading">
              <h3>{{ tr(filterPicker === 'project_ids' ? 'Projects' : 'Employees') }}</h3>
              <button class="matrix-filter-add" :aria-label="tr('Close selection')" @click="filterPicker = null">
                <Close :size="18" />
              </button>
            </div>
            <div class="matrix-filter-search">
              <input ref="filterSearchInput" v-model="filterSearch[filterPicker]" type="search"
                :aria-label="tr('Search by name')" :placeholder="tr('Search by name')">
            </div>
            <div class="matrix-filter-list">
              <button v-for="option in visibleFilterOptions" :key="option.id" class="matrix-filter-option"
                @click="toggleFilter(filterPicker, option.id, true)">
                <span>{{ option.name }}</span><Plus :size="16" />
              </button>
              <p v-if="!visibleFilterOptions.length">{{ tr('No matches') }}</p>
            </div>
          </div>
        </template>
        </template>
        <template v-else-if="selected">
        <header>
          <div><span class="matrix-eyebrow">{{ tr('Time entries') }}</span>
            <h2>
              <router-link v-if="selected.profile" class="matrix-profile-link" :to="selected.profile">
                {{ selected.name }}&nbsp;<span class="matrix-link-arrow" aria-hidden="true">↗</span>
              </router-link>
              <template v-else>{{ selected.name }}</template>
            </h2>
          </div>
          <button ref="closeButton" class="matrix-close" :aria-label="tr('Close details')" @click="closeDetails">
            <Close :size="20" />
          </button>
        </header>
        <div class="matrix-details-summary">
          <span>{{ formatDate(selected.start) }}<template v-if="selected.start !== selected.end">
            – {{ formatDate(selected.end) }}</template></span>
          <strong>{{ hours(selected.minutes) }} {{ tr('hours') }}</strong>
          <small v-if="detailCount !== null">{{ detailCount }} {{ tr('entries') }}</small>
        </div>
        <div ref="detailList" class="matrix-detail-list" tabindex="0" :aria-label="tr('Time entries')"
          @scroll.passive="onDetailsScroll">
          <p v-if="detailError" role="alert">{{ detailError }}
            <button @click="loadDetails">{{ tr('Retry') }}</button></p>
          <article v-for="entry in entries" :key="entry.id" class="matrix-entry">
            <div class="matrix-entry-top"><time>{{ formatDate(entry.date) }}</time>
              <strong>{{ hours(entry.minutes) }} {{ tr('h') }}</strong></div>
            <div class="matrix-entry-entities">
              <component :is="index === 0 ? 'h3' : 'div'" v-for="(entity, index) in entryEntities(entry)"
                :key="entity.type" :class="{ 'matrix-entry-secondary': index === 1 }">
                <router-link v-if="entity.to" class="matrix-profile-link" :to="entity.to">
                  {{ entity.name }}&nbsp;<span class="matrix-link-arrow" aria-hidden="true">↗</span>
                </router-link>
                <span v-else>{{ entity.name }}</span>
              </component>
            </div>
            <p>{{ entry.description }}</p>
            <p v-if="entry.comment" class="matrix-entry-comment">{{ entry.comment }}</p>
            <small v-if="Number(entry.is_downtime)">{{ tr('Downtime') }}</small>
            <a v-if="entry.task_link" class="matrix-entry-task" :href="entry.task_link"
              target="_blank" rel="noopener noreferrer">
              {{ tr('Open task') }}&nbsp;<span class="matrix-link-arrow" aria-hidden="true">↗</span>
            </a>
          </article>
          <p v-if="detailsLoading && !entries.length" role="status">{{ tr('Loading entries…') }}</p>
          <button v-if="entries.length < detailCount" class="matrix-load-more" :disabled="detailsLoading"
            @click="loadDetails">
            {{ tr(detailsLoading ? 'Loading entries…' : 'Load more') }} ({{ entries.length }} / {{ detailCount }})
          </button>
          <p v-else-if="detailCount === 0">{{ tr('No time entries in this period') }}</p>
        </div>
        </template>
      </aside>
    </transition>
  </section>
</template>

<script>
import { t } from "@nextcloud/l10n";
import { NcActions, NcActionButton, NcBreadcrumbs, NcBreadcrumb, NcButton } from "@nextcloud/vue";
import ChevronRight from "vue-material-design-icons/ChevronRight.vue";
import ChevronLeft from "vue-material-design-icons/ChevronLeft.vue";
import Download from "vue-material-design-icons/Download.vue";
import Close from "vue-material-design-icons/Close.vue";
import FileChart from "vue-material-design-icons/FileChart.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import FilterVariant from "vue-material-design-icons/FilterVariant.vue";
import UnfoldMoreHorizontal from "vue-material-design-icons/UnfoldMoreHorizontal.vue";
import UnfoldLessHorizontal from "vue-material-design-icons/UnfoldLessHorizontal.vue";
import ArrowUp from "vue-material-design-icons/ArrowUp.vue";
import ArrowDown from "vue-material-design-icons/ArrowDown.vue";
import SwapVertical from "vue-material-design-icons/SwapVertical.vue";
import Fullscreen from "vue-material-design-icons/Fullscreen.vue";
import { VToolbar } from "@/shared/components";
import { fetchMatrix, fetchMatrixEntries, fetchMatrixFilters, matrixExportUrl } from "@/entities/reports/api";

const iso = (date) => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, "0"),
  String(date.getDate()).padStart(2, "0")].join("-");
const parse = (value) => new Date(`${value}T12:00:00`);

export default {
  name: "ReportsMatrixPage",
  components: { NcActions, NcActionButton, NcBreadcrumbs, NcBreadcrumb, NcButton, VToolbar,
    ChevronRight, ChevronLeft, Download, Close, FileChart, Fullscreen,
    Plus, FilterVariant, UnfoldMoreHorizontal, UnfoldLessHorizontal, ArrowUp, ArrowDown, SwapVertical },
  data() {
    return {
      matrix: null, loading: false, error: "", expanded: {}, sequence: 0,
      selected: null, entries: [], detailCount: null, detailsLoading: false, detailError: "", detailSequence: 0,
      returnFocus: null, exportMenuOpen: false, canDrag: false, drag: null,
      hasHiddenLeft: false, hasHiddenRight: false,
      isMobile: window.matchMedia("(max-width: 600px)").matches,
      nativeFullscreen: false, fallbackFullscreen: false,
      filtersOpen: false, filterOptions: null, filterOptionsLoading: false, filterOptionsError: false,
      filterPicker: null, filterSearch: { project_ids: "", user_ids: "" }, filterController: null,
      filterGroups: [{ key: "project_ids", label: "Projects" }, { key: "user_ids", label: "Employees" }],
      locale: document.documentElement.lang || "en", controller: null, detailController: null,
    };
  },
  computed: {
    filterSelections() {
      const selections = {};
      this.filterGroups.forEach(({ key }) => {
        const value = this.$route.query[key];
        selections[key] = value === undefined || value === null ? null : String(value).split(",").filter(Boolean);
      });
      return selections;
    },
    filterParams() {
      const params = {};
      this.filterGroups.forEach(({ key }) => {
        if (this.filterSelections[key] !== null) params[key] = this.filterSelections[key].join(",");
      });
      return params;
    },
    activeFilterCount() { return Object.keys(this.filterParams).length; },
    visibleFilterOptions() {
      if (!this.filterPicker) return [];
      const search = this.filterSearch[this.filterPicker].trim().toLocaleLowerCase(this.locale);
      return (this.filterOptions?.[this.filterPicker] || [])
        .filter((option) => !this.filterSelected(this.filterPicker, option.id)
          && option.name.toLocaleLowerCase(this.locale).includes(search));
    },
    isFullscreen() { return this.nativeFullscreen || this.fallbackFullscreen; },
    mode() { return this.$route.query.mode === "projects" ? "projects" : "employees"; },
    period() { return ["year", "week"].includes(this.$route.query.period) ? this.$route.query.period : "month"; },
    byDates() { return this.$route.query.by_dates === "1"; },
    activeDate() {
      const date = this.$route.query.date;
      return /^\d{4}-\d{2}-\d{2}$/.test(date || "") ? date : this.today();
    },
    sortKey() { return this.$route.query.sort || "name"; },
    sortDirection() { return this.$route.query.direction === "desc" ? "desc" : "asc"; },
    request() {
      return { date: this.activeDate, period: this.period, mode: this.mode, "by_dates": this.byDates,
        sort: this.sortKey, direction: this.sortDirection, ...this.filterParams };
    },
    periodTitle() {
      if (this.period === "week" && this.matrix) {
        return `${this.formatDate(this.matrix.start)} – ${this.formatDate(this.matrix.end)}`;
      }
      return this.localizedDate(this.activeDate,
        this.period === "year" ? { year: "numeric" } : { month: "long", year: "numeric" });
    },
    visibleRows() {
      if (!this.matrix) return [];
      const result = [{ ...this.matrix.company, depth: 0 }];
      this.matrix.rows.forEach((row) => {
        result.push({ ...row, depth: 0 });
        if (this.expanded[row.id]) row.children.forEach((child) => result.push({ ...child, depth: 1 }));
      });
      return result;
    },
    allExpanded() {
      return this.matrix && this.matrix.rows.length && this.matrix.rows.every((row) => this.expanded[row.id]);
    },
  },
  watch: {
    "$route.query": { immediate: true, handler() { this.load(); } },
    isFullscreen(value) { document.body.classList.toggle("done-matrix-fullscreen", value); },
  },
  mounted() {
    this.mobileQuery = window.matchMedia("(max-width: 600px)");
    this.mobileQuery.addEventListener("change", this.onMobileChange);
    document.addEventListener("click", this.onDrawerOutsideClick, true);
    document.addEventListener("fullscreenchange", this.onFullscreenChange);
    document.addEventListener("keydown", this.onFullscreenKeydown, true);
    this.resizeObserver = new ResizeObserver(() => this.measureScroll());
    this.syncScroll();
  },
  updated() { this.syncScroll(); },
  beforeDestroy() {
    document.body.classList.remove("done-matrix-fullscreen");
    this.mobileQuery?.removeEventListener("change", this.onMobileChange);
    document.removeEventListener("click", this.onDrawerOutsideClick, true);
    document.removeEventListener("fullscreenchange", this.onFullscreenChange);
    document.removeEventListener("keydown", this.onFullscreenKeydown, true);
    if (document.fullscreenElement === this.$el) document.exitFullscreen().catch(() => {});
    this.endDrag();
    this.resizeObserver?.disconnect();
    this.sequence++;
    this.detailSequence++;
    this.controller?.abort();
    this.detailController?.abort();
    this.filterController?.abort();
  },
  methods: {
    onMobileChange(event) {
      this.isMobile = event.matches;
    },
    closePanel() {
      if (this.filtersOpen) this.closeFilters();
      else this.closeDetails();
    },
    openFilters() {
      this.closeDetails();
      this.filtersOpen = true;
      this.filterPicker = null;
      if (!this.filterOptions && !this.filterOptionsLoading) this.loadFilterOptions();
      this.$nextTick(() => this.$refs.filterClose?.focus());
    },
    closeFilters(restoreFocus = true) {
      this.filtersOpen = false;
      this.filterPicker = null;
      if (restoreFocus) this.$nextTick(() => this.$refs.filterButton?.focus());
    },
    async loadFilterOptions() {
      this.filterController?.abort();
      const controller = new AbortController();
      this.filterController = controller;
      this.filterOptionsLoading = true;
      this.filterOptionsError = false;
      try {
        const { data } = await fetchMatrixFilters(controller.signal);
        if (!Array.isArray(data.project_ids) || !Array.isArray(data.user_ids)) throw new Error("Invalid filters");
        if (!controller.signal.aborted) this.filterOptions = data;
      } catch (error) {
        if (!controller.signal.aborted) this.filterOptionsError = true;
      } finally { if (!controller.signal.aborted) this.filterOptionsLoading = false; }
    },
    openFilterPicker(key) {
      this.filterPicker = this.filterPicker === key ? null : key;
      this.$nextTick(() => this.$refs.filterSearchInput?.focus());
    },
    filterSelected(key, id) { return this.filterSelections[key]?.includes(id) || false; },
    selectedFilterOptions(key) {
      const ids = this.filterSelections[key] || [];
      const options = this.filterOptions[key];
      return [...options.filter((option) => ids.includes(option.id)),
        ...ids.filter((id) => !options.some((option) => option.id === id)).map((id) => ({ id, name: id }))];
    },
    setFilter(key, ids) {
      const query = { ...this.$route.query };
      const options = this.filterOptions?.[key] || [];
      if (ids?.length && options.length && options.every((option) => ids.includes(option.id))) ids = null;
      if (ids === null) delete query[key];
      else query[key] = [...new Set(ids)].sort().join(",");
      this.$router.replace({ name: "report-matrix", query }).catch(() => {});
    },
    toggleFilter(key, id, checked) {
      const ids = this.filterSelections[key] || [];
      const selection = checked ? [...ids, id] : ids.filter((value) => value !== id);
      this.setFilter(key, selection.length ? selection : null);
    },
    onDrawerOutsideClick(event) {
      if (event.button !== 0 || (!this.filtersOpen && !this.selected)) return;
      const target = event.target;
      const insideDrawer = this.$refs.drawer?.contains(target);
      if (this.filtersOpen && this.filterPicker) {
        if (insideDrawer && target.closest(".matrix-filter-picker, .matrix-filter-trigger")) return;
        // The explicit drawer close button still closes the entire drawer.
        if (!this.$refs.filterClose?.contains(target)) {
          this.filterPicker = null;
          // Consume this click: it must not also close the drawer or open time details.
          event.preventDefault();
          event.stopPropagation();
          return;
        }
      }
      if (insideDrawer) return;
      // These controls switch drawer content themselves, without a close/open animation.
      if (this.$refs.filterButton?.contains(target)
        || this.$el.contains(target) && target.closest("button.matrix-hours")) return;
      if (this.filtersOpen) this.closeFilters(false);
      else this.closeDetails(false);
    },
    clearReportSelection() {
      const selection = window.getSelection();
      if (!selection || selection.isCollapsed || !selection.rangeCount || !this.$el) return;
      if (selection.getRangeAt(0).intersectsNode(this.$el)) selection.removeAllRanges();
    },
    async enterFullscreen() {
      this.clearReportSelection();
      this.filtersOpen = false;
      this.closeDetails();
      this.exportMenuOpen = false;
      // Phone browsers do not consistently support fullscreen for arbitrary elements.
      if (!this.isMobile && this.$el.requestFullscreen) {
        try {
          await this.$el.requestFullscreen();
          return;
        } catch (error) {
          // Embedded browsers may deny native fullscreen; still offer a table-only viewport.
        }
      }
      this.fallbackFullscreen = true;
      this.$nextTick(() => this.$refs.scrollArea?.focus());
    },
    onFullscreenChange() {
      this.clearReportSelection();
      const wasFullscreen = this.nativeFullscreen;
      this.nativeFullscreen = document.fullscreenElement === this.$el;
      if (this.nativeFullscreen) this.$nextTick(() => this.$refs.scrollArea?.focus());
      else if (wasFullscreen) this.$nextTick(() => this.$refs.fullscreenButton?.$el.focus());
    },
    onFullscreenKeydown(event) {
      if (event.key !== "Escape" || !this.isFullscreen) return;
      event.preventDefault();
      event.stopPropagation();
      this.exitFullscreen();
    },
    exitFullscreen() {
      if (document.fullscreenElement === this.$el) document.exitFullscreen();
      else {
        this.fallbackFullscreen = false;
        this.$nextTick(() => this.$refs.fullscreenButton?.$el.focus());
      }
    },
    profileRoute(type, record) {
      const slug = record[`${type}_slug`];
      return slug ? { name: type === "user" ? "staff-preview" : "project-preview", params: { slug } } : null;
    },
    rowProfile(row) {
      if (row.id === "company") return null;
      const type = (this.mode === "employees") !== (row.depth === 1) ? "user" : "project";
      return this.profileRoute(type, row);
    },
    entryEntities(entry) {
      const order = this.selected.employeeView ? ["project", "user"] : ["user", "project"];
      return order.map((type) => ({ type, name: entry[`${type}_name`], to: this.profileRoute(type, entry) }));
    },
    syncScroll() {
      const element = this.$refs.scrollArea;
      if (element !== this.scrollElement) {
        this.resizeObserver?.disconnect();
        this.scrollElement = element;
        if (element) {
          this.resizeObserver?.observe(element);
          this.resizeObserver?.observe(element.querySelector("table"));
        }
      }
      this.measureScroll();
    },
    measureScroll() {
      const element = this.$refs.scrollArea;
      this.canDrag = !!element && element.scrollWidth > element.clientWidth + 1;
      this.hasHiddenLeft = this.canDrag && element.scrollLeft > 1;
      this.hasHiddenRight = this.canDrag && element.scrollWidth - element.clientWidth - element.scrollLeft > 1;
    },
    startDrag(event) {
      if (!this.canDrag || event.button !== 0 || event.pointerType !== "mouse"
        || event.target.closest("button, a, input, select")) return;
      event.preventDefault();
      const element = event.currentTarget;
      this.drag = { pointerId: event.pointerId, x: event.clientX, left: element.scrollLeft, element };
      element.setPointerCapture(event.pointerId);
    },
    moveDrag(event) {
      if (!this.drag || this.drag.pointerId !== event.pointerId) return;
      this.drag.element.scrollLeft = this.drag.left - (event.clientX - this.drag.x);
    },
    endDrag() {
      const drag = this.drag;
      this.drag = null;
      if (drag?.element.hasPointerCapture(drag.pointerId)) drag.element.releasePointerCapture(drag.pointerId);
    },
    sortState(key) {
      return key !== this.sortKey ? "none" : this.sortDirection === "asc" ? "ascending" : "descending";
    },
    sortIcon(key) {
      return key !== this.sortKey ? "SwapVertical" : this.sortDirection === "asc" ? "ArrowUp" : "ArrowDown";
    },
    sortBy(key) {
      const direction = key === this.sortKey ? (this.sortDirection === "asc" ? "desc" : "asc")
        : key === "name" ? "asc" : "desc";
      this.change({ sort: key, direction });
    },
    tr(text) { return t("done", text); },
    today() { return iso(new Date()); },
    hours(minutes) { return (minutes / 60).toLocaleString(this.locale, { maximumFractionDigits: 2 }); },
    localizedDate(date, options) {
      const text = parse(date).toLocaleDateString(this.locale, options);
      return /^ru(?:-|$)/i.test(this.locale) ? text.replace(/\s*г\.$/u, "") : text;
    },
    formatDate(date) {
      return this.localizedDate(date, { day: "numeric", month: "short", year: "numeric" });
    },
    columnSubtitle(column) {
      const date = parse(column.start);
      if (this.period === "month" && !this.byDates) {
        return new Intl.DateTimeFormat(this.locale, { day: "numeric", month: "long" })
          .formatToParts(date).find((part) => part.type === "month").value;
      }
      if (this.period === "week" || this.period === "month" && this.byDates) {
        return date.toLocaleDateString(this.locale, { weekday: "long" });
      }
      return "";
    },
    columnTitle(column) {
      const first = parse(column.start);
      if (this.period === "year") return first.toLocaleDateString(this.locale, { month: "short" });
      if (this.period === "month" && !this.byDates) {
        return column.start === column.end ? String(first.getDate())
          : `${first.getDate()}–${parse(column.end).getDate()}`;
      }
      return first.toLocaleDateString(this.locale, { day: "numeric", month: "short" });
    },
    isWeekend(column) {
      return column.start === column.end && [0, 6].includes(parse(column.start).getDay());
    },
    cellLabel(row, column, minutes) {
      return `${row.name}, ${column.start} – ${column.end}, ${this.hours(minutes)} ${this.tr("hours")}`;
    },
    change(values) {
      this.clearReportSelection();
      const query = { ...this.request, "by_dates": this.byDates ? "1" : "0", ...values };
      if (this.sortKey.startsWith("column:") && ["date", "period", "by_dates"].some((key) => key in values)) {
        query.sort = "name";
        query.direction = "asc";
      }
      this.$router.push({ name: "report-matrix", query }).catch(() => {});
    },
    async load() {
      this.clearReportSelection();
      const scrollLeft = this.$refs.scrollArea?.scrollLeft || 0;
      this.endDrag();
      const sequence = ++this.sequence;
      this.controller?.abort();
      this.controller = new AbortController();
      this.closeDetails();
      this.loading = true;
      this.error = "";
      try {
        const { data } = await fetchMatrix(this.request, this.controller.signal);
        if (!Array.isArray(data.rows)) throw new Error("Invalid report response");
        if (sequence === this.sequence) this.matrix = data;
      } catch (error) {
        if (sequence === this.sequence && !this.controller.signal.aborted) {
          this.error = this.tr("Could not load the report. Please try again.");
          this.matrix = null;
        }
      } finally {
        if (sequence === this.sequence) {
          this.loading = false;
          this.$nextTick(() => {
            if (this.$refs.scrollArea) this.$refs.scrollArea.scrollLeft = scrollLeft;
            this.clearReportSelection();
          });
        }
      }
    },
    toggle(id) { this.$set(this.expanded, id, !this.expanded[id]); },
    toggleAll() {
      const expanded = {};
      if (!this.allExpanded) this.matrix.rows.forEach((row) => { expanded[row.id] = true; });
      this.expanded = expanded;
    },
    openDetails(row, column, index, event) {
      this.filtersOpen = false;
      this.detailController?.abort();
      this.detailSequence++;
      this.returnFocus = event.currentTarget;
      this.selected = { name: row.id === "company" ? this.tr("Company total") : row.name,
        profile: this.rowProfile(row), employeeView: this.mode === "employees" || !!row.user_id,
        "user_id": row.user_id || "", "project_id": row.project_id || "",
        start: column.start, end: column.end, minutes: index === "total" ? row.total : row.cells[index],
        rowId: row.id, key: `${row.id}:${index}` };
      this.entries = [];
      this.detailsLoading = false;
      this.detailCount = null;
      this.loadDetails();
      this.$nextTick(() => {
        if (this.$refs.detailList) this.$refs.detailList.scrollTop = 0;
        this.$refs.closeButton?.focus();
      });
    },
    closeDetails(restoreFocus = true) {
      this.detailSequence++;
      this.detailController?.abort();
      this.selected = null;
      if (restoreFocus) this.returnFocus?.focus();
      this.returnFocus = null;
    },
    onDetailsScroll(event) {
      const list = event.currentTarget;
      if (this.detailsLoading || this.detailError || !this.selected || this.entries.length >= this.detailCount) return;
      if (list.scrollTop > 0 && list.scrollHeight - list.scrollTop - list.clientHeight < 240) this.loadDetails();
    },
    async loadDetails() {
      if (!this.selected || this.detailsLoading) return;
      const sequence = ++this.detailSequence;
      this.detailController?.abort();
      this.detailController = new AbortController();
      this.detailsLoading = true;
      this.detailError = "";
      try {
        const { data } = await fetchMatrixEntries(
          { ...this.selected, ...this.filterParams, offset: this.entries.length }, this.detailController.signal);
        if (sequence !== this.detailSequence) return;
        if (!Array.isArray(data.entries)) throw new Error("Invalid detail response");
        this.entries = [...this.entries, ...data.entries];
        this.detailCount = data.count;
      } catch (error) {
        if (sequence === this.detailSequence) this.detailError = this.tr("Could not load time entries.");
      } finally { if (sequence === this.detailSequence) this.detailsLoading = false; }
    },
    exportView(format) {
      this.exportMenuOpen = false;
      const expanded = this.matrix.rows.filter((row) => this.expanded[row.id]).map((row) => row.id);
      const link = document.createElement("a");
      link.href = matrixExportUrl({ ...this.request, format }, expanded);
      link.download = "";
      document.body.appendChild(link);
      link.click();
      link.remove();
    },
  },
};
</script>

<style scoped>
.matrix-page { position: relative; display: flex; flex-direction: column; height: 100%; overflow: hidden;
  background: var(--color-main-background); color: var(--color-main-text); container-type: inline-size; }
.matrix-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
  color: var(--color-text-maxcontrast); }
.matrix-export { flex-shrink: 0; }
.matrix-controls button, .matrix-table button,
.matrix-details button, .matrix-error button { min-height: 34px; margin: 0; font-size: 13px; }
.matrix-page button { cursor: pointer; }
/* These surfaces act as controls and a draggable grid, including in native WebKit fullscreen. */
.matrix-controls-bar, .matrix-table { user-select: none; }
/* Keep standard button/icon spacing; append only the dropdown indicator. */
.matrix-export ::v-deep .button-vue__wrapper::after { content: ""; width: 6px; height: 6px;
  margin: -3px 2px 0 10px; border-right: 1.5px solid currentColor; border-bottom: 1.5px solid currentColor;
  transform: rotate(45deg); flex-shrink: 0; }


.matrix-controls-bar { display: flex; flex: 0 0 auto; align-items: flex-start;
  border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border); }
.matrix-controls-bar .matrix-fullscreen-exit { display: flex; align-items: center; justify-content: center;
  flex: 0 0 40px; width: 40px; height: 40px; padding: 0; margin: 10px 24px 10px 0; }
.matrix-controls { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;
  flex-wrap: wrap; padding: 10px 24px; }
.matrix-segment { display: flex; flex-shrink: 0; padding: 3px; border-radius: 8px;
  background: var(--color-background-dark); }
.matrix-segment button { display: inline-flex; align-items: center; justify-content: center; line-height: 1.5;
  border: 0; border-radius: 6px; background: transparent; padding: 0 14px; }
.matrix-segment button[aria-pressed="true"] { background: var(--color-primary-element);
  color: var(--color-primary-element-text); }
.matrix-period { display: inline-flex; flex: 0 0 auto; align-items: center; gap: 6px; }
.matrix-period button { display: inline-flex; align-items: center; justify-content: center;
  width: 34px; height: 34px; padding: 0; line-height: 1; }
.matrix-period ::v-deep .material-design-icon { display: flex; align-items: center; justify-content: center; }
.matrix-period ::v-deep .material-design-icon__svg { position: static; display: block; }
.matrix-period strong { display: inline-flex; align-items: center; justify-content: center; min-height: 34px;
  text-align: center; font-size: 13px; line-height: 1.5; white-space: nowrap; }
.matrix-checkbox { display: flex; align-items: center; gap: 7px; cursor: pointer; font-size: 13px; }
.matrix-checkbox input { appearance: auto; width: 16px; height: 16px; }
.matrix-meta-actions { display: flex; align-items: center; justify-content: flex-end; gap: 12px;
  margin-left: auto; flex-shrink: 0; }
.matrix-meta-actions button { display: inline-flex; align-items: center; gap: 5px;
  border: 0; background: transparent; color: var(--color-primary-element); }
.matrix-filter-badge { padding: 0 5px; border-radius: 8px; background: var(--color-primary-element);
  color: var(--color-primary-element-text); font-size: 10px; }
.matrix-scroll { flex: 1; min-height: 0; overflow: auto; margin: 8px 24px; border: 0;
  border-radius: 8px 8px 0 0; }
.matrix-table { width: 100%; border-spacing: 0; border-collapse: separate; font-size: 13px;
  font-variant-numeric: tabular-nums; }
.matrix-table th, .matrix-table td { padding: 8px 14px; min-width: 105px; height: 42px; text-align: right;
  border-right: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border); white-space: nowrap; }


.matrix-table .matrix-name { position: sticky; left: 0; z-index: 2; min-width: 250px; max-width: 330px;
  text-align: left; background: var(--color-main-background); }

.matrix-profile-link { color: var(--color-primary-element); text-decoration: none; cursor: pointer; }
.matrix-profile-link:hover { text-decoration: underline; }
.matrix-link-arrow { display: inline; font-size: .8em; font-weight: 400; opacity: .75; }
/* Extend the native button/link hit area to the entire pinned name cell. */
.matrix-name .matrix-name-button, .matrix-name .matrix-profile-link, .matrix-name .matrix-sort {
  position: static; }
.matrix-name .matrix-name-button::after, .matrix-name .matrix-profile-link::after,
.matrix-name .matrix-sort::after { content: ""; position: absolute; inset: 0; cursor: pointer; }

.matrix-name-content { display: flex; align-items: center; gap: 12px; }
.matrix-table .matrix-name-button { display: inline-flex; align-items: center; gap: 8px;
  min-height: 0; min-width: 0; max-width: 100%; padding: 0;
  text-align: left; border: 0; border-radius: 0; background: transparent; font-weight: 600; white-space: normal; }
.matrix-row-name { min-width: 0; white-space: normal; overflow-wrap: anywhere; line-height: 1.4; }
.matrix-name-content small { flex-shrink: 0; margin-left: auto; color: var(--color-text-maxcontrast); font-size: 10px; }
.matrix-chevron { display: inline-flex; align-items: center; justify-content: center;
  width: 18px; height: 18px; flex: 0 0 18px; transform-origin: center; }
.matrix-chevron.is-expanded { transform: rotate(90deg); }
.matrix-name-static { display: block; white-space: normal; overflow-wrap: anywhere; }
.matrix-child .matrix-name-static { padding-left: 26px; color: var(--color-text-maxcontrast); font-weight: 400; }
.matrix-table .matrix-hours, .matrix-table .matrix-sort { display: inline-flex; align-items: center; gap: 5px;
  min-height: 0; min-width: 0; width: auto; height: auto; padding: 0; text-align: right;
  border: 0; border-radius: 0; background: transparent; color: inherit; font-weight: inherit; }
.matrix-table .matrix-hours:hover, .matrix-table .matrix-hours.selected {
  color: var(--color-primary-element); background: transparent;
  text-decoration: underline; }
.matrix-table .matrix-name-button, .matrix-table .matrix-name-button:hover {
  color: var(--color-main-text); }
.matrix-table .matrix-name-button:hover { text-decoration: underline; }
.matrix-sort-icon { display: inline-flex; align-items: center; justify-content: center;
  width: 14px; height: 14px; flex: 0 0 14px; line-height: 1; color: var(--color-text-maxcontrast); }
.matrix-table th[aria-sort="ascending"] .matrix-sort-icon,
.matrix-table th[aria-sort="descending"] .matrix-sort-icon { color: #000; }
.matrix-table .matrix-total { position: sticky; right: 0; z-index: 2;
  background: var(--color-main-background); font-weight: 700;
  box-shadow: -1px 0 0 var(--color-border); }
.matrix-company td, .matrix-company .matrix-name { background: var(--color-primary-element-light); font-weight: 700; }
.matrix-company .matrix-total { background: var(--color-primary-element-light); }
.matrix-company { color: var(--color-main-text); }
.matrix-table .matrix-zero { color: var(--color-text-maxcontrast); }
.matrix-table .weekend { background: var(--color-background-hover); }
.matrix-loading, .matrix-empty { padding: 60px 24px; text-align: center; }
.matrix-empty h2 { font-size: 18px; font-weight: 600; }
.matrix-error { margin: 8px 24px; padding: 12px; border: 1px solid var(--color-error); border-radius: 6px; }
.matrix-details { position: absolute; right: 0; top: 0; bottom: 0; width: min(480px, 100%); z-index: 10;
  display: flex; flex-direction: column; background: var(--color-main-background);
  border-left: 1px solid var(--color-border); box-shadow: -10px 0 30px #0002; overflow: hidden; }
.matrix-details header { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 22px; }
.matrix-details header > div { min-width: 0; }
.matrix-details h2 { overflow-wrap: anywhere; margin: 4px 0; font-size: 20px; font-weight: 600; }
.matrix-details .matrix-close { display: inline-flex; align-items: center; justify-content: center;
  width: 36px; height: 36px; min-width: 36px; padding: 0; flex-shrink: 0; }
.matrix-close ::v-deep .material-design-icon { display: flex; align-items: center; justify-content: center; }
.matrix-details-summary { display: flex; flex-direction: column; gap: 6px; padding: 16px 22px;
  background: var(--color-primary-element-light); }
.matrix-details-summary strong { font-size: 26px; }
.matrix-details-summary small { color: var(--color-text-maxcontrast); }
.matrix-detail-list { padding: 0 22px 22px; overflow: auto; flex: 1; user-select: text; }
.matrix-entry { padding: 20px 0; border-bottom: 1px solid var(--color-border); }
.matrix-entry-top { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 7px; }
.matrix-entry-top time { color: var(--color-text-maxcontrast); }
.matrix-entry h3 { overflow-wrap: anywhere; margin: 0; font-size: 17px; font-weight: 650; line-height: 1.4; }
.matrix-entry-secondary { font-size: 13px; margin: 4px 0 10px; }
.matrix-entry p { white-space: pre-wrap; overflow-wrap: anywhere; line-height: 1.5; margin: 6px 0; }
.matrix-entry-comment { font-size: 12px; color: var(--color-text-maxcontrast); }
.matrix-entry-task { display: inline-block; margin-top: 8px; color: var(--color-primary-element); }
.matrix-details h2 .matrix-profile-link, .matrix-entry h3 .matrix-profile-link {
  color: var(--color-main-text); }
.matrix-load-more { width: 100%; margin-top: 16px !important; }
.matrix-drawer-enter-active, .matrix-drawer-leave-active { transition: transform .2s ease; }
.matrix-drawer-enter, .matrix-drawer-leave-to { transform: translateX(100%); }
.matrix-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden;
  clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
.matrix-details > header, .matrix-filter-summary { flex-shrink: 0; }
.matrix-filter-summary { padding: 0 22px 14px; font-size: 13px; }
.matrix-filter-groups { display: flex; flex-direction: column; gap: 20px; padding: 0 22px 18px;
  flex: 0 1 auto; min-height: 0; overflow: hidden; }
.has-filter-picker .matrix-filter-groups { max-height: 45%; }
.matrix-filter-group { display: flex; flex-direction: column; flex: 0 1 auto; min-height: 74px; }
.matrix-filter-group.has-many-chips { min-height: 106px; }
.matrix-filter-group-heading, .matrix-filter-reset { flex-shrink: 0; }
.matrix-filter-group-heading, .matrix-filter-picker-heading { display: flex; align-items: center;
  justify-content: space-between; gap: 10px; margin-bottom: 8px; }
.matrix-filter-group h3 small { margin-left: 5px; color: var(--color-text-maxcontrast); font-size: 12px; }
.matrix-filter-group h3, .matrix-filter-picker h3 { margin: 0; font-size: 14px; font-weight: 600; }
.matrix-filters .matrix-filter-add { display: inline-flex; align-items: center; justify-content: center;
  width: 30px; min-height: 30px; padding: 0; }
.matrix-filter-chips { display: flex; flex-wrap: wrap; align-content: flex-start; gap: 6px;
  overflow: auto; min-height: 32px; overscroll-behavior: contain; padding-right: 4px; }
.matrix-filter-chip { display: inline-flex; flex-shrink: 0; align-items: center; gap: 6px;
  max-width: 100%; padding: 5px 10px;
  border-radius: 16px; background: var(--color-primary-element-light); color: var(--color-main-text);
  font-size: 12px; line-height: 20px; }
.matrix-filters .matrix-filter-plus { width: 32px; height: 32px; min-height: 32px; flex: 0 0 32px;
  border-radius: 50%; align-self: flex-start; }
.matrix-filters .matrix-filter-all { min-height: 32px; border: 0; font-weight: 400; margin: 0; }
.matrix-filter-chip > span { overflow-wrap: anywhere; min-width: 0; }
.matrix-filter-chip button { display: inline-flex; align-items: center; justify-content: center;
  flex: 0 0 22px; width: 22px; height: 22px; min-height: 22px; padding: 0; border: 0; background: transparent; }
.matrix-filter-group .matrix-filter-reset { padding: 0; min-height: 30px;
  background: transparent; border: 0; color: var(--color-primary-element); font-size: 12px; }
.matrix-filter-picker { display: flex; flex-direction: column; flex: 1; min-height: 180px;
  border-top: 1px solid var(--color-border); }
.matrix-filter-picker-heading { padding: 14px 22px 0; }
.matrix-filter-search { padding: 0 22px 10px; border-bottom: 1px solid var(--color-border); }
.matrix-filter-search input[type="search"] { width: 100%; margin: 0; }
.matrix-filter-list { overflow: auto; flex: 1; min-height: 0; padding: 8px 22px 22px; }
.matrix-filter-picker-heading, .matrix-filter-search { flex-shrink: 0; }
.matrix-filters .matrix-filter-option { display: flex; align-items: center; justify-content: space-between;
  width: 100%; min-height: 42px; gap: 9px; padding: 8px 4px; text-align: left; font-weight: 400;
  border: 0; border-bottom: 1px solid var(--color-border); border-radius: 0; background: transparent; }
.matrix-filters .matrix-filter-option:hover { background: var(--color-background-hover); }
.matrix-filter-option > span:first-child { flex: 1; }
.matrix-filters .matrix-filter-option > .material-design-icon { flex-shrink: 0; color: var(--color-text-maxcontrast); }
.matrix-page button:disabled { opacity: .45; cursor: default; }
.matrix-page button:focus-visible, .matrix-scroll:focus-visible { outline: 2px solid var(--color-primary-element);
  outline-offset: -2px; }
.matrix-table thead th { position: sticky; top: 0; z-index: 3; height: 52px; padding: 8px 14px;
  background: var(--color-background-dark); font-weight: 600; }
.matrix-table thead .matrix-sort { line-height: 1.1; }
.matrix-table thead small { display: block; margin-top: 2px; font-size: 11px; line-height: 1.1;
  color: var(--color-text-maxcontrast); }
.matrix-table thead .matrix-name { z-index: 5; background: var(--color-background-dark); }
.matrix-table thead .matrix-total { z-index: 5; background: var(--color-background-dark); }
.matrix-scroll.has-hidden-left .matrix-name::before,
.matrix-scroll.has-hidden-right .matrix-total::before { content: ""; position: absolute;
  top: 0; bottom: 0; width: 12px; pointer-events: none; }
.matrix-scroll.has-hidden-left .matrix-name::before { right: -12px;
  background: linear-gradient(to right, #0003, transparent); }
.matrix-scroll.has-hidden-right .matrix-total::before { left: -12px;
  background: linear-gradient(to left, #0003, transparent); }
.matrix-scroll.is-scrollable th, .matrix-scroll.is-scrollable td { cursor: grab; }
.matrix-scroll.is-dragging, .matrix-scroll.is-dragging * { cursor: grabbing !important; user-select: none; }
.matrix-table tbody tr:hover > th, .matrix-table tbody tr:hover > td,
.matrix-table tbody tr:focus-within > th, .matrix-table tbody tr:focus-within > td,
.matrix-table tbody tr.matrix-selected > th, .matrix-table tbody tr.matrix-selected > td {
  background: var(--color-primary-element-light); }
@container (max-width: 1180px) {
  .matrix-today { order: 1; margin-left: auto !important; }
  .matrix-meta-actions { order: 2; flex-basis: 100%; }
}
@container (max-width: 900px) {
  .matrix-controls::before { content: ""; order: 5; flex-basis: 100%; }
  .matrix-controls .matrix-today { order: 6; margin-left: 0 !important; }
  .matrix-meta-actions { order: 7; flex: 1 1 auto; }
}
@media (max-width: 800px) {
  .matrix-controls { padding: 8px 16px; gap: 8px; }
  .matrix-controls-bar .matrix-fullscreen-exit { margin: 8px 16px 8px 0; }
  .matrix-segment button { padding: 0 8px; font-size: 12px; }
  .matrix-period strong { font-size: 12px; }
  .matrix-scroll { margin: 8px; }
  .matrix-table .matrix-name { min-width: 190px; max-width: 220px; }
}
@media (max-width: 600px) {
  .matrix-toolbar { flex-wrap: nowrap; gap: 4px; padding-right: 8px; }
  .matrix-toolbar ::v-deep .breadcrumb__crumbs > li:first-child { display: none; }
  .matrix-toolbar ::v-deep .breadcrumb__crumbs > li:last-child::before { display: none; }
  .matrix-toolbar ::v-deep .button-vue { min-width: 44px; min-height: 44px; }
  .matrix-export ::v-deep .button-vue__text { position: absolute; width: 1px; height: 1px;
    overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; }
  .matrix-export ::v-deep .button-vue__wrapper::after { display: none; }
  .matrix-controls { padding: 6px 8px; gap: 4px 8px; }
  .matrix-controls-bar .matrix-fullscreen-exit { flex-basis: 44px; width: 44px; height: 44px;
    margin: 6px 8px 6px 0; }
  .matrix-controls button { min-height: 44px; }
  .matrix-period { order: 3; flex: 1; min-width: 0; }
  .matrix-period button { width: 44px; height: 44px; flex: 0 0 44px; }
  .matrix-period strong { white-space: normal; line-height: 1.2; font-size: 13px; }
  .matrix-today { width: 72px; padding: 0; }
  .matrix-segment { order: 2; flex: 1 0 calc(50% - 4px); min-width: 0; }
  .matrix-segment button { padding: 0 8px; flex: 1; font-size: 12px; }
  .matrix-checkbox { order: 4; min-height: 44px; }
  .matrix-meta-actions { gap: 4px; }
  .matrix-meta-actions button { padding: 0 4px; font-size: 12px; gap: 3px; }
  .matrix-table th, .matrix-table td { min-width: 96px; padding: 6px 10px; }
  .matrix-table .matrix-name { min-width: 148px; max-width: 148px; width: 148px; padding: 8px; }
  .matrix-table .matrix-total { display: none; }
  .matrix-table thead th { padding: 8px; }
  .matrix-table thead .matrix-name > .matrix-sort:first-child::after { display: none; }
  .matrix-table .matrix-mobile-total { position: relative; z-index: 1; display: block;
    margin-top: 4px; color: var(--color-text-maxcontrast); font-size: 12px; }
  .matrix-table .matrix-mobile-total::after { display: none; }
  .matrix-table button.matrix-mobile-total { display: flex; min-height: 30px; }
  .matrix-mobile-total .matrix-hours { min-height: 30px; }
  .matrix-name-content { gap: 4px; }
  .matrix-name-content small { display: none; }
  .matrix-table .matrix-name-button { gap: 4px; }
  .matrix-child .matrix-name-static { padding-left: 12px; }
  .matrix-details { width: 100%; border-left: 0; }
  .matrix-details header { padding: 10px 12px 10px 52px; }
  .matrix-details h2 { font-size: 18px; line-height: 1.3; }
  .matrix-details .matrix-close { width: 44px; height: 44px; min-width: 44px; }
  .matrix-details-summary { padding: 12px 16px; }
  .matrix-detail-list { padding: 0 16px 16px; }
  .matrix-entry-comment, .matrix-entry-secondary { font-size: 14px; }
  .matrix-entry-task { min-height: 44px; padding-top: 8px; }
  .matrix-filter-groups { padding: 0 16px 12px; gap: 12px; }
  .matrix-filter-chips { min-height: 44px; }
  .matrix-filter-chip { font-size: 13px; }
  .matrix-filters .matrix-filter-add, .matrix-filters .matrix-filter-all,
  .matrix-filter-group .matrix-filter-reset { min-height: 44px; }
  .matrix-filters .matrix-filter-plus { flex-basis: 44px; width: 44px; height: 44px; }
  .matrix-filter-chip button { flex-basis: 32px; width: 32px; height: 32px; }
  .matrix-filter-picker-heading { padding: 8px 16px 0; }
  .matrix-filter-search { padding: 0 16px 8px; }
  .matrix-filter-search input[type="search"] { min-height: 44px; font-size: 16px; }
  .matrix-filter-list { padding: 4px 16px 16px; }
  .matrix-filters .matrix-filter-option { min-height: 48px; }

}
@media (prefers-reduced-motion: reduce) {
  .matrix-drawer-enter-active, .matrix-drawer-leave-active { transition: none; }
}
.matrix-page.is-fullscreen { position: fixed; inset: 0; width: 100%; height: 100%; z-index: 10000; }
.matrix-page.is-fullscreen .matrix-toolbar { display: none; }
.matrix-page.is-fullscreen .matrix-scroll { margin: 0; border: 0; border-radius: 0; }
</style>

<style>
/* Nextcloud chrome lives outside the report's stacking context. */
body.done-matrix-fullscreen #header,
body.done-matrix-fullscreen #app-navigation-vue,
body.done-matrix-fullscreen .app-navigation-toggle-wrapper { visibility: hidden; }
</style>
