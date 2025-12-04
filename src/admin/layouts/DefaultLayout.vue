/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <LayoutContainer>
    <AppNavigation :items="defaultNavigation">
      <template #footer>
        <NcButton :to="{ name: 'settings-user' }" alignment="start" wide>
          <template #icon>
            <Cog />
          </template>
          {{ contextTranslate("Settings", context) }}
        </NcButton>
      </template>
    </AppNavigation>
    <NcAppContent>
      <router-view />
    </NcAppContent>
  </LayoutContainer>
</template>

<script>
import { NcAppContent, NcButton } from "@nextcloud/vue";

import Cog from "vue-material-design-icons/Cog.vue";

import AppNavigation from "@/common/widgets/AppNavigation/AppNavigation.vue";

import LayoutContainer from "./LayoutContainer.vue";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";
import { defaultNavigationMixin } from "@/admin/shared/lib/mixins/defaultNavigationMixin";

export default {
  name: "DefaultLayout",
  mixins: [contextualTranslationsMixin, defaultNavigationMixin],
  components: {
    NcAppContent,
    NcButton,
    AppNavigation,
    Cog,
    LayoutContainer,
  },
};
</script>
