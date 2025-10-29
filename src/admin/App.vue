/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <NcContent appName="app">
    <router-view />
    <notifications position="bottom right" />
  </NcContent>
</template>

<script>
import { NcContent } from "@nextcloud/vue";

export default {
  name: "App",
  components: {
    NcContent,
  },
};
</script>
