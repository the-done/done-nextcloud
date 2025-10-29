/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <NcContent appName="app">
    <AppNavigation :items="navigation" />
    <NcAppContent>
      <router-view />
    </NcAppContent>

    <notifications position="bottom right" />
  </NcContent>
</template>

<script>
import { NcContent, NcAppContent } from "@nextcloud/vue";
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

import Plus from "vue-material-design-icons/Plus.vue";
import ClockTimeEightOutline from "vue-material-design-icons/ClockTimeEightOutline.vue";

import AppNavigation from "@/common/widgets/AppNavigation/AppNavigation.vue";

export default {
  name: "App",
  mixins: [contextualTranslationsMixin],
  components: {
    NcContent,
    NcAppContent,
    AppNavigation,
  },
  data() {
    return {
      context: 'user/time-tracking',
      navigation: [
        {
          key: "time-tracking-new",
          label: this.contextTranslate('Add record', this.context),
          icon: Plus,
          to: {
            name: "time-tracking-new",
          },
          exact: true,
        },
        {
          key: "time-tracking-statistics",
          label: this.contextTranslate('Statistics', this.context),
          icon: ClockTimeEightOutline,
          to: {
            name: "time-tracking-statistics",
          },
          exact: true,
        },
      ],
    };
  },
  methods: {
    t,
  },
};
</script>
