/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <tr class="v-table-row">
    <slot />
  </tr>
</template>

<script>
export default {
  name: "VTableRow",
};
</script>

<style scoped>
.v-table-row:hover {
  background-color: var(--color-background-hover);
}
</style>
