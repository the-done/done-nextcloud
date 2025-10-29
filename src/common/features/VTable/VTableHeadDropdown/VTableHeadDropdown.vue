/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div v-if="value" class="v-table-dropdown v-table-dropdown--top">
    <VTableHeadSort :item="item" @on-sort="handleSort" />
    <VTableHeadFilter :item="item" @on-create-filter="handleCreateFilter" />
  </div>
</template>

<script>
import VTableHeadSort from "../VTableHeadSort/VTableHeadSort.vue";
import VTableHeadFilter from "../VTableHeadFilter/VTableHeadFilter.vue";

export default {
  name: "VTableHeadDropdown",
  emits: ["on-sort"],
  components: {
    VTableHeadSort,
    VTableHeadFilter,
  },
  props: {
    value: {
      type: Boolean,
      default: false,
    },
    item: {
      type: Object,
      default: () => ({}),
    },
  },
  methods: {
    handleSort(payload) {
      this.$emit("on-sort", payload);
    },
    handleCreateFilter(payload) {
      this.$emit("on-create-filter", payload);
    },
  },
};
</script>
