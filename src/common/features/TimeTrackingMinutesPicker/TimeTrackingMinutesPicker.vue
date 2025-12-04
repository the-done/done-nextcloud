/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div>
    <div v-for="item in [15, 30, 45]" :key="item" :class="buttonWrapperClass">
      <NcButton
        :type="String(value) === String(item) ? 'primary' : 'secondary'"
        wide
        @click="() => handleClick(item)"
      >
        {{ item }} {{ contextTranslate("minutes", context) }}
      </NcButton>
    </div>
  </div>
</template>

<script>
import { NcButton } from "@nextcloud/vue";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "TimeTrackingMinutesPicker",
  emits: ["input"],
  components: {
    NcButton,
  },
  mixins: [contextualTranslationsMixin],
  data: () => ({
    context: "user/time-tracking",
  }),
  props: {
    value: {
      type: [String, Number],
      default: "",
    },
    buttonWrapperClass: {
      type: [String, Object, Array],
      default: "",
    },
  },
  methods: {
    handleClick(item) {
      this.$emit("input", item);
    },
  },
};
</script>
