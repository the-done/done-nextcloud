/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <NcAppNavigation>
    <template v-if="hasHeaderSlot" #search>
      <div class="p-(--app-navigation-padding)">
        <slot name="header" />
      </div>
    </template>
    <template #list>
      <template v-for="item in items">
        <template v-if="item.visible !== false">
          <div
            v-if="item.isBlock && item.children?.length > 0"
            class="relative my-3 -mx-(--app-navigation-padding) px-(--app-navigation-padding) pb-1"
          >
            <span :class="[blockBorderClass, 'top-0']" />
            <span :class="[blockBorderClass, 'bottom-0']" />
            <NcAppNavigationList>
              <div
                v-if="item.label"
                class="font-semibold pl-[calc(var(--default-grid-baseline,4px)*2)]"
              >
                {{ item.label }}
              </div>
              <template v-for="children in item.children">
                <NcAppNavigationItem
                  v-if="children.children"
                  :key="children.key"
                  :name="children.label"
                  :allowCollapse="true"
                  :open="children.open"
                  :to="children.to"
                  :exact="children.exact"
                >
                  <template #icon>
                    <component :is="children.icon" />
                  </template>

                  <template v-for="(subChildren, index) in children.children">
                    <NcAppNavigationItem
                      v-if="subChildren.visible !== false"
                      :key="index"
                      :name="contextTranslate(subChildren.label, context)"
                      :to="subChildren.to"
                      :exact="subChildren.exact"
                    />
                  </template>
                </NcAppNavigationItem>
                <NcAppNavigationItem
                  v-else
                  :key="`single-${children.key}`"
                  :name="children.label"
                  :to="children.to"
                  :exact="children.exact"
                >
                  <template #icon v-if="children.icon">
                    <component :is="children.icon" />
                  </template>
                </NcAppNavigationItem>
              </template>
            </NcAppNavigationList>
          </div>
          <NcAppNavigationCaption
            v-else-if="item.isCaption === true"
            :name="item.label"
            class="mt-0!"
          />
          <NcAppNavigationItem
            v-else-if="item.children"
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
      <div class="p-(--app-navigation-padding)">
        <slot name="footer" />
      </div>
    </template>
  </NcAppNavigation>
</template>

<script>
import {
  NcAppNavigation,
  NcAppNavigationList,
  NcAppNavigationCaption,
  NcAppNavigationItem,
} from "@nextcloud/vue";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "AppNavigation",
  components: {
    NcAppNavigation,
    NcAppNavigationList,
    NcAppNavigationCaption,
    NcAppNavigationItem,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    items: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      blockBorderClass:
        "absolute left-0 w-full h-[1px] bg-(image:--gradient-primary-background) opacity-30",
    };
  },
  computed: {
    hasHeaderSlot() {
      return !!this.$slots.header;
    },
    hasFooterSlot() {
      return !!this.$slots.footer;
    },
  },
};
</script>
