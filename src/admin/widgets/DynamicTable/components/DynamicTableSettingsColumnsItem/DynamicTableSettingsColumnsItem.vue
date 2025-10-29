/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div class="flex items-center justify-between pag-4">
    <div class="flex items-center gap-2">
      <DragVertical :size="20" data-handle class="cursor-grab" />
      <span> {{ item.label }} </span>
    </div>
    <NcButton
      :aria-label="contextTranslate('Visibility', context)"
      type="tertiary"
      :class="[isHidden && 'opacity-50']"
      @click="handleUpdateHiddenColumns"
    >
      <template #icon> <Eye :size="20" /></template>
    </NcButton>
  </div>
</template>

<script>
import { NcButton } from "@nextcloud/vue";

import DragVertical from "vue-material-design-icons/DragVertical.vue";
import Eye from "vue-material-design-icons/Eye.vue";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "DynamicTableSettingsColumnsItem",
  mixins: [contextualTranslationsMixin],
  components: {
    NcButton,
    DragVertical,
    Eye,
  },
  props: {
    item: {
      type: Object,
      deafult: () => ({}),
    },
    allColumnsOrdering: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["on-update-hidden-columns"],
  data() {
    return {};
  },
  computed: {
    isHidden() {
      return this.item.hidden === true;
    },
  },
  methods: {
    handleUpdateHiddenColumns() {
      const nextValue = this.allColumnsOrdering.map((column) => {
        if (column.key === this.item.key) {
          return {
            ...column,
            hidden: !this.isHidden,
          };
        }

        return column;
      });

      this.$emit("on-update-hidden-columns", {
        nextValue,
        item: this.item,
        isHidden: this.isHidden,
      });
    },
  },
};
</script>
