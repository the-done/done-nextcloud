/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <NcContent appName="app">
    <router-view />
    <notifications position="bottom right" />
    <!-- <AiChat /> -->
  </NcContent>
</template>

<script>
import { NcContent } from "@nextcloud/vue";

import { AiChat } from "@/admin/widgets/AiChat";

export default {
  name: "App",
  components: {
    NcContent,
    AiChat,
  },
};
</script>
