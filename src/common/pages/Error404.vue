/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage class="error-page">
    <VPageContent content-class="error-page__content">
      <VPagePadding>
        <PagePlaceholderWidget name="404" :description="contextTranslate('Page not found', context)" />
      </VPagePadding>
    </VPageContent>
  </VPage>
</template>

<script>
import { VPage, VPageContent, VPagePadding } from "@/common/widgets/VPage";
import { PagePlaceholderWidget } from "@/common/widgets/PagePlaceholderWidget";
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

export default {
  name: "404Page",
  mixins:[contextualTranslationsMixin],
  components: {
    VPage,
    VPageContent,
    VPagePadding,
    PagePlaceholderWidget,
  },
  methods: {
    t,
  },
};
</script>

<style scoped>
.error-page {
  display: flex;
  flex-direction: column;
  width: 100%;
  height: 100%;
  background-color: var(--color-main-background);
}

.error-page:deep(.error-page__content) {
  align-items: center;
  justify-content: center;
}
</style>
