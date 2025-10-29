/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <form
    class="flex flex-col gap-2 w-full"
    data-component-id="VForm"
    @submit.prevent="handleSubmit"
  >
    <slot />
  </form>
</template>

<script>
export default {
  name: "VForm",
  emits: ["submit"],
  methods: {
    handleSubmit() {
      this.$emit("submit");
    },
  },
};
</script>
