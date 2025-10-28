<template>
  <div class="v-radio-set">
    <div
      v-if="label"
      :class="[
        'v-label',
        readDisabled === true && 'v-label--field-placeholder',
      ]"
    >
      {{ label }}
    </div>
    <template v-if="readDisabled === false">
      <NcCheckboxRadioSwitch
        v-for="item in options"
        :key="item.key || item.value"
        :model-value="value"
        :value="item.value"
        :name="name"
        :disabled="disabled"
        type="radio"
        @update:modelValue="handleUpdateValue"
      >
        {{ item.label }}
      </NcCheckboxRadioSwitch>
    </template>
    <div v-else class="v-caption v-caption--grey">
      {{ contextTranslate("Reading is not available") }}
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
import { NcCheckboxRadioSwitch } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "VRadioSet",
  components: {
    NcCheckboxRadioSwitch,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    value: {
      type: [String],
      default: null,
    },
    label: {
      type: String,
      default: "",
    },
    name: {
      type: String,
      default: "",
    },
    options: {
      type: Array,
      default: () => [],
    },
    caption: {
      type: String,
      default: "",
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    readDisabled: {
      type: Boolean,
      default: false,
    },
    error: {
      type: [String, Boolean],
      default: "",
    },
  },
  emits: ["input"],
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
    handleUpdateValue(value) {
      this.$emit("input", value);
    },
  },
};
</script>
