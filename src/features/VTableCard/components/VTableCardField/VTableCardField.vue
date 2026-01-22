/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <div v-click-outside="handleCloseDropdown" class="flex flex-col">
    <div
      v-if="col.label"
      class="relative text-sm text-(--color-text-maxcontrast)"
    >
      <span class="cursor-pointer" @click="handleToggleDropdown">
        {{ col.label }}
      </span>
      <VTableFilterDropdown
        v-model="isDropdownActive"
        :item="col"
        @on-sort="handleSort"
        @on-create-filter="handleCreateFilter"
      />
    </div>
    <slot />
  </div>
</template>

<script>
import { VTableFilterDropdown } from "@/shared/components";

export default {
  name: "VTableCardField",
  components: {
    VTableFilterDropdown,
  },
  props: {
    row: {
      type: Object,
      required: true,
    },
    col: {
      type: Object,
      required: true,
    },
    data: {
      type: Array,
      required: true,
    },
  },
  emits: ["on-sort", "on-create-filter"],
  data() {
    return {
      isDropdownActive: false,
    };
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
