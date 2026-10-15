<template>
  <div class="matrix-drawer-content matrix-filters" :class="{ 'has-filter-picker': filterPicker }">
        <header>
          <h2>{{ tr('Filters') }}</h2>
          <button ref="filterClose" class="matrix-close" :aria-label="tr('Close filters')" @click="$emit('close')">
            <Close :size="20" />
          </button>
        </header>
        <div v-if="filterOptionsLoading" class="matrix-filter-summary" role="status"><VLoaderDots /> {{ tr('Loading filters…') }}</div>
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
                <VChip v-for="option in selectedFilterOptions(group.key)" :key="option.id" :size="32" variant="secondary" class="matrix-filter-chip">
                  <span>{{ option.name }}</span>
                  <button :aria-label="tr('Remove filter') + ': ' + option.name"
                    @click="toggleFilter(group.key, option.id, false)"><Close :size="14" /></button>
                </VChip>
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
  </div>
</template>
<script>
import Close from "vue-material-design-icons/Close.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import { VChip, VLoaderDots } from "@/shared/components";
import { fetchMatrixFilters } from "@/entities/reports/api";
import formatting from "./formatting";
import { visibleOptions, normalizeSelection } from "./filterState";
export default {
  name: "MatrixFiltersDrawer",
  components: { Close, Plus, VChip, VLoaderDots },
  mixins: [formatting],
  props: { filterSelections: { type: Object, required: true } },
  data: () => ({ filterOptions: null, filterOptionsLoading: false, filterOptionsError: false,
    filterPicker: null, filterSearch: { project_ids: "", user_ids: "" }, filterController: null,
    filterGroups: [{ key: "project_ids", label: "Projects" }, { key: "user_ids", label: "Employees" }],
  }),
  computed: {
    visibleFilterOptions() {
      if (!this.filterPicker) return [];
      return visibleOptions(this.filterOptions?.[this.filterPicker] || [],
        this.filterSelections[this.filterPicker], this.filterSearch[this.filterPicker], this.locale);
    },
  },
  mounted() { this.loadFilterOptions(); this.$refs.filterClose.focus(); },
  beforeDestroy() { this.filterController?.abort(); },
  methods: {
    outsideClickContext(target) {
      return { pickerOpen: !!this.filterPicker,
        insidePicker: this.$el.contains(target) && !!target.closest(".matrix-filter-picker, .matrix-filter-trigger"),
        closeButton: !!this.$refs.filterClose?.contains(target) };
    },
    dismissPicker() { this.filterPicker = null; },
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
      const options = this.filterOptions?.[key] || [];
      this.$emit("selection", key, normalizeSelection(ids, options));
    },
    toggleFilter(key, id, checked) {
      const ids = this.filterSelections[key] || [];
      const selection = checked ? [...ids, id] : ids.filter((value) => value !== id);
      this.setFilter(key, selection.length ? selection : null);
    },
  },
};
</script>
