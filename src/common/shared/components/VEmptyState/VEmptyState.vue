/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div
    class="v-flex v-flex--column h-full v-flex--align-center v-flex--justify-center"
  >
    <NcEmptyContent :name="contextTranslate(title, context)" :description="contextTranslate(caption, context)">
      <template #icon>
        <slot name="icon">
          <CloudSearch :size="20" />
        </slot>
      </template>
    </NcEmptyContent>
  </div>
</template>

<script>
import { NcEmptyContent } from "@nextcloud/vue";
import CloudSearch from "vue-material-design-icons/CloudSearch.vue";

import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

export default {
  name: "VEmptyState",
  mixins: [contextualTranslationsMixin],
  props: {
    title: {
      type: String,
      default: () => t("done", "No data found"),
    },
    caption: {
      type: String,
      default: () => t("done", "No data found for your request"),
    },
  },
  components: {
    NcEmptyContent,
    CloudSearch,
  },
  methods: {
    t
  }
};
</script>
