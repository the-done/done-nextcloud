/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div :style="style" :class="['v-chip', variant && `v-chip--${variant}`]">
    <slot />
  </div>
</template>

<script>
export default {
  name: "VChip",
  props: {
    size: {
      type: Number,
      default: 20,
    },
    variant: {
      type: String,
      default: "",
    },
    borderRadius: {
      type: Number,
      default: 0,
    },
    square: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    computedBorderRadius() {
      if (this.borderRadius !== 0) {
        return this.toPixels(this.borderRadius);
      }

      if (this.square === true) {
        return this.toPixels(3);
      }

      return this.toPixels(this.size / 2);
    },
    style() {
      return {
        height: this.toPixels(this.size),
        lineHeight: this.toPixels(this.size),
        borderRadius: this.computedBorderRadius,
      };
    },
  },
  methods: {
    toPixels(value) {
      return `${value}px`;
    },
  },
};
</script>

<style scoped>
.v-chip {
  display: inline-flex;
  max-width: 100%;
  background-color: var(--color-background-dark);
  padding: 0 8px;
}

.v-chip:empty {
  display: none;
}

.v-chip--primary {
  color: var(--color-primary-element-text);
  background-color: var(--color-primary-element);
}

.v-chip--warning {
  color: var(--color-primary-element-text);
  background-color: var(--color-element-warning);
}

.v-chip--dark {
  color: var(--color-primary-element-text);
  background-color: var(--color-border-maxcontrast);
}
</style>
