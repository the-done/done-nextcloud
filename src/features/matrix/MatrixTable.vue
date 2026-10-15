<template>
    <VScrollArea class="matrix-scroll" tabindex="0"
      :class="{ 'is-scrollable': canDrag, 'is-dragging': drag,
        'has-hidden-left': hasHiddenLeft, 'has-hidden-right': hasHiddenRight }"
      :aria-label="tr('Time allocation table')" @scroll.passive.native="measureScroll"
      @pointerdown.native="startDrag" @pointermove.native="moveDrag" @pointerup.native="endDrag"
      @pointercancel.native="endDrag" @lostpointercapture.native="endDrag">
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
                <button class="matrix-name-button" :aria-expanded="!!expanded[row.id]" @click="$emit('toggle', row.id)">
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
                  @click="$emit('details', row, matrix, 'total', $event)">{{ hours(row.total) }} {{ tr('h') }}</button>
                <span v-else>—</span>
              </div>
            </th>
            <td v-for="(minutes, index) in row.cells" :key="matrix.columns[index].start"
              :class="{ weekend: isWeekend(matrix.columns[index]), 'matrix-zero': !minutes }">
              <button v-if="minutes" class="matrix-hours"
                :class="{ selected: selected && selected.key === row.id + ':' + index }"
                :aria-label="cellLabel(row, matrix.columns[index], minutes)"
                @click="$emit('details', row, matrix.columns[index], index, $event)">
                {{ hours(minutes) }}
              </button>
              <span v-else>—</span>
            </td>
            <td class="matrix-total">
              <button v-if="row.total" class="matrix-hours" :aria-label="cellLabel(row, matrix, row.total)"
                @click="$emit('details', row, matrix, 'total', $event)">{{ hours(row.total) }}</button>
              <span v-else>—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </VScrollArea>
</template>
<script>
import { VScrollArea } from "@/shared/components";
import ChevronRight from "vue-material-design-icons/ChevronRight.vue";
import ArrowUp from "vue-material-design-icons/ArrowUp.vue";
import ArrowDown from "vue-material-design-icons/ArrowDown.vue";
import SwapVertical from "vue-material-design-icons/SwapVertical.vue";
import formatting from "./formatting";
import scroll from "./scroll";
export default {
  name: "MatrixTable",
  components: { VScrollArea, ChevronRight, ArrowUp, ArrowDown, SwapVertical },
  mixins: [formatting, scroll],
  props: {
    matrix: { type: Object, required: true }, expanded: { type: Object, required: true },
    selected: { type: Object, default: null }, mode: String, period: String, byDates: Boolean,
    sortKey: String, sortDirection: String, periodTitle: String, isMobile: Boolean,
  },
  computed: {
    visibleRows() {
      if (!this.matrix) return [];
      const result = [{ ...this.matrix.company, depth: 0 }];
      this.matrix.rows.forEach((row) => {
        result.push({ ...row, depth: 0 });
        if (this.expanded[row.id]) row.children.forEach((child) => result.push({ ...child, depth: 1 }));
      });
      return result;
    },
  },
  methods: {
    sortState(key) {
      return key !== this.sortKey ? "none" : this.sortDirection === "asc" ? "ascending" : "descending";
    },
    sortIcon(key) {
      return key !== this.sortKey ? "SwapVertical" : this.sortDirection === "asc" ? "ArrowUp" : "ArrowDown";
    },
    sortBy(key) {
      const direction = key === this.sortKey ? (this.sortDirection === "asc" ? "desc" : "asc")
        : key === "name" ? "asc" : "desc";
      this.$emit("sort", { sort: key, direction });
    },
  },
};
</script>
