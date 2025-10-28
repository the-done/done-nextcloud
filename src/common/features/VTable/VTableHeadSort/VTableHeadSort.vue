<template>
  <div class="v-table-dropdown__item">
    <div
      class="v-table-dropdown__label v-flex v-flex--justify-between v-cursor v-cursor--pointer"
    >
      <div class="v-flex v-flex--gap-1">
        <Sort :size="24" />
        Sort
      </div>
      <ChevronRight />
    </div>
    <div class="v-table-dropdown v-table-dropdown--sub">
      {{ /** Use div (not button), because nextcloud styles very strong */ }}
      <div
        :class="[
          'v-table-dropdown__button v-cursor v-cursor--pointer',
          isSortedAsc && 'v-table-dropdown__button--active',
        ]"
        @click="() => handleSort('ASC')"
      >
        <ArrowUpThin />
        Sort ascending
      </div>
      <div
        :class="[
          'v-table-dropdown__button v-cursor v-cursor--pointer',
          isSortedDesc && 'v-table-dropdown__button--active',
        ]"
        @click="() => handleSort('DESC')"
      >
        <ArrowDownThin />
        Sort descending
      </div>
    </div>
  </div>
</template>

<script>
import ChevronRight from "vue-material-design-icons/ChevronRight.vue";
import Sort from "vue-material-design-icons/Sort.vue";
import ArrowUpThin from "vue-material-design-icons/ArrowUpThin.vue";
import ArrowDownThin from "vue-material-design-icons/ArrowDownThin.vue";

export default {
  name: "VTableHeadSort",
  emits: ["on-sort"],
  components: {
    ChevronRight,
    Sort,
    ArrowUpThin,
    ArrowDownThin,
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
    isSortedDesc() {
      return this.item?.rules?.sort_settings?.sort === "DESC";
    },
  },
  methods: {
    handleSort(value) {
      this.$emit("on-sort", { item: this.item, value });
    },
  },
};
</script>
