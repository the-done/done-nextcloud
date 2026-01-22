/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <div v-if="value" class="v-table-dropdown v-table-dropdown--top">
    <VTableHeadSort :item="item" @on-sort="handleSort" />
    <VTableFilterContent :item="item" @on-create-filter="handleCreateFilter" />
  </div>
</template>

<script>
import { VTableHeadSort } from "./components/VTableHeadSort";
import { VTableFilterContent } from "./components/VTableFilterContent";

export default {
  name: "VTableFilterDropdown",
  emits: ["on-sort"],
  components: {
    VTableHeadSort,
    VTableFilterContent,
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
