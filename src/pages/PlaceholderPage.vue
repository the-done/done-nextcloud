/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

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
import { t } from "@nextcloud/l10n";

import {
  VPage,
  VPageContent,
  VPagePadding,
  PagePlaceholderWidget,
} from "@/widgets";

export default {
  name: "PlaceholderPage",
  components: {
    VPage,
    VPageContent,
    VPagePadding,
    PagePlaceholderWidget,
  },
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
  methods: {},
};
</script>
