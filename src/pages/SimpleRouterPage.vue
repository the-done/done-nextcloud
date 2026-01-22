/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <RouterView v-bind="$props" />
</template>

<script>
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "SimpleRouterPage",
  mixins: [contextualTranslationsMixin],
  props: {
    additionalProps: {
      type: Object,
      default: () => ({}),
    },
  },
};
</script>
