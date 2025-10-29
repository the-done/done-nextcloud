/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <DefaultLayout />
</template>

<script>
import DefaultLayout from "./layouts/DefaultLayout.vue";

export default {
  name: "App",
  components: {
    DefaultLayout
  },
};
</script>

<style scoped lang="scss">
#done {
  display: flex;
  justify-content: center;
  margin: 16px;
}
</style>
