/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <NcContent appName="app">
    <router-view />
    <notifications position="bottom right" />
    <AiChat v-if="aiChatEnabled === true" />
  </NcContent>
</template>

<script>
import { mapState } from "pinia";

import { NcContent } from "@nextcloud/vue";

import { AiChat } from "@/admin/widgets/AiChat";

import { usePermissionStore } from "@/admin/app/store/permission";
import { useModulesStore } from "@/admin/app/store/modules";

export default {
  name: "App",
  components: {
    NcContent,
    AiChat,
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
