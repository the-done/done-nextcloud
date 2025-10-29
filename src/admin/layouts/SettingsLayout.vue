/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <section class="v-app-layout">
    <AppNavigation :items="settingsNavigation">
      <template #header>
        <NcButton :to="{ name: 'time-tracking-new' }" wide>
          <template #icon>
            <ArrowLeft />
          </template>
          {{ contextTranslate("Back", context) }}
        </NcButton>
      </template>
    </AppNavigation>
    <NcAppContent>
      <router-view />
    </NcAppContent>
  </section>
</template>

<script>
import { NcAppContent, NcButton } from "@nextcloud/vue";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import ArrowLeft from "vue-material-design-icons/ArrowLeft.vue";

import AppNavigation from "@/common/widgets/AppNavigation/AppNavigation.vue";

import { settingsNavigationMixin } from "@/admin/shared/lib/mixins/settingsNavigationMixin";

export default {
  name: "SettingsLayout",
  mixins: [contextualTranslationsMixin, settingsNavigationMixin],
  data: () => ({
    context: "admin/users",
  }),
  components: {
    NcAppContent,
    NcButton,
    ArrowLeft,
    AppNavigation,
  },
};
</script>
