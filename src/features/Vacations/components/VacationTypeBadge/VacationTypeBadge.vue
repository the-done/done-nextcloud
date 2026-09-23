/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <span class="vacation-type-badge">
    <span
      class="vacation-type-badge__dot"
      :style="{ backgroundColor: color }"
    />
    <span class="vacation-type-badge__name">{{ name }}</span>
  </span>
</template>

<script>
export default {
  name: "VacationTypeBadge",
  props: {
    name: {
      type: String,
      required: true,
    },
    color: {
      type: String,
      required: true,
    },
  },
};
</script>

<style scoped>
.vacation-type-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.vacation-type-badge__dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.vacation-type-badge__name {
  color: var(--color-main-text);
}
</style>