/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage>
    <DynamicTable :source="source" @on-fetch="handleFetchData">
      <template #toolbar-left>
        <FinancesContractBreadcrumbs />
      </template>
      <template #toolbar-controls>
        <NcButton
          v-if="getCommonPermission('canReadFinances') === true"
          :to="{ name: 'finances-contract-parameter-group-new' }"
        >
          <template #icon>
            <Plus />
          </template>
          {{ contextTranslate("Create", context) }}
        </NcButton>
        <ExportButton
          :source="source"
          context-name="admin/users"
        />
      </template>
      <template #controls="{ row: { slug } }">
        <NcActions :inline="1">
          <NcActionButton
            v-if="getCommonPermission('canReadFinances') === true"
            :aria-label="contextTranslate('Edit', context)"
            @click="() => handleClickEdit(slug)"
          >
            <template #icon>
              <Pencil />
              {{ contextTranslate("Edit", context) }}
            </template>
          </NcActionButton>
          <NcActionButton
            v-if="getCommonPermission('canReadFinances') === true"
            :force-name="true"
            :aria-label="contextTranslate('Delete employee', context)"
            @click="() => handleDelete(slug)"
          >
            <template #icon>
              <TrashCanOutline />
              {{ contextTranslate("Delete", context) }}
            </template>
          </NcActionButton>
        </NcActions>
      </template>
      <template #emptyContentActions>
        <NcButton
          :to="{ name: 'finances-contract-parameter-group-new' }"
          class="mt-2"
        >
          <template #icon> <Plus /> </template>
          {{ contextTranslate("Create", context) }}
        </NcButton>
      </template>
    </DynamicTable>
  </VPage>
</template>

<script>
import { NcActions, NcActionButton, NcButton } from "@nextcloud/vue";
import { mapState } from "pinia";

import CashMultiple from "vue-material-design-icons/CashMultiple.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";
import EyeOutline from "vue-material-design-icons/EyeOutline.vue";
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";
import ClockTimeEightOutline from "vue-material-design-icons/ClockTimeEightOutline.vue";

import {
  VPage,
  FinancesContractBreadcrumbs,
  DynamicTable,
  ExportButton,
} from "@/widgets";

import { usePermissionStore } from "@/app/store/permission";

import {
  fetchContractParameterGroupsTableData,
  deleteContractParameterGroupBySlug,
} from "@/entities/contractParameterGroups/api";

import { dynamicTableMixin } from "@/shared/lib/mixins/dynamicTableMixin";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { contractTabsMixin } from "@/shared/lib/mixins/contractTabsMixin";

import { MAP_DYNAMIC_TABLE_SOURCES } from "@/entities/dynamicTables/constants";

export default {
  name: "FinancesContractParameterGroupTablePage",
  mixins: [dynamicTableMixin, contextualTranslationsMixin, contractTabsMixin],
  components: {
    NcActions,
    NcActionButton,
    NcButton,
    CashMultiple,
    Plus,
    Pencil,
    EyeOutline,
    TrashCanOutline,
    ClockTimeEightOutline,
    VPage,
    FinancesContractBreadcrumbs,
    DynamicTable,
    ExportButton,
  },
  computed: {
    ...mapState(usePermissionStore, ["canReadField", "getCommonPermission"]),
    source() {
      return MAP_DYNAMIC_TABLE_SOURCES["contractParameterGroup"];
    },
  },
  methods: {
    handleClickEdit(slug) {
      this.$router.push({
        name: "finances-contract-parameter-group-edit",
        params: { slug },
      });
    },
    async handleFetchData() {
      try {
        this.setTableLoading(true);

        const { data } = await fetchContractParameterGroupsTableData();

        this.initDynamicTable(data);
      } catch (e) {
        console.error(e);
      } finally {
        this.setTableLoading(false);
      }
    },
    async handleDelete(slug) {
      if (
        !confirm(
          this.contextTranslate(
            "Are you sure you want to delete the record?",
            this.context,
          ),
        )
      ) {
        return;
      }

      try {
        await deleteContractParameterGroupBySlug(slug);

        this.handleFetchData();
      } catch (e) {
        console.error(e);
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
