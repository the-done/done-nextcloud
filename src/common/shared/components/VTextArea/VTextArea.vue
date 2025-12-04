/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <div :class="['v-text-area', error && 'v-text-area--invalid']">
    <div v-if="label" class="v-label">
      {{ label }}
      <span v-if="required === true" class="v-color-error">*</span>
    </div>
    <NcTextArea
      ref="input"
      :model-value="value"
      :placeholder="placeholder"
      :disabled="disabled"
      :input-class="inputClass"
      @update:modelValue="handleUpdateModelValue"
      @keydown.enter.exact.prevent="handleKeydownEnter"
      @keydown.enter.shift.exact.prevent="handleKeydownEnterShift"
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
  emits: ["input", "on-keydown-enter", "on-keydown-enter-shift"],
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
    disabled: {
      type: Boolean,
      default: false,
    },
    inputClass: {
      type: [String, Object],
      default: "",
    },
  },
  methods: {
    handleUpdateModelValue(value) {
      this.$emit("input", value);
    },
    handleKeydownEnter() {
      this.$emit("on-keydown-enter");
    },
    handleKeydownEnterShift() {
      this.$emit("on-keydown-enter-shift");
    },
  },
};
</script>

<style scoped>
.v-text-area--invalid:deep(.textarea__input) {
  border-color: var(--color-element-error);
}
</style>
