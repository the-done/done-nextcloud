/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div
    data-component-id="VLoader"
    :class="[
      'flex items-center justify-center size-full',
      absolute && 'absolute top-0 left-0 z-1000',
    ]"
  >
    <NcLoadingIcon class="z-100" :size="size" />
    <div
      v-if="absolute"
      class="bg-(--color-main-background) absolute top-0 left-0 size-full opacity-60"
    />
  </div>
</template>

<script>
import { NcLoadingIcon } from "@nextcloud/vue";

import { LOADER_SIZE } from "@/common/shared/lib/constants";

export default {
  name: "VLoader",
  props: {
    size: {
      type: Number,
      default: LOADER_SIZE,
    },
    absolute: {
      type: Boolean,
      default: false,
    },
  },
  components: {
    NcLoadingIcon,
  },
};
</script>
