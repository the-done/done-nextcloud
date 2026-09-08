/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <transition name="demo-banner">
    <div v-if="visible" class="demo-banner" role="alert" aria-live="assertive">
      <span class="demo-banner__badge">
        <ArrowUpBold :size="14" />
        <span>pro</span>
      </span>
      <span class="demo-banner__text">{{ message }}</span>
      <button
        type="button"
        class="demo-banner__close"
        :aria-label="closeLabel"
        @click="hideDemoNotice"
      >
        <Close :size="18" />
      </button>
    </div>
  </transition>
</template>

<script>
import { mapState, mapActions } from "pinia";

import ArrowUpBold from "vue-material-design-icons/ArrowUpBold.vue";
import Close from "vue-material-design-icons/Close.vue";

import { useDemoBannerStore } from "@/app/store/demoBanner";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "DemoBanner",
  components: {
    ArrowUpBold,
    Close,
  },
  mixins: [contextualTranslationsMixin],
  computed: {
    ...mapState(useDemoBannerStore, ["visible", "message"]),
    closeLabel() {
      return this.contextTranslate("Close", this.context);
    },
  },
  methods: {
    ...mapActions(useDemoBannerStore, ["hideDemoNotice"]),
  },
};
</script>

<style scoped>
.demo-banner {
  position: fixed;
  top: 80px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 100000;
  display: flex;
  align-items: center;
  gap: 12px;
  max-width: min(90vw, 560px);
  padding: 12px 14px 12px 16px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large, 12px);
  background-color: var(--color-main-background);
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.2);
  color: var(--color-main-text);
}

.demo-banner__badge {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  flex: 0 0 auto;
  height: 20px;
  padding: 0 8px 0 5px;
  border-radius: 10px;
  background-color: var(--color-primary-element);
  color: var(--color-primary-element-text);
  font-size: 11px;
  font-weight: 600;
  text-transform: lowercase;
  letter-spacing: 0.02em;
}

.demo-banner__text {
  flex: 1 1 auto;
  font-size: 14px;
  line-height: 1.4;
}

.demo-banner__close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  width: 28px;
  height: 28px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background-color: transparent;
  color: var(--color-text-maxcontrast);
  cursor: pointer;
}

.demo-banner__close:hover {
  background-color: var(--color-background-hover);
  color: var(--color-main-text);
}

.demo-banner-enter-active,
.demo-banner-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.demo-banner-enter,
.demo-banner-leave-to {
  opacity: 0;
  transform: translate(-50%, -8px);
}
</style>