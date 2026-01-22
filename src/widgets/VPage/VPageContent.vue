/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <div class="size-full overflow-hidden">
    <VScrollArea :class="wrapperClass" :style="wrapperStyles">
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
import { VScrollArea } from "@/shared/components";

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
    wrapperStyles: {
      type: [Object, String],
      default: null,
    },
  },
  components: {
    VScrollArea,
  },
};
</script>
