/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div
    class="v-page-aside-navigation flex-[0_0_auto] overflow-auto shadow-md z-100 md:shadow-none md:border-b-0 md:border-r md:border-(--color-border) md:w-[300px] md:h-full"
    data-component-id="VPageAsideNavigation"
  >
    <ul
      v-if="items?.length > 0"
      class="flex items-center gap-4 w-fit py-2 px-4 md:flex-col md:w-full md:gap-1 md:p-(--app-navigation-padding)"
    >
      <template v-for="item in items">
        <NcListItem
          v-if="item.visible"
          :key="item.key"
          :to="item.to"
          :name="item.label"
          :exact="item.exact"
          compact
        >
          <template v-if="item.icon" #icon>
            <component :is="item.icon" :sie="20" />
          </template>
        </NcListItem>
      </template>
    </ul>
  </div>
</template>

<script>
import { NcButton, NcListItem } from "@nextcloud/vue";

export default {
  name: "VPageAsideNavigation",
  props: {
    items: {
      type: Array,
      default: [],
    },
  },
  components: {
    NcButton,
    NcListItem,
  },
};
</script>

<style scoped>
@media (max-width: 767px) {
  .v-page-aside-navigation:deep(.list-item__wrapper) {
    width: auto;
    padding: 0;
  }

  .v-page-aside-navigation:deep(.list-item-content__main) {
    width: auto;
  }
}
</style>
