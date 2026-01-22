<template>
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
  </NcBreadcrumbs>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb } from "@nextcloud/vue";

import CashMultiple from "vue-material-design-icons/CashMultiple.vue";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "FinancesContractBreadcrumbs",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    CashMultiple,
  },
};
</script>
