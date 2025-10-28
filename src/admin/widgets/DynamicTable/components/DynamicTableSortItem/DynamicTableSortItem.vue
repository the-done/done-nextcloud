<template>
  <div class="dynamic-table-sort-item">
    <DragVertical :size="20" class="dynamic-table-sort-item__handle" />
    <div class="v-flex v-flex--gap-1">
      <VButton size="small" variant="tertiary" @click="handleChangeSort">
        <template #icon>
          <ArrowUpThin v-if="isSortedAsc" :size="16" />
          <ArrowDownThin v-else :size="16" />
        </template>
      </VButton>
      {{ item.title }}
    </div>

    <VButton size="small" variant="tertiary" @click="handleDelete" style="margin-left:auto">
      <template #icon>
        <Close :size="16"/>
      </template>
    </VButton>
  </div>
</template>

<script>
import DragVertical from "vue-material-design-icons/DragVertical.vue";
import ArrowUpThin from "vue-material-design-icons/ArrowUpThin.vue";
import ArrowDownThin from "vue-material-design-icons/ArrowDownThin.vue";
import Close from "vue-material-design-icons/Close.vue";

import VButton from "@/common/shared/components/VButton/VButton.vue";

export default {
  name: "DynamicTableSortItem",
  emits: ["on-delete"],
  components: {
    DragVertical,
    ArrowUpThin,
    ArrowDownThin,
    Close,
    VButton,
  },
  props: {
    item: {
      type: Object,
      default: () => ({}),
    },
  },
  computed: {
    isSortedAsc() {
      return this.item?.rules?.sort_settings?.sort === "ASC";
    },
  },
  methods: {
    handleChangeSort() {
      const nextValue = this.isSortedAsc ? "DESC" : "ASC";

      this.$emit("on-change", { item: this.item, value: nextValue });
    },
    handleDelete() {
      this.$emit("on-delete", this.item);
    },
  },
};
</script>

<style scoped>
.dynamic-table-sort-item {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 4px 8px;
}

.dynamic-table-sort-item__handle {
  cursor: grab;
}

.dynamic-table-sort-item + .dynamic-table-sort-item {
  border-top: 1px solid var(--color-border);
}
</style>
