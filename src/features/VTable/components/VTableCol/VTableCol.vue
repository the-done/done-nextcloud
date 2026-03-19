/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <td
    :class="[
      {
        'v-table-col': true,
        'v-table-col--sticky': stickyLeft || stickyRight,
        'v-table-col--sticky-left': stickyLeft,
        'v-table-col--sticky-right': stickyRight,
      },
      customClass,
    ]"
    :data-name="name"
  >
    <span
      v-if="stickyRight || stickyLeft"
      :class="{
        'v-table-sticky-box': true,
        'v-table-sticky-box--left': stickyLeft,
        'v-table-sticky-box--right': stickyRight,
        'v-table-sticky-box--hidden': hideStickyBox,
      }"
    />
    <slot />
  </td>
</template>

<script>
export default {
  name: "VTableCol",
  props: {
    name: {
      type: String,
      default: "",
    },
    customClass: {
      type: [String, Object, Array],
      default: "",
    },
    stickyRight: {
      type: Boolean,
      default: false,
    },
    stickyLeft: {
      type: Boolean,
      default: false,
    },
    scrolledHorizontally: {
      type: Boolean,
      default: false,
    },
    scrolledHorizontallyEnd: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    hideStickyBox() {
      return (
        (this.stickyRight && this.scrolledHorizontallyEnd) ||
        (this.stickyLeft && !this.scrolledHorizontally)
      );
    },
  },
};
</script>
