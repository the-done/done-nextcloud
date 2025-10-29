/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb :name="contextTranslate('Reports', context)"  :to="{ name: 'report-home' }" forceIconText>
          <template #icon>
            <FileChart />
          </template>
        </NcBreadcrumb>
      </NcBreadcrumbs>
    </VToolbar>
    <VPageLayout>
      <VPageContent>
        <VPagePadding>
          <ul>
            <template v-for="(item, index) in reportsNavigation">
              <NcListItem
                v-if="item.visible"
                :key="index"
                :name="contextTranslate(item.label, context)"
                :to="item.to"
                compact
              >
                <template #icon>
                  <FolderOutline />
                </template>
              </NcListItem>
            </template>
          </ul>
        </VPagePadding>
      </VPageContent>
    </VPageLayout>
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb, NcListItem } from "@nextcloud/vue";

import FileChart from "vue-material-design-icons/FileChart.vue";
import FolderOutline from "vue-material-design-icons/FolderOutline.vue";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
} from "@/common/widgets/VPage";

import { reportsNavigationMixin } from "@/admin/shared/lib/mixins/reportsNavigationMixin";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";

import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

export default {
  name: "ReportsHomePage",
  mixins: [reportsNavigationMixin, contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcListItem,
    FileChart,
    FolderOutline,
    VPage,
    VPageLayout,
    VPageContent,
    VPagePadding,
    VToolbar,
  },
  methods: {
    t
  },
};
</script>
