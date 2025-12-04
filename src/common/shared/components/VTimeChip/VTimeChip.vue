/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VChip v-if="value" :variant="variant" :square="square" class="v-text-nowrap">
    {{ value }}
  </VChip>
</template>

<script>
import VChip from "@/common/shared/components/VChip/VChip.vue";

export default {
  name: "VTimeChip",
  components: {
    VChip,
  },
  props: {
    value: {
      type: [String, Number],
      default: "",
    },
    variant: {
      type: String,
      default: "",
    },
    square: {
      type: Boolean,
      default: true,
    },
  },
};
</script>
