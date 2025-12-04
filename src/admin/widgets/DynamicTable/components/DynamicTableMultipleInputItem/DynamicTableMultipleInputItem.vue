/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div class="v-flex v-flex--align-center v-flex--gap-1">
    {{ value }}
    <VButton size="small" variant="tertiary" @click="handleDelete">
      <template #icon>
        <Close :size="16" />
      </template>
    </VButton>
  </div>
</template>

<script>
import Close from "vue-material-design-icons/Close.vue";

import VButton from "@/common/shared/components/VButton/VButton.vue";

export default {
  name: "DynamicTableMultipleInputItem",
  components: {
    Close,
    VButton,
  },
  props: {
    value: {
      type: String,
      default: "",
    },
    index: {
      type: Number,
      default: 0,
    },
  },
  emits: ["on-delete"],
  methods: {
    handleDelete() {
      this.$emit("on-delete", this.index);
    },
  },
};
</script>
