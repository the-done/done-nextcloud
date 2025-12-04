/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div :class="['v-datepicker', error && 'v-datepicker--invalid']">
    <div
      v-if="label"
      :class="[
        'v-label',
        readDisabled === true && 'v-label--field-placeholder',
      ]"
    >
      {{ label }}
      <span v-if="required === true" class="v-color-error">*</span>
    </div>
    <NcDateTimePicker
      v-if="readDisabled === false"
      :model-value="value"
      :placeholder="placeholder"
      :format="format"
      :clearable="clearable"
      :disabled="disabled"
      :type="type"
      :minute-step="minuteStep"
      class="v-100"
      @update:modelValue="handleUpdateModelValue"
    />
    <div v-else class="v-caption v-caption--grey">{{ contextTranslate('Reading is not available', context) }}</div>
    <div v-if="showErrorCaption === true" class="v-caption v-color-error mt-1">
      {{ error }}
    </div>
    <div v-if="caption" class="v-caption v-caption--grey mt-1">
      {{ caption }}
    </div>
  </div>
</template>

<script>
import { NcDateTimePicker } from "@nextcloud/vue";
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

export default {
  name: "VDatePicker",
  components: {
    NcDateTimePicker,
  },
  emits: ["input"],
  mixins: [contextualTranslationsMixin],
  props: {
    value: {
      type: [Date, String],
      default: "",
    },
    label: {
      type: String,
      default: "",
    },
    placeholder: {
      type: String,
      default: "",
    },
    caption: {
      type: String,
      default: "",
    },
    format: {
      type: String,
      default: "DD.MM.YYYY",
    },
    error: {
      type: [String, Boolean],
      default: "",
    },
    clearable: {
      type: Boolean,
      default: true,
    },
    required: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    readDisabled: {
      type: Boolean,
      default: false,
    },
    type: {
      type: String,
      default: "date",
    },
    minuteStep: {
      type: Number,
      default: 1,
    },
  },
  computed: {
    showErrorCaption() {
      if (this.error && typeof this.error === "string") {
        return true;
      }

      return false;
    },
  },
  methods: {
    t,
    handleUpdateModelValue(value) {
      this.$emit("input", value);
    },
  },
};
</script>

<style scoped>
.v-datepicker:deep(.mx-input-wrapper .mx-input) {
  border-width: 1px;
}

.v-datepicker:deep(.mx-time-column .mx-time-item.active) {
  background-color: var(--color-primary);
}

.v-datepicker:deep(.mx-time-column .mx-time-item:hover) {
  background-color: var(--color-background-dark);
  color: var(--color-main-text);
}

.v-datepicker--invalid:deep(.mx-input-wrapper .mx-input) {
  border-color: var(--color-element-error);
}
</style>
