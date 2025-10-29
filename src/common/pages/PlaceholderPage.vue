/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage>
    <VPageContent contentClass="justify-center">
      <VPagePadding>
        <PagePlaceholderWidget :name="name" :description="description" />
      </VPagePadding>
    </VPageContent>
  </VPage>
</template>

<script>
import { VPage, VPageContent, VPagePadding } from "@/common/widgets/VPage";
import { PagePlaceholderWidget } from "@/common/widgets/PagePlaceholderWidget";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "PlaceholderPage",
  components: {
    VPage,
    VPageContent,
    VPagePadding,
    PagePlaceholderWidget,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    name: {
      type: String,
      default: () => t("done", "In development"),
    },
    description: {
      type: String,
      default: () => t("done", "Page is under development"),
    },
  },
  methods: {
    t,
  },
};
</script>
