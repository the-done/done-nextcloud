/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div class="flex flex-col gap-2 p-2">
    <NcButton
      type="tertiary"
      alignment="start"
      wide
      @click="() => handleSetActiveTab('columns-settings')"
    >
      <template #icon>
        <EyeOutline />
      </template>
      {{ contextTranslate("Column visibility", context) }}
    </NcButton>
  </div>
</template>

<script>
import { NcButton } from "@nextcloud/vue";

import EyeOutline from "vue-material-design-icons/EyeOutline.vue";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "DynamicTableSettingsMenu",
  mixins: [contextualTranslationsMixin],
  components: {
    NcButton,
    EyeOutline,
  },
  props: {},
  emits: ["update:activeTab"],
  data() {
    return {};
  },
  computed: {},
  methods: {
    handleSetActiveTab(value) {
      this.$emit("update:activeTab", value);
    },
  },
};
</script>
