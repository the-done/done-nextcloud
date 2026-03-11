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
            :name="contextTranslate('Contract parameters', context)"
            :to="{ name: 'finances-contract-parameter-table' }"
            forceIconText
          />
        </NcBreadcrumbs>
      </template>
      <template #toolbar-controls>
        <NcButton
          v-if="getCommonPermission('canReadFinances') === true"
          :to="{ name: 'finances-contract-parameter-new' }"
        >
          <template #icon>
            <Plus />
          </template>
          {{ contextTranslate("Create", context) }}
        </NcButton>
        <ExportButton :source="source" context-name="admin/contracts" />
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
            :aria-label="contextTranslate('Delete', context)"
            @click="() => handleDelete(slug)"
          >
            <template #icon>
              <TrashCanOutline />
              {{ contextTranslate("Delete", context) }}
            </template>
          </NcActionButton>
        </NcActions>
      </template>
      <template #type_id="{ value }">
        {{
          contractParameterTypes[value]
            ? contextTranslate(contractParameterTypes[value].label)
            : value
        }}
      </template>
      <template #emptyContentActions>
        <NcButton
          :to="{ name: 'finances-contract-parameter-new' }"
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
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcActions,
  NcActionButton,
  NcButton,
} from "@nextcloud/vue";
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
  fetchContractParametersTableData,
  deleteContractParameterBySlug,
} from "@/entities/contractParameters/api";

import { dynamicTableMixin } from "@/shared/lib/mixins/dynamicTableMixin";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { MAP_DYNAMIC_TABLE_SOURCES } from "@/entities/dynamicTables/constants";
import { MAP_CONTRACT_PARAMETER_TYPES } from "@/entities/contractParameters/constants.js";

export default {
  name: "FinancesContractParameterTablePage",
  mixins: [dynamicTableMixin, contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
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
  data() {
    return {
      contractParameterTypes: MAP_CONTRACT_PARAMETER_TYPES,
    };
  },
  computed: {
    ...mapState(usePermissionStore, ["canReadField", "getCommonPermission"]),
    source() {
      return MAP_DYNAMIC_TABLE_SOURCES["contractParameter"];
    },
  },
  methods: {
    handleClickEdit(slug) {
      this.$router.push({
        name: "finances-contract-parameter-edit",
        params: { slug },
      });
    },
    async handleFetchData() {
      try {
        this.setTableLoading(true);

        const { data } = await fetchContractParametersTableData();

        this.initDynamicTable(data);
      } catch (e) {
        console.log(e);
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
        await deleteContractParameterBySlug(slug);

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
