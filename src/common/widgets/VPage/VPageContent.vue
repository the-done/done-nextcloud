/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div class="size-full overflow-hidden">
    <VScrollArea :class="wrapperClass">
      <div
        :class="['flex flex-col size-full', contentClass]"
        data-component-id="VPageContent"
      >
        <slot />
      </div>
    </VScrollArea>
  </div>
</template>

<script>
import VScrollArea from "@/common/shared/components/VScrollArea/VScrollArea.vue";

export default {
  name: "VPageContent",
  props: {
    contentClass: {
      type: String,
      default: "",
    },
    wrapperClass: {
      type: String,
      default: "",
    },
  },
  components: {
    VScrollArea,
  },
};
</script>
