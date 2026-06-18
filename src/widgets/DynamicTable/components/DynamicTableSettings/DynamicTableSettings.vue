/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <div class="dynamic-table-settings">
    <!--
      :focus-trap="false" — this popover is a non-interactive hover label, so it
      has no tabbable nodes; the default focus trap would otherwise throw
      "focus-trap must have at least one container with at least one tabbable node".
    -->
    <NcPopover :triggers="['hover']" :focus-trap="false">
      <template #trigger>
        <NcButton type="tertiary" @click="handleOpenAside">
          <template #icon>
            <Tune />
          </template>
        </NcButton>
      </template>
      <template #default
        ><div class="p-2">{{ contextTranslate("Settings", context) }}</div>
      </template>
    </NcPopover>
    <VAside :value="active" @input="handleCloseAside">
      <template #title>
        {{ contextTranslate("Table settings", context) }}
      </template>
      <DynamicTableSettingsMenu
        v-if="activeTab === 'menu'"
        :activeTab.sync="activeTab"
      />
      <DynamicTableSettingsColumns
        v-else-if="activeTab === 'columns-settings'"
        :all-columns-ordering="allColumnsOrdering"
        @on-back="handleBackToMenu"
        @on-update-columns-ordering="handleUpdateColumnsOrdering"
        @on-update-hidden-columns="handleUpdateHiddenColumns"
        @on-delete-all-columns-ordering="handleDeleteAllColumnsOrdering"
      />
      <DynamicTableSettingsAppearance
        v-else-if="activeTab === 'appearance-settings'"
        :source="source"
        @on-back="handleBackToMenu"
      />
    </VAside>
  </div>
</template>

<script>
import { NcButton, NcPopover } from "@nextcloud/vue";

import Tune from "vue-material-design-icons/Tune.vue";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { VAside } from "@/shared/components";

import { DynamicTableSettingsMenu } from "../DynamicTableSettingsMenu";
import { DynamicTableSettingsColumns } from "../DynamicTableSettingsColumns";
import { DynamicTableSettingsAppearance } from "../DynamicTableSettingsAppearance";

export default {
  name: "DynamicTableSettings",
  mixins: [contextualTranslationsMixin],
  components: {
    NcButton,
    NcPopover,
    Tune,
    VAside,
    DynamicTableSettingsMenu,
    DynamicTableSettingsColumns,
    DynamicTableSettingsAppearance,
  },
  props: {
    allColumnsOrdering: {
      type: Array,
      default: () => [],
    },
    hiddenColumns: {
      type: Array,
      default: () => [],
    },
    source: {
      type: Number,
      default: null,
    },
  },
  emits: [
    "on-update-column-ordering",
    "on-update-hidden-columns",
    "on-delete-all-columns-ordering",
    "on-update-view-mode",
  ],
  data() {
    return {
      active: false,
      activeTab: "menu",
    };
  },
  computed: {},
  methods: {
    handleOpenAside() {
      this.active = true;
    },
    handleCloseAside() {
      this.active = false;
    },
    handleSetActiveTab(value) {
      this.activeTab = value;
    },
    handleBackToMenu() {
      this.handleSetActiveTab("menu");
    },
    handleUpdateColumnsOrdering(value) {
      this.$emit("on-update-column-ordering", value);
    },
    handleUpdateHiddenColumns(value) {
      this.$emit("on-update-hidden-columns", value);
    },
    handleDeleteAllColumnsOrdering() {
      this.$emit("on-delete-all-columns-ordering");
    },
    handleUpdateViewMode(value) {
      this.$emit("on-update-view-mode", value);
    },
  },
};
</script>

<style scoped>
.dynamic-table-settings__sidebar {
  position: fixed;
  top: 0;
  right: 0;
  width: 100%;
  max-width: 300px;
  background-color: var(--color-main-background);
  box-shadow: 0px 0 3px 1px var(--color-box-shadow);
}
</style>
