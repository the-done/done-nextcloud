<template>
  <div class="v-switch">
    <NcCheckboxRadioSwitch
      :model-value="value"
      :name="name"
      :disabled="disabled"
      type="switch"
      @update:modelValue="handleUpdateValue"
    >
      {{ label }}
    </NcCheckboxRadioSwitch>
  </div>
</template>

<script>
import { NcCheckboxRadioSwitch } from "@nextcloud/vue";

export default {
  name: "VSwitch",
  components: {
    NcCheckboxRadioSwitch,
  },
  props: {
    value: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    label: {
      type: String,
      default: "",
    },
    name: {
      type: String,
      default: "",
    },
  },
  emits: ["input"],
  methods: {
    handleUpdateValue(value) {
      this.$emit("input", value);
    },
  },
};
</script>
