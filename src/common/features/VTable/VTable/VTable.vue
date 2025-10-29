/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div :class="['v-table-wrap', isDarkTheme && 'v-table-wrap--dark']">
    <VScrollArea v-if="value && value.length > 0">
      <table class="v-table">
        <template v-if="columns && columns.length">
          <thead v-if="draggable === true" class="v-table-header">
            <DraggableCol
              :value="columns"
              :animation="150"
              tag="tr"
              handle=".v-table-head__handle"
              @start="handleDragStart"
              @end="handleDragEnd"
              @input="handleUpdateColumnsOrdering"
            >
              <template v-for="col in columns">
                <VTableHead
                  v-if="col.visible !== false"
                  :key="col.key"
                  :draggable="col.draggable === false ? false : true"
                  :sortable="col.sortable === false ? false : true"
                  :filterable="col.filterable === false ? false : true"
                  :customClass="col.customClass"
                  :item="col"
                  @on-sort="handleSort"
                  @on-create-filter="handleCreateFilter"
                >
                  {{ col.label }}
                </VTableHead>
              </template>
            </DraggableCol>
          </thead>
          <VTableHeader v-else>
            <template v-for="col in columns">
              <VTableHead
                v-if="col.visible !== false"
                :key="col.key"
                :customClass="col.customClass"
              >
                {{ col.label }}
              </VTableHead>
            </template>
          </VTableHeader>
          <VTableBody :drag-in-process="isDragInProcess">
            <template v-if="value && value.length > 0">
              <VTableRow v-for="row in value" :key="row[uniqueKey]">
                <template v-for="col in columns">
                  <VTableCol
                    v-if="col.visible !== false"
                    :key="col.key"
                    :name="col.key"
                    :customClass="col.customClass"
                  >
                    <slot
                      :name="col.key"
                      :data="value"
                      :row="row"
                      :col="col"
                      :value="row[col.key]"
                    >
                      {{ row[col.key] }}
                    </slot>
                  </VTableCol>
                </template>
              </VTableRow>
            </template>
          </VTableBody>
        </template>

        <slot />
      </table>
    </VScrollArea>
    <div v-else-if="loading === true" class="v-table-full">
      <NcLoadingIcon :size="64" />
    </div>
    <div class="v-table-full" v-else>
      <NcEmptyContent
        :name="emptyContentTitle"
        :description="emptyContentDescription"
      >
        <template #icon>
          <TableSearch :size="20" />
        </template>

        <template #description>
          <slot name="emptyStateDescription" />
        </template>

        <template #action>
          <slot name="emptyContentActions" />
        </template>
      </NcEmptyContent>
    </div>
  </div>
</template>

<script>
import {
  NcEmptyContent,
  NcLoadingIcon,
  checkIfDarkTheme,
} from "@nextcloud/vue";
import DraggableCol from "vuedraggable";

import TableSearch from "vue-material-design-icons/TableSearch.vue";

import VScrollArea from "@/common/shared/components/VScrollArea/VScrollArea.vue";

import VTableHeader from "../VTableHeader/VTableHeader.vue";
import VTableHead from "../VTableHead/VTableHead.vue";
import VTableBody from "../VTableBody/VTableBody.vue";
import VTableRow from "../VTableRow/VTableRow.vue";
import VTableCol from "../VTableCol/VTableCol.vue";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "VTable",
  components: {
    DraggableCol,
    NcEmptyContent,
    NcLoadingIcon,
    TableSearch,
    VTableHeader,
    VTableHead,
    VTableBody,
    VTableRow,
    VTableCol,
    VScrollArea,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    value: {
      type: Array,
      default: () => [],
    },
    columns: {
      type: Array,
      default: () => [],
    },
    uniqueKey: {
      type: String,
      default: "id",
    },
    loading: {
      type: Boolean,
      default: false,
    },
    emptyContentTitle: {
      type: String,
      default: () => t("done", "No data"),
    },
    emptyContentDescription: {
      type: String,
      default: () => t("done", "No data found for your request"),
    },
    draggable: {
      type: Boolean,
      default: false,
    },
    filterConditionsList: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["on-update-columns-order", "on-drag-end"],
  data() {
    return {
      isDragInProcess: false,
    };
  },
  computed: {
    isDarkTheme() {
      return checkIfDarkTheme();
    },
  },
  methods: {
    handleDragStart() {
      this.isDragInProcess = true;
    },
    handleDragEnd(value) {
      const { oldIndex, newIndex } = value;
      const item = this.columns[newIndex];

      this.isDragInProcess = false;

      this.$emit("on-drag-end", { oldIndex, newIndex, item });
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
    t,
  },
};
</script>
