/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <div
    :class="[
      'flex flex-col gap-2 p-4',
      'shadow-lg border border-(--color-border) bg-(--color-main-background)',
    ]"
  >
    <DraggableItem
      :value="columns"
      :animation="150"
      tag="div"
      handle="[data-handle=row]"
      @start="handleDragStart"
      @end="handleDragEnd"
      @input="handleUpdateColumnsOrdering"
    >
      <template v-for="col in columns">
        <div
          v-if="col.visible !== false"
          :key="col.key"
          class="flex gap-4 items-start"
        >
          <div
            v-if="draggable === true && col.draggable !== false"
            data-handle="row"
            class="w-4"
          >
            <DragVertical :size="20" class="cursor-grab" />
          </div>
          <VTableCardField
            :col="col"
            :row="row"
            :data="data"
            @on-sort="handleSort"
            @on-create-filter="handleCreateFilter"
          >
            <slot
              :name="col.key"
              :data="data"
              :row="row"
              :col="col"
              :value="row[col.key]"
            >
              {{ row[col.key] }}
            </slot>
          </VTableCardField>
        </div>
      </template>
    </DraggableItem>
  </div>
</template>

<script>
import DraggableItem from "vuedraggable";
import DragVertical from "vue-material-design-icons/DragVertical.vue";

import { VTableCardField } from "./components/VTableCardField";

export default {
  name: "VTableCard",
  components: {
    DraggableItem,
    DragVertical,
    VTableCardField,
  },
  props: {
    row: {
      type: Object,
      required: true,
    },
    columns: {
      type: Array,
      required: true,
    },
    data: {
      type: Array,
      required: true,
    },
    draggable: {
      type: Boolean,
      default: false,
    },
  },
  emits: [
    "on-update-columns-ordering",
    "on-drag-start",
    "on-drag-end",
    "on-sort",
    "on-create-filter",
  ],
  methods: {
    handleDragStart() {
      this.$emit("on-drag-start");
    },
    handleDragEnd(value) {
      this.$emit("on-drag-end", value);
    },
    handleUpdateColumnsOrdering(value) {
      this.$emit("on-update-columns-ordering", value);
    },
    handleSort(payload) {
      this.$emit("on-sort", payload);
    },
    handleCreateFilter(payload) {
      this.$emit("on-create-filter", payload);
    },
  },
};
</script>
