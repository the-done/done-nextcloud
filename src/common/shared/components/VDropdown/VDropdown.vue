<template>
  <div :class="['v-dropdown', error && 'v-dropdown--invalid']">
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
    <NcSelect
      v-if="readDisabled === false"
      :model-value="value"
      :options="options"
      :label="valueLabel"
      :placeholder="placeholder"
      :aria-label-combobox="label || ariaLabel"
      :user-select="userSelect"
      :disabled="disabled"
      :keep-open="keepOpen"
      :multiple="multiple"
      :append-to-body="appendToBody"
      class="v-dropdown__input"
      @update:modelValue="handleUpdateModelValue"
    />
    <div v-else class="v-caption v-caption--grey">
      {{ contextTranslate("Reading is not available", context) }}
    </div>
    <div
      v-if="showErrorCaption === true"
      :class="[
        'v-caption',
        'v-color-error',
        absoluteCaption === true ? 'v-caption--absolute' : 'mt-1',
      ]"
    >
      {{ error }}
    </div>
    <div v-if="caption" class="v-caption v-caption--grey mt-1">
      {{ caption }}
    </div>
  </div>
</template>

<script>
import { NcSelect } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "VSelect",
  components: {
    NcSelect,
  },
  emits: ["input"],
  mixins: [contextualTranslationsMixin],
  props: {
    value: {
      type: [String, Number, Object, Array],
      default: "",
    },
    options: {
      type: Array,
      default: () => [],
    },
    label: {
      type: String,
      default: "",
    },
    ariaLabel: {
      type: String,
      default: "",
    },
    required: {
      type: Boolean,
      default: false,
    },
    valueLabel: {
      type: String,
      default: "name",
    },
    error: {
      type: [String, Boolean],
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
    userSelect: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    absoluteCaption: {
      type: Boolean,
      default: false,
    },
    readDisabled: {
      type: Boolean,
      default: false,
    },
    keepOpen: {
      type: Boolean,
      default: false,
    },
    multiple: {
      type: Boolean,
      default: false,
    },
    appendToBody: {
      type: Boolean,
      default: true,
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
      // Ignore the separator selection
      if (
        value &&
        (value === "separator" ||
          (typeof value === "object" && value.id === "separator"))
      ) {
        return;
      }

      this.$emit("input", value);
    },
  },
};
</script>

<style scoped>
.v-dropdown {
  width: 100%;
  position: relative;
}

.v-dropdown--invalid:deep(.vs__dropdown-toggle) {
  border-color: var(--color-element-error);
}

.v-dropdown__input.v-select {
  width: 100%;
  min-width: 0;
  margin: 0;
}

/* Styles for the separator in the dropdown list */
.v-dropdown__input:deep(.vs__dropdown-option--separator) {
  color: #999;
  font-style: italic;
  pointer-events: none;
  background-color: #f5f5f5;
  cursor: default;
}

.v-dropdown__input:deep(.vs__dropdown-option--separator:hover) {
  background-color: #f5f5f5;
}
</style>
