/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <span class="pro-badge">
    <ArrowUpBold :size="12" class="pro-badge__icon" />
    <span class="pro-badge__text">pro</span>
  </span>
</template>

<script>
import ArrowUpBold from "vue-material-design-icons/ArrowUpBold.vue";

export default {
  name: "ProBadge",
  components: {
    ArrowUpBold,
  },
};
</script>

<style scoped>
.pro-badge {
  display: inline-flex;
  align-items: center;
  gap: 1px;
  height: 16px;
  padding: 0 5px 0 3px;
  border-radius: 8px;
  background-color: var(--color-primary-element);
  color: var(--color-primary-element-text);
  font-size: 10px;
  font-weight: 600;
  line-height: 1;
  letter-spacing: 0.02em;
}

.pro-badge__icon {
  display: inline-flex;
}

.pro-badge__text {
  text-transform: lowercase;
}

/*
 * When the navigation item is active its background turns primary-blue, which
 * would blend with the (also blue) badge. Invert the badge there: white fill,
 * blue arrow + text (icon and text follow currentColor).
 */
.list-item__wrapper--active .pro-badge,
.app-navigation-entry.active .pro-badge {
  background-color: var(--color-primary-element-text);
  color: var(--color-primary-element);
}
</style>