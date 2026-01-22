/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

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
    <NcButton
      type="tertiary"
      alignment="start"
      wide
      @click="() => handleSetActiveTab('appearance-settings')"
    >
      <template #icon>
        <ViewDashboard />
      </template>
      {{ contextTranslate("Appearance", context) }}
    </NcButton>
  </div>
</template>

<script>
import { NcButton } from "@nextcloud/vue";

import EyeOutline from "vue-material-design-icons/EyeOutline.vue";
import ViewDashboard from "vue-material-design-icons/ViewDashboard.vue";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "DynamicTableSettingsMenu",
  mixins: [contextualTranslationsMixin],
  components: {
    NcButton,
    EyeOutline,
    ViewDashboard,
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
