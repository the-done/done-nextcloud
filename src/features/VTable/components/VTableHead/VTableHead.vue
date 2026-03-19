/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <th
    :class="[
      {
        'v-table-head': true,
        'v-table-head--sticky': stickyLeft,
      },
      customClass,
    ]"
    v-click-outside="handleCloseDropdown"
  >
    <span
      v-if="stickyLeft"
      :class="{
        'v-table-sticky-box v-table-sticky-box--left': true,
        'v-table-sticky-box--hidden': hideStickyBox,
      }"
    />
    <div
      :class="[
        'v-table-head__content',
        draggable === true && 'v-table-head__content--draggable',
        hasDropdown === true && 'v-table-head__content--clickable',
      ]"
      @click="handleToggleDropdown"
    >
      <DragVertical
        v-if="draggable === true"
        :size="20"
        data-handle="col"
        class="cursor-grab"
      />
      <span class="v-table-head__label">
        <slot />
      </span>
    </div>
    <VTableFilterDropdown
      v-if="hasDropdown"
      v-model="isDropdownActive"
      :item="item"
      @on-sort="handleSort"
      @on-create-filter="handleCreateFilter"
    />
  </th>
</template>

<script>
import DragVertical from "vue-material-design-icons/DragVertical.vue";

import { VTableFilterDropdown } from "@/shared/components";

export default {
  name: "VTableHead",
  emits: ["on-sort"],
  components: {
    DragVertical,
    VTableFilterDropdown,
  },
  props: {
    item: {
      type: Object,
      default: () => ({}),
    },
    draggable: {
      type: Boolean,
      default: false,
    },
    sortable: {
      type: Boolean,
      default: false,
    },
    filterable: {
      type: Boolean,
      default: false,
    },
    customClass: {
      type: [String, Object, Array],
      default: "",
    },
    stickyLeft: {
      type: Boolean,
      default: false,
    },
    scrolledHorizontally: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      isDropdownActive: false,
    };
  },
  computed: {
    hasDropdown() {
      return this.sortable === true || this.filterable === true;
    },
    hideStickyBox() {
      return this.stickyLeft && !this.scrolledHorizontally;
    },
  },
  methods: {
    handleCloseDropdown() {
      this.isDropdownActive = false;
    },
    handleToggleDropdown() {
      this.isDropdownActive = !this.isDropdownActive;
    },
    handleSort(payload) {
      this.handleCloseDropdown();

      this.$emit("on-sort", payload);
    },
    handleCreateFilter(payload) {
      this.handleCloseDropdown();

      this.$emit("on-create-filter", payload);
    },
  },
};
</script>
