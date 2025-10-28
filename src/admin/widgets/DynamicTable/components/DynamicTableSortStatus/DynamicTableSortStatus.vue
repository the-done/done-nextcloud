<template>
  <div class="flex gap-2 items-center">
    <div class="relative" v-click-outside="handleCloseDropdown">
      <DynamicTableBadge
        :active="sortIsExist"
        :disabled="!sortIsExist"
        @click="handleToggleDropdown"
      >
        <Sort :size="16" />
        {{ contextTranslate("Number of sorts applied", context) }}:
        {{ sortCount }}
        <ChevronDown v-if="sortIsExist" :size="16" />
      </DynamicTableBadge>
      <div
        v-if="isDropdownActive && sortCount > 0"
        class="v-table-dropdown p-0 v-flex--gap-none"
      >
        <template v-if="sortIsExist">
          <Draggable
            :value="list"
            :animation="150"
            handle=".dynamic-table-sort-item__handle"
            @input="handleUpdateSortOrdering"
          >
            <DynamicTableSortItem
              v-for="(item, key) in list"
              :key="key"
              :item="item"
              @on-delete="handleDelete"
              @on-change="handleChange"
            />
          </Draggable>
        </template>
      </div>
    </div>
    <VButton
      v-if="sortCount > 0"
      size="small"
      variant="tertiary"
      @click="handelDeleteAll"
    >
      <template #icon>
        <Close :size="16" />
      </template>
    </VButton>
  </div>
</template>

<script>
import Draggable from "vuedraggable";

import ChevronDown from "vue-material-design-icons/ChevronDown.vue";
import Sort from "vue-material-design-icons/Sort.vue";
import Close from "vue-material-design-icons/Close.vue";

import VButton from "@/common/shared/components/VButton/VButton.vue";

import { DynamicTableBadge } from "../DynamicTableBadge";
import { DynamicTableSortItem } from "../DynamicTableSortItem";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "DynamicTableSortStatus",
  components: {
    Draggable,
    ChevronDown,
    Sort,
    Close,
    VButton,
    DynamicTableBadge,
    DynamicTableSortItem,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    allColumnsOrdering: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["on-delete", "on-change", "on-update-sort-ordering", "on-delete-all"],
  data() {
    return {
      isDropdownActive: false,
    };
  },
  computed: {
    list() {
      return this.allColumnsOrdering
        .filter((item) => item.rules?.sort_settings)
        .sort(
          (a, b) =>
            a.rules.sort_settings.sort_ordering -
            b.rules.sort_settings.sort_ordering
        );
    },
    sortCount() {
      if (!this.list) {
        return 0;
      }

      return Object.keys(this.list).length;
    },
    sortIsExist() {
      return this.sortCount > 0;
    },
  },
  methods: {
    handleToggleDropdown() {
      this.isDropdownActive = !this.isDropdownActive;
    },
    handleCloseDropdown() {
      this.isDropdownActive = false;
    },
    handleDelete(item) {
      this.$emit("on-delete", item);
    },
    handelDeleteAll() {
      this.$emit("on-delete-all");
    },
    handleChange(payload) {
      this.$emit("on-change", payload);
    },
    handleUpdateSortOrdering(payload) {
      this.$emit("on-update-sort-ordering", payload);
    },
  },
};
</script>
