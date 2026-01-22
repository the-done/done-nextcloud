/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage class="error-page">
    <VPageContent content-class="error-page__content">
      <VPagePadding>
        <PagePlaceholderWidget
          name="404"
          :description="contextTranslate('Page not found', context)"
        />
      </VPagePadding>
    </VPageContent>
  </VPage>
</template>

<script>
import {
  VPage,
  VPageContent,
  VPagePadding,
  PagePlaceholderWidget,
} from "@/widgets";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "404Page",
  mixins: [contextualTranslationsMixin],
  components: {
    VPage,
    VPageContent,
    VPagePadding,
    PagePlaceholderWidget,
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
