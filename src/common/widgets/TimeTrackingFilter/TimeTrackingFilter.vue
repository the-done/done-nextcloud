/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div
    data-component-id="TimeTrackingFilter"
    :class="[
      'fixed top-[50px] right-0',
      'flex flex-col w-[300px] max-w-full h-full overflow-auto',
      'bg-(--color-main-background) shadow-xl z-9999 transition-transform transition-shadow',
      'md:relative md:top-0 md:w-full md:h-auto md:p-0 md:shadow-none md:transform-[translateX(0)]',
      active === true
        ? 'shadow-xl transform-[translateX(0)]'
        : 'shadow-none transform-[translateX(100%)]',
    ]"
  >
    <div class="flex items-center gap-4 px-4 py-2 min-h-[52px] md:hidden">
      <NcButton aria-label="Close" type="tertiary" @click="handleClose">
        <template #icon>
          <Close :size="20" />
        </template>
      </NcButton>
      <span class="font-medium"> Filters </span>
    </div>
    <div class="flex flex-col gap-2 py-2 px-4 border-t border-(--color-border)">
      <template v-for="item in descriptor">
        <div v-if="item.visible !== false" :key="item.key">
          <template v-if="item.type === 'select'">
            <VDropdown
              v-if="item.initiated === true"
              v-model="item.value"
              :options="item.options"
              :multiple="item.multiple === true"
              :keep-open="true"
              :aria-label="item.placeholder"
              :placeholder="item.placeholder"
              @input="handleUpdateFilter"
            />
            <VSkeletonLoader v-else />
          </template>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
import { NcButton } from "@nextcloud/vue";
import Close from "vue-material-design-icons/Close.vue";

import VDropdown from "@/common/shared/components/VDropdown/VDropdown.vue";
import VSkeletonLoader from "@/common/shared/components/VSkeletonLoader/VSkeletonLoader.vue";

export default {
  name: "TimeTrackingFilter",
  components: {
    NcButton,
    Close,
    VDropdown,
    VSkeletonLoader,
  },
  emits: ["update:filter", "update:active"],
  props: {
    descriptor: {
      type: Array,
      default: () => [],
    },
    active: {
      type: Boolean,
      default: false,
    },
  },
  methods: {
    handleClose() {
      this.$emit("update:active");
    },
    handleUpdateFilter(value) {
      // Ignore the separator selection
      if (value && (value === 'separator' || (typeof value === 'object' && value.id === 'separator'))) {

        return;
      }
      
      this.$emit("update:filter");
    },
    fetchDictionaries({ items }) {
      items.forEach((item) => {
        item.fetchOptionsFunction().then(({ data }) => {
          const { value, multiple } = item;

          // Filter the separator from the options
          item.options = data.filter(option => option.id !== 'separator');

          if (value) {
            if (multiple === true && Array.isArray(value) === true) {
              item.value = value.reduce((accum, id) => {
                const valueData = data.find((item) => item.id === id);

                if (valueData) {
                  return [...accum, valueData];
                }

                return [...accum, id];
              }, []);
            } else {
              const valueData = data.find((item) => item.id === value);

              item.value = valueData || value;
            }
          }

          item.initiated = true;
        });
      });
    },
    init() {
      const itemsWithDictionaries = this.descriptor.filter(
        (item) => item.fetchOptionsFunction !== undefined
      );

      this.fetchDictionaries({ items: itemsWithDictionaries });
    },
  },
  mounted() {
    this.init();
  },
};
</script>
