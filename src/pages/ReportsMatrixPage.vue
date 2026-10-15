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
          :disabled="exporting || loading || !matrix || !matrix.rows.length">
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
    <p v-if="exportError" class="matrix-error" role="alert">{{ exportError }}</p>
    <div v-if="error" class="matrix-error" role="alert">
      {{ error }} <button @click="load">{{ tr('Retry') }}</button>
    </div>
    <div v-if="loading" class="matrix-loading" role="status"><VLoaderDots /> {{ tr('Loading report…') }}</div>
    <VEmptyState v-else-if="matrix && !matrix.rows.length" class="matrix-empty"
      title="No time entries in this period" caption="Use the arrows to find a period with data." />
    <MatrixTable v-else-if="matrix" ref="matrixTable" :matrix="matrix" :expanded="expanded" :selected="selected"
      :mode="mode" :period="period" :by-dates="byDates" :sort-key="sortKey" :sort-direction="sortDirection"
      :period-title="periodTitle" :is-mobile="isMobile" @sort="change" @toggle="toggle" @details="openDetails" />

    <transition name="matrix-drawer">
      <VAside v-if="filtersOpen || selected" ref="drawer" class="matrix-details"
        :value="true" :width="480" :show-header="false"
        body-class="matrix-drawer-body" :id="filtersOpen ? 'matrix-filters' : null"
        :aria-label="tr(filtersOpen ? 'Filters' : 'Time entry details')" @input="closePanel">
        <MatrixFiltersDrawer v-if="filtersOpen" ref="filtersDrawer" :filter-selections="filterSelections"
          @selection="setFilter" @close="closeFilters" />
        <MatrixDetailsDrawer v-else-if="selected" :key="selected.key" :selected="selected"
          :filter-params="filterParams" @close="closeDetails" />
      </VAside>
    </transition>
  </section>
</template>

<script>
import { NcActions, NcActionButton, NcBreadcrumbs, NcBreadcrumb, NcButton } from "@nextcloud/vue";
import ChevronRight from "vue-material-design-icons/ChevronRight.vue";
import ChevronLeft from "vue-material-design-icons/ChevronLeft.vue";
import Download from "vue-material-design-icons/Download.vue";
import Close from "vue-material-design-icons/Close.vue";
import FileChart from "vue-material-design-icons/FileChart.vue";
import FilterVariant from "vue-material-design-icons/FilterVariant.vue";
import UnfoldMoreHorizontal from "vue-material-design-icons/UnfoldMoreHorizontal.vue";
import UnfoldLessHorizontal from "vue-material-design-icons/UnfoldLessHorizontal.vue";
import Fullscreen from "vue-material-design-icons/Fullscreen.vue";
import { VToolbar, VAside, VEmptyState, VLoaderDots } from "@/shared/components";
import { fetchMatrix, exportMatrix } from "@/entities/reports/api";
import MatrixTable from "@/features/matrix/MatrixTable.vue";
import MatrixFiltersDrawer from "@/features/matrix/MatrixFiltersDrawer.vue";
import MatrixDetailsDrawer from "@/features/matrix/MatrixDetailsDrawer.vue";
import formatting from "@/features/matrix/formatting";
import fullscreen from "@/features/matrix/fullscreen";
import { outsideClickAction } from "@/features/matrix/filterState";
const iso = (date) => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, "0"),
  String(date.getDate()).padStart(2, "0")].join("-");
export default {
  name: "ReportsMatrixPage",
  components: { NcActions, NcActionButton, NcBreadcrumbs, NcBreadcrumb, NcButton, VToolbar,
    VAside, VEmptyState, VLoaderDots, MatrixTable, MatrixFiltersDrawer, MatrixDetailsDrawer,
    ChevronRight, ChevronLeft, Download, Close, FileChart, Fullscreen,
    FilterVariant, UnfoldMoreHorizontal, UnfoldLessHorizontal },
  mixins: [formatting, fullscreen],
  data: () => ({ matrix: null, loading: false, error: "", expanded: {}, sequence: 0,
    selected: null, returnFocus: null, exportMenuOpen: false, exporting: false, exportError: "",
    isMobile: window.matchMedia("(max-width: 600px)").matches, filtersOpen: false, controller: null,
    filterGroups: [{ key: "project_ids" }, { key: "user_ids" }],
  }),
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
    allExpanded() {
      return this.matrix && this.matrix.rows.length && this.matrix.rows.every((row) => this.expanded[row.id]);
    },
  },
  watch: { "$route.query": { immediate: true, handler() { this.load(); } } },
  mounted() {
    this.mobileQuery = window.matchMedia("(max-width: 600px)");
    this.mobileQuery.addEventListener("change", this.onMobileChange);
    document.addEventListener("click", this.onDrawerOutsideClick, true);
  },
  beforeDestroy() {
    this.mobileQuery?.removeEventListener("change", this.onMobileChange);
    document.removeEventListener("click", this.onDrawerOutsideClick, true);
    this.sequence++;
    this.controller?.abort();
  },
  methods: {
    onMobileChange(event) {
      this.isMobile = event.matches;
    },
    closePanel() {
      if (this.filtersOpen) this.closeFilters();
      else this.closeDetails();
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
      const scrollLeft = this.$refs.matrixTable?.$el?.scrollLeft || 0;
      this.$refs.matrixTable?.endDrag();
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
            if (this.$refs.matrixTable) this.$refs.matrixTable.$el.scrollLeft = scrollLeft;
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
    async exportView(format) {
      if (this.exporting) return;
      this.exportMenuOpen = false;
      this.exporting = true;
      this.exportError = "";
      try {
        const expanded = this.matrix.rows.filter((row) => this.expanded[row.id]).map((row) => row.id);
        const { data } = await exportMatrix({ ...this.request, format }, expanded);
        const url = URL.createObjectURL(data);
        const link = document.createElement("a");
        link.href = url;
        link.download = `done-table-${this.mode}-${this.matrix.start}.${format}`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        // Let the browser start consuming the blob before releasing it.
        setTimeout(() => URL.revokeObjectURL(url), 60000);
      } catch (error) {
        this.exportError = this.tr("Could not export the report. Please try again.");
      } finally { this.exporting = false; }
    },
    openDetails(row, column, index, event) {
      this.filtersOpen = false;
      this.returnFocus = event.currentTarget;
      this.selected = { name: row.id === "company" ? this.tr("Company total") : row.name,
        profile: this.rowProfile(row), employeeView: this.mode === "employees" || !!row.user_id,
        "user_id": row.user_id || "", "project_id": row.project_id || "",
        start: column.start, end: column.end, minutes: index === "total" ? row.total : row.cells[index],
        rowId: row.id, key: `${row.id}:${index}` };
    },
    today() { return iso(new Date()); },
    openFilters() { this.closeDetails(false); this.filtersOpen = true; },
    closeFilters(restoreFocus = true) {
      this.filtersOpen = false;
      if (restoreFocus) this.$nextTick(() => this.$refs.filterButton?.focus());
    },
    closeDetails(restoreFocus = true) {
      this.selected = null;
      if (restoreFocus) this.returnFocus?.focus();
      this.returnFocus = null;
    },
    setFilter(key, ids) {
      const query = { ...this.$route.query };
      if (ids === null) delete query[key];
      else query[key] = [...new Set(ids)].sort().join(",");
      this.$router.replace({ name: "report-matrix", query }).catch(() => {});
    },
    onDrawerOutsideClick(event) {
      if (event.button !== 0 || (!this.filtersOpen && !this.selected)) return;
      const target = event.target;
      const action = outsideClickAction({
        ...this.$refs.filtersDrawer?.outsideClickContext(target),
        insideDrawer: this.$refs.drawer?.$el.contains(target),
        switchesDrawer: this.$refs.filterButton?.contains(target)
          || this.$el.contains(target) && !!target.closest("button.matrix-hours"),
      });
      if (action === "picker") {
        this.$refs.filtersDrawer.dismissPicker();
        event.preventDefault();
        event.stopPropagation();
        return;
      }
      if (action !== "drawer") return;
      if (this.filtersOpen) this.closeFilters(false);
      else this.closeDetails(false);
    },
  },
};
</script>

<style src="../features/matrix/matrix.css"></style>

<style>
/* Nextcloud chrome lives outside the report's stacking context. */
body.done-matrix-fullscreen #header,
body.done-matrix-fullscreen #app-navigation-vue,
body.done-matrix-fullscreen .app-navigation-toggle-wrapper { visibility: hidden; }
</style>
