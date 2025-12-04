/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <section
    :class="{
      'flex overflow-hidden transition-width duration-200': true,
      'w-full': isChatActive === false || isSidebarMode === false,
      'w-[calc(100%-var(--ai-chat-width)-var(--body-container-margin))] rounded-(--body-container-radius)':
        isChatActive === true && isSidebarMode === true,
    }"
  >
    <slot />
  </section>
</template>

<script>
import { mapState } from "pinia";

import { useAiChatStore } from "@/admin/app/store/aiChat";

export default {
  name: "LayoutContainer",
  computed: {
    ...mapState(useAiChatStore, ["isChatActive", "isSidebarMode"]),
  },
};
</script>
