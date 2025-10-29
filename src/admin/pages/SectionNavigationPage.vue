/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          v-for="(item, index) in breadcrumbs"
          :key="index"
          :name="contextTranslate(item.title, context)"
          :to="item.path"
          :forceIconText="index === 0"
        >
          <template v-if="item.icon" #icon>
            <component v-if="item.icon" :is="item.icon" />
            <BookOpenVariant v-else />
          </template>
        </NcBreadcrumb>
      </NcBreadcrumbs>
    </VToolbar>
    <VPageLayout>
      <VPageContent>
        <VPagePadding>
          <ul>
            <NcListItem
              v-for="(item, index) in navigation"
              v-if="item.visible ?? true"
              :key="index"
              :name="contextTranslate(item.label, context)"
              :to="item.to"
              compact
            >
              <template #icon>
                <FolderOutline />
              </template>
            </NcListItem>
          </ul>
        </VPagePadding>
      </VPageContent>
    </VPageLayout>
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb, NcListItem } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import BookOpenVariant from "vue-material-design-icons/BookOpenVariant.vue";
import FolderOutline from "vue-material-design-icons/FolderOutline.vue";

import { useModulesStore } from "@/admin/app/store/modules";
import { settingsNavigationMixin } from "@/admin/shared/lib/mixins/settingsNavigationMixin";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
} from "@/common/widgets/VPage";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import { mapState } from "pinia";

export default {
  name: "DictionaryPositionsPage",
  mixins: [contextualTranslationsMixin, settingsNavigationMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcListItem,
    BookOpenVariant,
    FolderOutline,
    VPage,
    VPageLayout,
    VPageContent,
    VPagePadding,
    VToolbar,
  },
  props: {
    additionalProps: {
      type: Object,
      default: () => ({}),
    },
  },
  data: () => ({
    context: "admin/users",
  }),
  computed: {
    ...mapState(useModulesStore, ["moduleExist"]),
    breadcrumbs() {
      return this.additionalProps.breadcrumbs || [];
    },
    navigation() {
      if (!this.additionalProps.navigationName) {
        return [];
      }

      return this[this.additionalProps.navigationName]?.[0]?.children || [];
    },
  },
  methods: { t },
};
</script>
