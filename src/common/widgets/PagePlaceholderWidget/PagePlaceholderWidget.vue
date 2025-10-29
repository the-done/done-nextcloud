/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <NcEmptyContent :name="name" :description="description">
    <template #icon>
      <Web :size="20" />
    </template>

    <template #description>
      <slot name="emptyStateDescription" />
    </template>
  </NcEmptyContent>
</template>

<script>
import { NcEmptyContent } from "@nextcloud/vue";
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

import Web from "vue-material-design-icons/Web.vue";

export default {
  name: "PlaceholderPage",
  components: {
    NcEmptyContent,
    Web,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    name: {
      type: String,
      default: () => this.contextTranslate('In development', this.context),
    },
    description: {
      type: String,
      default: () => this.contextTranslate('Page is under development', this.context),
    },
  },
  methods: {
    t,
  },
};
</script>
