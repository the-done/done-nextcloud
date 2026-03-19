/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VAside :value="active" :width="768" @input="handleClose">
    <template #title>
      {{ contextTranslate("Form my day", context) }}
    </template>

    <div class="relative h-full flex flex-col">
      <VLoader v-if="loading" absolute />

      <div
        v-else-if="!reports.length"
        class="p-4 text-(--color-text-maxcontrast)"
      >
        {{ contextTranslate("No data found", context) }}
      </div>
      <template v-else>
        <div
          v-for="(item, index) in reports"
          :key="index"
          class="border-b border-(--color-border)"
        >
          <GeneratedReportsItem
            :item="item"
            :loading="loaders.includes(item.uuid)"
            :project-options="projectOptions"
            @on-decline="handleDecline"
            @on-accept="handleAccept"
          />
        </div>
      </template>
    </div>
  </VAside>
</template>

<script>
import { VAside, VLoader } from "@/shared/components";

import { GeneratedReportsItem } from "./components/GeneratedReportsItem";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "GeneratedReportsAside",
  components: {
    VAside,
    VLoader,
    GeneratedReportsItem,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    active: {
      type: Boolean,
      default: false,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    reports: {
      type: Array,
      default: () => [],
    },
    loaders: {
      type: Array,
      deafult: [],
    },
    projectOptions: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["on-close"],
  data: () => ({
    context: "user/time-tracking",
  }),
  methods: {
    handleClose() {
      this.$emit("on-close");
    },
    handleDecline(modelData) {
      this.$emit("on-decline", modelData);
    },
    handleAccept(modelData) {
      this.$emit("on-accept", modelData);
    },
  },
};
</script>
