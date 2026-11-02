/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs v-if="breadcrumbs?.length > 0">
        <NcBreadcrumb
          v-for="(item, index) in breadcrumbs"
          :key="index"
          :name="contextTranslate(item.title, context)"
          :to="item.path"
          :forceIconText="index === 0"
        />
      </NcBreadcrumbs>
    </VToolbar>

    <VScrollArea class="huly-settings__content">
      <div class="huly-settings__header">
        <h1 class="huly-settings__title">{{ contextTranslate("Huly integration", context) }}</h1>
        <p class="huly-settings__description">
          {{ description }}
        </p>
      </div>

      <VTabs :value="tabs" class="huly-settings__tabs" />

      <RouterView />
    </VScrollArea>
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb } from "@nextcloud/vue";

import { VPage } from "@/widgets";
import { VToolbar, VScrollArea, VTabs } from "@/shared/components";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "HulyIntegrationSettingsPage",
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    VPage,
    VToolbar,
    VScrollArea,
    VTabs,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    // Route props of the "huly" route node, same shape as the other settings routes.
    additionalProps: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      context: "settings",
    };
  },
  computed: {
    breadcrumbs() {
      return this.additionalProps.breadcrumbs || [];
    },
    description() {
      return this.contextTranslate(
        "Bring time logged in Huly into Done: as suggestions when filling in the day and, optionally, automatically.",
        this.context
      );
    },
    tabs() {
      return [
        {
          key: "settings-huly",
          label: this.contextTranslate("Integration settings", this.context),
          to: { name: "settings-huly" },
          exact: true,
        },
        {
          key: "settings-huly-projects",
          label: this.contextTranslate("Project mapping", this.context),
          to: { name: "settings-huly-projects" },
          exact: true,
        },
      ];
    },
  },
};
</script>

<style scoped>
.huly-settings__content {
  padding: 24px;
}

.huly-settings__header {
  margin-bottom: 16px;
}

.huly-settings__title {
  font-size: 24px;
  font-weight: 600;
}

.huly-settings__description {
  color: var(--color-text-maxcontrast);
}

.huly-settings__tabs {
  margin-bottom: 24px;
  border-bottom: 1px solid var(--color-border);
}
</style>
