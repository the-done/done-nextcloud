/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <NcContent appName="app">
    <RouterView />
    <notifications position="bottom right" />
    <DemoBanner />
    <AiChat v-if="aiChatEnabled === true" />
  </NcContent>
</template>

<script>
import { mapState } from "pinia";

import { NcContent } from "@nextcloud/vue";

import { AiChat, DemoBanner } from "@/widgets";

import { usePermissionStore } from "@/app/store/permission";
import { useModulesStore } from "@/app/store/modules";

export default {
  name: "App",
  components: {
    NcContent,
    AiChat,
    DemoBanner,
  },
  computed: {
    ...mapState(usePermissionStore, ["getCommonPermission"]),
    ...mapState(useModulesStore, ["moduleExist"]),
    aiChatEnabled() {
      return (
        this.moduleExist("doneai") && this.getCommonPermission("canViewAIChat")
      );
    },
  },
};
</script>
