/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb :name="contextTranslate('Finances', context)" :to="{ name: 'finances-home' }" forceIconText>
          <template #icon>
            <Cash />
          </template>
        </NcBreadcrumb>
      </NcBreadcrumbs>
    </VToolbar>
    <VPageLayout>
      <VPageContent>
        <VPagePadding>
          <ul>
            <template v-for="(item, index) in financesNavigation">
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

import Cash from "vue-material-design-icons/Cash.vue";
import FolderOutline from "vue-material-design-icons/FolderOutline.vue";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
} from "@/common/widgets/VPage";

import { financesNavigationMixin } from "@/admin/shared/lib/mixins/financesNavigationMixin";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";

import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

export default {
  name: "FinancesHomePage",
  mixins: [financesNavigationMixin, contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcListItem,
    Cash,
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
