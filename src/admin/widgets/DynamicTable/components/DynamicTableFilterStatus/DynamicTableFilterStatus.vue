/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div class="relative" v-click-outside="handleCloseDropdown">
    <DynamicTableBadge :active="isActive" @click="handleToggleDropdown">
      <FilterVariant :size="16" />
      {{ item.title }}
      <ChevronDown :size="16" />
    </DynamicTableBadge>
    <div
      v-if="isDropdownActive"
      class="v-table-dropdown"
      :style="{ width: '200px' }"
    >
      <VDropdown
        v-model="conditionValue"
        :options="filterConditionsList"
        ariaLabel="Condition"
        placeholder="Select condition"
      />
      <DynamicTableMultipleInput v-if="isMultiple" v-model="inputValue" />
      <VTextField
        v-else-if="showInput"
        v-model="inputValue"
        placeholder="Type a value"
      />
      <div class="v-mt-1 v-flex v-flex--justify-end">
        <VButton size="small" variant="error" @click="handleDelete">
          <template #icon>
            <TrashCanOutline :size="16" />
          </template>
        </VButton>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState } from "pinia";

import ChevronDown from "vue-material-design-icons/ChevronDown.vue";
import FilterVariant from "vue-material-design-icons/FilterVariant.vue";
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import { useDynamicTableStore } from "@/admin/app/store/dynamicTable";
import { debounceMixin } from "@/admin/shared/lib/mixins/debounceMixin";

import { DynamicTableBadge } from "../DynamicTableBadge";
import { DynamicTableSortItem } from "../DynamicTableSortItem";
import { DynamicTableMultipleInput } from "../DynamicTableMultipleInput";

import VDropdown from "@/common/shared/components/VDropdown/VDropdown.vue";
import VTextField from "@/common/shared/components/VTextField/VTextField.vue";
import VButton from "@/common/shared/components/VButton/VButton.vue";

export default {
  name: "DynamicTableFilterStatus",
  components: {
    ChevronDown,
    FilterVariant,
    TrashCanOutline,
    DynamicTableBadge,
    DynamicTableSortItem,
    DynamicTableMultipleInput,
    VDropdown,
    VTextField,
    VButton,
  },
  props: {
    item: {
      type: Object,
      default: () => ({}),
    },
    allColumnsOrdering: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["on-change", "on-delete"],
  mixins: [debounceMixin],
  data() {
    return {
      isDropdownActive: false,
    };
  },
  computed: {
    ...mapState(useDynamicTableStore, ["filterConditionsList"]),
    conditionValue: {
      get() {
        const condition = this.item?.rules?.filter_settings?.condition;

        if (!condition) {
          return null;
        }

        const filterItem = this.filterConditionsList.find(
          (item) => String(item.value) === String(condition)
        );

        if (filterItem) {
          return filterItem;
        }

        return null;
      },
      set(item) {
        if (!item) {
          return;
        }

        const { value, showInput, multiple } = item;

        this.item.rules.filter_settings.condition = value;

        if (multiple === true) {
          if (Array.isArray(this.inputValue)) {
            this.handleChange();

            return;
          }

          this.inputValue = [];

          return;
        }

        if (showInput === false) {
          this.inputValue = "";

          return;
        }

        if (Array.isArray(this.inputValue) === true) {
          this.inputValue = "";

          return;
        }

        if (!this.inputValue) {
          return;
        }

        this.handleChange();
      },
    },
    inputValue: {
      get() {
        const value = this.item?.rules?.filter_settings?.value;

        return value || "";
      },
      set(value) {
        this.item.rules.filter_settings.value = value;

        if (Array.isArray(value) && value.length === 0) {
          return;
        }

        this.handleInputValue();
      },
    },
    showInput() {
      if (!this.conditionValue) {
        return false;
      }

      return this.conditionValue.showInput ?? true;
    },
    isMultiple() {
      if (!this.conditionValue) {
        return false;
      }

      return this.conditionValue.multiple ?? false;
    },
    isActive() {
      return this.inputValue !== "" || this.conditionValue?.showInput === false;
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
    handleChange() {
      this.$emit("on-change", {
        item: this.item,
        value: this.inputValue,
        conditionValue: this.conditionValue,
      });
    },
    handleDelete() {
      if (this.item?.rules?.filter_settings?.slug) {
        this.$emit("on-delete", this.item);

        return;
      }

      this.item.rules.filter_settings = undefined;
    },
  },
  created() {
    this.handleInputValue = this.debounce(this.handleChange, 1000);
  },
};
</script>
