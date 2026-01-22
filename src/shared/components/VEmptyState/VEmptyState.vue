/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <div class="flex flex-col items-center justify-center h-full">
    <NcEmptyContent
      :name="contextTranslate(title, context)"
      :description="contextTranslate(caption, context)"
    >
      <template #icon>
        <slot name="icon">
          <CloudSearch :size="20" />
        </slot>
      </template>
    </NcEmptyContent>
  </div>
</template>

<script>
import { t } from "@nextcloud/l10n";
import { NcEmptyContent } from "@nextcloud/vue";

import CloudSearch from "vue-material-design-icons/CloudSearch.vue";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

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

  },
};
</script>
