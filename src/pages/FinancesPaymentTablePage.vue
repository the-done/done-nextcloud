/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage>
    <DynamicTable :source="source" @on-fetch="handleFetchData">
      <template #toolbar-left>
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
            :name="contextTranslate('Payments', context)"
            :to="{ name: 'finances-payment-table' }"
            forceIconText
          />
        </NcBreadcrumbs>
      </template>
      <template #toolbar-controls>
        <NcActions :force-name="true">
          <NcActionButton
            :aria-label="contextTranslate('Create Payment', context)"
            :to="{ name: 'finances-payment-new' }"
          >
            <template #icon>
              <Plus />
            </template>
            {{ contextTranslate("Create", context) }}
          </NcActionButton>
        </NcActions>
        <ExportButton
          :source="MAP_ENTITY_SOURCE['payment']"
          context-name="admin/finances"
        />
      </template>
      <template #controls="{ row: { slug, slug_type } }">
        <NcActions :inline="1">
          <NcActionButton
            :aria-label="contextTranslate('Edit Payment', context)"
            @click="() => handleClickEdit(slug)"
          >
            <template #icon>
              <Pencil />
              {{ contextTranslate("Edit", context) }}
            </template>
          </NcActionButton>
          <NcActionButton
            :force-name="true"
            :aria-label="contextTranslate('Delete Payment', context)"
            @click="() => handleDelete({ slug, slug_type })"
          >
            <template #icon>
              <TrashCanOutline />
              {{ contextTranslate("Delete", context) }}
            </template>
          </NcActionButton>
        </NcActions>
      </template>
      <template #emptyContentActions>
        <NcButton :to="{ name: 'finances-payment-new' }" class="mt-2">
          <template #icon> <Plus /> </template>
          {{ contextTranslate("Create Payment", context) }}
        </NcButton>
      </template>
    </DynamicTable>
  </VPage>
</template>

<script>
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcButton,
  NcActions,
  NcActionButton,
} from "@nextcloud/vue";
import { mapState } from "pinia";

import CashMultiple from "vue-material-design-icons/CashMultiple.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import { VPage, DynamicTable, ExportButton } from "@/widgets";

import { fetchPaymentsTableData, deletePayment } from "@/entities/finances/api";

import { usePermissionStore } from "@/app/store/permission";

import { dynamicTableMixin } from "@/shared/lib/mixins/dynamicTableMixin";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { MAP_DYNAMIC_TABLE_SOURCES } from "@/entities/dynamicTables/constants";
import { MAP_ENTITY_SOURCE } from "@/shared/lib/constants/entity";

export default {
  name: "FinancesPaymentTablePage",
  mixins: [dynamicTableMixin, contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcButton,
    NcActions,
    NcActionButton,
    CashMultiple,
    Plus,
    Pencil,
    TrashCanOutline,
    VPage,
    DynamicTable,
    ExportButton,
  },
  data() {
    return {
      context: "admin/finances",
      MAP_ENTITY_SOURCE,
    };
  },
  computed: {
    ...mapState(usePermissionStore, ["getCommonPermission"]),
    source() {
      return MAP_DYNAMIC_TABLE_SOURCES["payment"];
    },
  },
  methods: {
    handleClickEdit(slug) {
      this.$router.push({ name: "finances-payment-edit", params: { slug } });
    },
    async handleFetchData() {
      try {
        this.setTableLoading(true);

        const { data } = await fetchPaymentsTableData();

        this.initDynamicTable(data);
      } catch (e) {
        console.log(e);
      } finally {
        this.setTableLoading(false);
      }
    },
    async handleDelete({ slug, slug_type }) {
      if (
        !confirm(
          this.contextTranslate("Are you sure you want to delete the record?"),
        )
      ) {
        return;
      }

      try {
        await deletePayment({ slug, slug_type });

        this.handleFetchData();
      } catch (e) {
        console.log(e);
      }
    },
    init() {
      this.handleFetchData();
    },
  },
  mounted() {
    this.init();
  },
};
</script>

<style scoped>
/* Styles for payments table */
</style>
