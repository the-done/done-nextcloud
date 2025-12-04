/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <NcAppNavigation class="app-navigation">
    <template v-if="hasHeaderSlot" #search>
      <div class="app-navigation__header">
        <slot name="header" />
      </div>
    </template>
    <template #list>
      <template v-for="item in items">
        <template v-if="item.visible !== false">
          <NcAppNavigationItem
            v-if="item.children"
            :key="item.key"
            :name="item.label"
            :allowCollapse="true"
            :open="item.open"
            :to="item.to"
            :exact="item.exact"
          >
            <template #icon>
              <component :is="item.icon" />
            </template>

            <template v-for="(children, index) in item.children">
              <NcAppNavigationItem
                v-if="children.visible !== false"
                :key="index"
                :name="contextTranslate(children.label, context)"
                :to="children.to"
                :exact="children.exact"
              />
            </template>
          </NcAppNavigationItem>
          <NcAppNavigationItem
            v-else
            :key="`single-${item.key}`"
            :name="item.label"
            :to="item.to"
            :exact="item.exact"
          >
            <template #icon v-if="item.icon">
              <component :is="item.icon" />
            </template>
          </NcAppNavigationItem>
        </template>
      </template>
    </template>
    <template v-if="hasFooterSlot" #footer>
      <div class="app-navigation__footer">
        <slot name="footer" />
      </div>
    </template>
  </NcAppNavigation>
</template>

<script>
import { NcAppNavigation, NcAppNavigationItem } from "@nextcloud/vue";
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

export default {
  name: "AppNavigation",
  components: {
    NcAppNavigation,
    NcAppNavigationItem,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    items: {
      type: Array,
      default: () => [],
    },
  },
  computed: {
    hasHeaderSlot() {
      return !!this.$slots.header;
    },
    hasFooterSlot() {
      return !!this.$slots.footer;
    },
  },
    methods: {
        t
    },
};
</script>

<style scoped>
.app-navigation__header {
  padding: var(--app-navigation-padding);
}

.app-navigation__footer {
  padding: var(--app-navigation-padding);
}
</style>
