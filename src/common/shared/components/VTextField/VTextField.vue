<template>
  <div
    :class="['v-text-field', error || invalid ? 'v-text-field--invalid' : null]"
  >
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
    <NcTextField
      v-if="readDisabled === false"
      :model-value="value"
      :placeholder="placeholder"
      :label-outside="true"
      :disabled="disabled"
      @update:modelValue="handleUpdateModelValue"
    />
    <div v-else class="v-caption v-caption--grey">
      {{ contextTranslate("Reading is not available", context) }}
    </div>
    <div v-if="showErrorCaption === true" class="v-caption v-color-error mt-1">
      {{ error }}
    </div>
    <div v-if="caption" class="v-caption v-caption--grey mt-1">
      {{ caption }}
    </div>
  </div>
</template>

<script>
import { NcTextField } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "VTextField",
  components: {
    NcTextField,
  },
  emits: ["input"],
  mixins: [contextualTranslationsMixin],
  props: {
    value: {
      type: [String, Number],
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
    required: {
      type: Boolean,
      default: false,
    },
    error: {
      type: [String, Boolean],
      default: "",
    },
    invalid: {
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
.v-text-field--invalid:deep(.input-field__input) {
  border-color: var(--color-element-error);
}
</style>
