<template>
  <NcButton
    :disabled="disabled || loading"
    :native-type="nativeType"
    :type="variant"
    :size="size"
    @click="handleClick"
  >
    <VLoader v-if="loading" :size="20" />
    <template v-if="hasIconSlot && !loading" #icon>
      <slot name="icon" /> </template
    ><!--
    --><slot v-if="!loading" />
  </NcButton>
</template>

<script>
import { NcButton } from "@nextcloud/vue";

import VLoader from "@/common/shared/components/VLoader/VLoader.vue";

export default {
  name: "VButton",
  emits: ["click"],
  components: {
    NcButton,
    VLoader,
  },
  props: {
    loading: {
      type: Boolean,
      default: false,
    },
    hideTextOnLoading: {
      type: Boolean,
      default: false,
    },
    square: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    nativeType: {
      type: String,
      default: "button",
    },
    variant: {
      type: String,
      default: null,
    },
    size: {
      type: String,
      default: "normal",
    },
  },
  computed: {
    hasIconSlot() {
      return !!this.$slots.icon;
    },
    hasDefaultSlot() {
      return !!this.$slots.default;
    },
  },
  methods: {
    handleClick(e) {
      this.$emit("click", e);
    },
  },
};
</script>
