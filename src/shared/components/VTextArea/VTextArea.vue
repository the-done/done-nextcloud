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
      :style="{ marginTop: 0 }"
      @update:modelValue="handleUpdateModelValue"
      @keydown.enter.exact="handleKeydownEnter"
      @keydown.enter.shift.exact="handleKeydownEnterShift"
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
    enterSubmit: {
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
    handleKeydownEnter(e) {
      if (this.enterSubmit === true) {
        e.preventDefault();

        this.$emit("on-keydown-enter");

        return;
      }

      this.handleUpdateModelValue(e.target.value);
    },
    handleKeydownEnterShift(e) {
      if (this.enterSubmit === true) {
        e.preventDefault();

        this.$emit("on-keydown-enter-shift");

        return;
      }

      this.handleUpdateModelValue(e.target.value);
    },
  },
};
</script>

<style scoped>
.v-text-area:deep(.textarea__main-wrapper) {
  height: unset;
}

.v-text-area--invalid:deep(.textarea__input) {
  border-color: var(--color-element-error);
}
</style>
