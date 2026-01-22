/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <LayoutContainer>
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
      <RouterView />
    </NcAppContent>
  </LayoutContainer>
</template>

<script>
import { NcAppContent, NcButton } from "@nextcloud/vue";

import ArrowLeft from "vue-material-design-icons/ArrowLeft.vue";

import { AppNavigation } from "@/widgets";

import LayoutContainer from "./LayoutContainer.vue";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { settingsNavigationMixin } from "@/shared/lib/mixins/settingsNavigationMixin";

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
    LayoutContainer,
  },
};
</script>
