/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div
    data-component-id="VAside"
    :class="[
      'absolute top-0 right-0 flex flex-col w-[768px] max-w-full h-full bg-(--color-main-background) z-500 transition-transform transition-shadow',
      value === true
        ? 'shadow-xl transform-[translateX(0)]'
        : 'shadow-none transform-[translateX(100%)]',
    ]"
  >
    <div
      class="flex items-center justify-center gap-4 px-4 py-2 border-b border-(--color-border) md:justify-start"
    >
      <NcButton
        :aria-label="contextTranslate('Close', context)"
        type="tertiary"
        @click="handleClose"
      >
        <template #icon>
          <Close :size="20" />
        </template>
      </NcButton>
      <span class="font-medium">
        <slot name="title" />
      </span>
    </div>
    <div class="overflow-auto flex-[1_1_auto]">
      <slot />
    </div>
  </div>
</template>

<script>
import { NcButton } from "@nextcloud/vue";
import Close from "vue-material-design-icons/Close.vue";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "VAside",
  components: {
    NcButton,
    Close,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    value: {
      type: Boolean,
      deafult: false,
    },
  },
  emits: ["input"],
  methods: {
    t,
    handleClose() {
      this.$emit("input", false);
    },
  },
};
</script>

<style scoped>
.v-aside {
  position: absolute;
  top: 0;
  right: 0;
  width: 40%;
  min-width: 510px;
  height: 100%;
  background-color: var(--color-main-background);
  z-index: 5555;
  box-shadow: 0 0 0 0 transparent;
  transform: translateX(100%);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.v-aside--active {
  transform: translateX(0);
  box-shadow: 0px 0 3px 1px var(--color-box-shadow);
}

.v-aside__header {
  display: flex;
  align-items: center;
  font-weight: 500;
  gap: 16px;
  padding: 8px 16px;
  border-bottom: 1px solid var(--color-border);
}
</style>
