<template>
  <th
    :class="['v-table-head', customClass]"
    v-click-outside="handleCloseDropdown"
  >
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
        class="v-table-head__handle"
      />
      <span class="v-table-head__label">
        <slot />
      </span>
    </div>
    <template v-if="hasDropdown">
      <VTableHeadDropdown
        v-model="isDropdownActive"
        :item="item"
        @on-sort="handleSort"
        @on-create-filter="handleCreateFilter"
      />
    </template>
  </th>
</template>

<script>
import DragVertical from "vue-material-design-icons/DragVertical.vue";

import VTableHeadDropdown from "../VTableHeadDropdown/VTableHeadDropdown.vue";

export default {
  name: "VTableHead",
  emits: ["on-sort"],
  components: {
    DragVertical,
    VTableHeadDropdown,
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
