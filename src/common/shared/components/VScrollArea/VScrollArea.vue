/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div class="size-full overflow-auto" data-component-id="VScrollArea">
    <slot />
  </div>
</template>

<script>
import { checkIfDarkTheme } from "@nextcloud/vue";

export default {
  name: "VScrollArea",

  computed: {
    isDarkTheme() {
      return checkIfDarkTheme();
    },
  },
};
</script>
