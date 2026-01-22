/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <div class="flex flex-col gap-2 p-2">
    <NcButton type="tertiary" alignment="start" @click="handleGoBack">
      <template #icon>
        <ArrowLeft />
      </template>
      {{ contextTranslate("Back") }}
    </NcButton>
    <NcRadioGroup
      :model-value="viewMode"
      hide-label
      @update:modelValue="handleUpdateViewMode"
    >
      <NcRadioGroupButton
        :aria-label="contextTranslate('Table')"
        :label="contextTranslate('Table')"
        value="table"
      />
      <NcRadioGroupButton
        :aria-label="contextTranslate('Card')"
        :label="contextTranslate('Card')"
        value="card"
      />
    </NcRadioGroup>
  </div>
</template>

<script>
import { mapState, mapActions } from "pinia";
import Draggable from "vuedraggable";

import { NcButton, NcRadioGroup, NcRadioGroupButton } from "@nextcloud/vue";

import ArrowLeft from "vue-material-design-icons/ArrowLeft.vue";

import { useDynamicTableStore } from "@/app/store/dynamicTable";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "DynamicTableSettingsAppearance",
  mixins: [contextualTranslationsMixin],
  components: {
    Draggable,
    NcButton,
    NcRadioGroup,
    NcRadioGroupButton,
    ArrowLeft,
  },
  emits: ["on-back", "on-update-view-mode"],
  props: {
    source: {
      type: Number,
      default: null,
    },
  },
  computed: {
    ...mapState(useDynamicTableStore, ["getTableBySource"]),
    tableData() {
      return this.getTableBySource(this.source);
    },
    viewMode() {
      return this.tableData?.viewMode || "table";
    },
  },
  methods: {
    ...mapActions(useDynamicTableStore, ["setViewMode"]),
    handleGoBack() {
      this.$emit("on-back");
    },
    handleUpdateViewMode(value) {
      this.setViewMode(this.source, value);
    },
  },
};
</script>
