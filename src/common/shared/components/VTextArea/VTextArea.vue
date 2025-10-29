/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div :class="['v-text-area', error && 'v-text-area--invalid']">
    <div v-if="label" class="v-label">
      {{ label }}
      <span v-if="required === true" class="v-color-error">*</span>
    </div>
    <NcTextArea
      :model-value="value"
      :placeholder="placeholder"
      @update:modelValue="handleUpdateModelValue"
    />
    <div v-if="error" class="v-caption v-color-error">
      {{ error }}
    </div>
  </div>
</template>

<script>
import { NcTextArea } from "@nextcloud/vue";

export default {
  name: "VTextArea",
  components: {
    NcTextArea,
  },
  emits: ["input"],
  props: {
    value: {
      type: [String, Number],
      default: "",
    },
    label: {
      type: String,
      default: "",
    },
    required: {
      type: Boolean,
      default: false,
    },
    error: {
      type: [String, Boolean],
      default: "",
    },
    placeholder: {
      type: String,
      default: "",
    },
  },
  methods: {
    handleUpdateModelValue(value) {
      this.$emit("input", value);
    },
  },
};
</script>

<style scoped>
.v-text-area--invalid:deep(.textarea__input) {
  border-color: var(--color-element-error);
}
</style>
