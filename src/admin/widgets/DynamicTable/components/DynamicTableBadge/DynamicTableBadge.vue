/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div
    :class="[
      'inline-flex items-center justify-center gap-1 px-2 h-[24px] rounded-(--border-radius-small) align-top whitespace-nowrap',
      !disabled && 'v-cursor v-cursor--pointer',
      !active && 'hover:bg-(--color-background-dark)',
      active &&
        'bg-(--color-primary-light) text-(--color-primary) hover:bg-(--color-primary-light-hover)',
    ]"
    @click="handleClick"
  >
    <slot />
  </div>
</template>

<script>
export default {
  name: "DynamicTableBadge",
  emits: ["click"],
  components: {},
  props: {
    active: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
  },
  emits: [],
  data() {
    return {};
  },
  computed: {},
  methods: {
    handleClick() {
      this.$emit("click");
    },
  },
};
</script>
