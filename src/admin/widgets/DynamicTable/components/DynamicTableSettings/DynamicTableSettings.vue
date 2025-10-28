<template>
  <div class="dynamic-table-settings">
    <NcPopover :triggers="['hover']">
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
    </VAside>
  </div>
</template>

<script>
import { NcButton, NcPopover } from "@nextcloud/vue";

import Tune from "vue-material-design-icons/Tune.vue";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { VAside } from "@/common/shared/components/VAside";

import { DynamicTableSettingsMenu } from "../DynamicTableSettingsMenu";
import { DynamicTableSettingsColumns } from "../DynamicTableSettingsColumns";

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
  },
  emits: ["on-update-column-ordering", "on-update-hidden-columns"],
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
