/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('Finances', context)"
          :to="{ name: 'finances-home' }"
          forceIconText
        >
          <template #icon>
            <CashMultiple />
          </template>
        </NcBreadcrumb>
        <NcBreadcrumb
          :name="contextTranslate('Contracts', context)"
          :to="{ name: 'finances-contract-table' }"
          forceIconText
        />
        <NcBreadcrumb :name="pageTitle" />
        <NcBreadcrumb
          v-if="isEdit === true"
          :name="contextTranslate('Edit', context)"
        />
      </NcBreadcrumbs>
    </VToolbar>
    <VPageLayout>
      <VPageAsideNavigation v-if="isEdit === true" :items="navigation" />
      <RouterView />
    </VPageLayout>
  </VPage>
</template>

<script>
import { NcBreadcrumb, NcBreadcrumbs } from "@nextcloud/vue";

import CashMultiple from "vue-material-design-icons/CashMultiple.vue";

import {
  VPage,
  VPageLayout,
  VPageAsideNavigation,
  VPageContent,
} from "@/widgets";
import { VTabs, VToolbar } from "@/shared/components";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "FinancesContractEditPage",
  mixins: [contextualTranslationsMixin],
  components: {
    CashMultiple,
    NcBreadcrumbs,
    NcBreadcrumb,
    VPage,
    VPageLayout,
    VPageAsideNavigation,
    VPageContent,
    VTabs,
    VToolbar,
  },
  computed: {
    slug() {
      return this.$route.params.slug;
    },
    isEdit() {
      return Boolean(this.slug);
    },
    pageTitle() {
      if (this.isEdit === false) {
        return this.contextTranslate("Create contract", this.context);
      }

      return String(this.slug);
    },
    navigation() {
      return [
        {
          key: "edit",
          label: this.contextTranslate("Main", this.context),
          to: { name: "finances-contract-edit", params: { slug: this.slug } },
          exact: true,
        },
        {
          key: "parameters",
          label: this.contextTranslate("Contract parameters", this.context),
          to: {
            name: "finances-contract-parameter-value",
            params: { slug: this.slug },
          },
          exact: true,
        },
      ];
    },
  },
};
</script>
