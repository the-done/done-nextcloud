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
          v-if="sourceTabs.length > 1"
          class="flex flex-wrap gap-1 p-2 border-b border-(--color-border)"
        >
          <button
            v-for="tab in sourceTabs"
            :key="tab.key"
            type="button"
            :class="['source-tab', { 'source-tab--active': tab.key === activeSource }]"
            @click="selectSource(tab.key)"
          >
            {{ tab.title }}
            <span class="source-tab__count">{{ tab.count }}</span>
          </button>
        </div>

        <div
          v-for="item in visibleReports"
          :key="item.uuid"
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
    activeSource: "all",
  }),
  computed: {
    sourceTabs() {
      // Stable source order; unknown keys fall after and keep first-seen order.
      const order = { calendar: 0, deck: 1, huly: 2 };
      const map = new Map();
      this.reports.forEach((item) => {
        const key = item.type_key || "unknown";
        if (!map.has(key)) {
          map.set(key, { key, title: item.type_title || key, count: 0 });
        }
        map.get(key).count += 1;
      });
      const tabs = [...map.values()].sort(
        (a, b) => (order[a.key] ?? 99) - (order[b.key] ?? 99)
      );

      return [
        {
          key: "all",
          title: this.contextTranslate("All", this.context),
          count: this.reports.length,
        },
        ...tabs,
      ];
    },
    visibleReports() {
      if (this.activeSource === "all") {
        return this.reports;
      }

      return this.reports.filter(
        (item) => (item.type_key || "unknown") === this.activeSource
      );
    },
  },
  watch: {
    active(value) {
      // Each time the sidebar opens, start from the "All" tab.
      if (value === true) {
        this.activeSource = "all";
      }
    },
    sourceTabs(tabs) {
      // If the active source disappeared (e.g. its last row was accepted),
      // fall back to "All" so we never sit on a non-existent tab.
      if (!tabs.some((tab) => tab.key === this.activeSource)) {
        this.activeSource = "all";
      }
    },
  },
  methods: {
    selectSource(key) {
      this.activeSource = key;
    },
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

<style scoped>
.source-tab {
  padding: 4px 14px;
  border-radius: 9999px;
  border: 2px solid var(--color-border-dark, var(--color-border));
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: 13px;
  line-height: 1.4;
  cursor: pointer;
  transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.source-tab:hover,
.source-tab:focus,
.source-tab:focus-visible {
  background: var(--color-background-hover);
  outline: none;
}

/* Active state wins in every interaction state — listed last on purpose. */
.source-tab--active,
.source-tab--active:hover,
.source-tab--active:focus,
.source-tab--active:focus-visible,
.source-tab--active:active {
  background: var(--color-primary-element);
  border-color: var(--color-primary-element);
  color: var(--color-primary-element-text);
  font-weight: 700;
  outline: none;
}

.source-tab__count {
  margin-inline-start: 6px;
  opacity: 0.8;
}
</style>
