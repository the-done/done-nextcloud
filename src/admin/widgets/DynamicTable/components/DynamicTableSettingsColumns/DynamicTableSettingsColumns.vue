<template>
  <div class="flex flex-col gap-2 p-2">
    <div class="flex justify-between">
      <NcButton type="tertiary" alignment="start" @click="handleGoBack">
        <template #icon>
          <ArrowLeft />
        </template>
        {{ contextTranslate("Back", context) }}
      </NcButton>
      <NcButton
        type="tertiary"
        alignment="start"
        class="text-(--color-primary)!"
        @click="handleDeleteAllColumnsOrdering"
      >
        {{ contextTranslate("Reset sorting", context) }}
      </NcButton>
    </div>
    <Draggable
      :value="columns"
      :animation="150"
      handle="[data-handle]"
      class="flex flex-col gap-4"
      @input="handleUpdateColumnsOrdering"
    >
      <DynamicTableSettingsColumnsItem
        v-for="item in columns"
        :key="item.key"
        :item="item"
        :all-columns-ordering="allColumnsOrdering"
        @on-update-hidden-columns="handleUpdateHiddenColumns"
      />
    </Draggable>
  </div>
</template>

<script>
import Draggable from "vuedraggable";

import { NcButton } from "@nextcloud/vue";

import ArrowLeft from "vue-material-design-icons/ArrowLeft.vue";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { DynamicTableSettingsColumnsItem } from "../DynamicTableSettingsColumnsItem";

export default {
  name: "DynamicTableSettingsColumns",
  mixins: [contextualTranslationsMixin],
  components: {
    Draggable,
    NcButton,
    ArrowLeft,
    DynamicTableSettingsColumnsItem,
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
  emits: [
    "on-back",
    "on-update-columns-ordering",
    "on-update-hidden-columns",
    "on-delete-all-columns-ordering",
  ],
  data() {
    return {};
  },
  computed: {
    columns() {
      return this.allColumnsOrdering
        .filter((item) => item.hideable !== false && item.draggable !== false)
        .map((item) => {
          const { key, title } = item;

          return {
            ...item,
            label: title || key,
          };
        });
    },
  },
  methods: {
    handleGoBack() {
      this.$emit("on-back");
    },
    handleUpdateColumnsOrdering(value) {
      this.$emit("on-update-columns-ordering", value);
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
