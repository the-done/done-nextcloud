<template>
  <VPage>
    <DynamicTable
      v-model="tableData"
      :all-columns-ordering.sync="columnsWithControls"
      :loading="tableIsLoading"
      :source="source"
      :settings="settings"
      :empty-content-description="
        contextTranslate('No data found for your payments', context)
      "
      @on-fetch="handleFetchData"
    >
      <template #toolbar-left>
        <NcBreadcrumbs>
          <NcBreadcrumb
            :name="contextTranslate('Finances', context)"
            :to="{ name: 'finances-home' }"
            forceIconText
          >
            <template #icon>
              <Cash />
            </template>
          </NcBreadcrumb>
          <NcBreadcrumb
            :name="contextTranslate('Payments', context)"
            :to="{ name: 'finances-payments' }"
            forceIconText
          >
            <template #icon>
              <Cash />
            </template>
          </NcBreadcrumb>
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
            {{ contextTranslate("Create Payment", context) }}
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
        <NcButton
          :to="{ name: 'finances-payment-new' }"
          type="primary"
          class="v-mt-1"
        >
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

import Cash from "vue-material-design-icons/Cash.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import { VPage } from "@/common/widgets/VPage";
import { DynamicTable } from "@/admin/widgets/DynamicTable";
import ExportButton from "@/admin/widgets/ExportButton.vue";

import {
  fetchPaymentsTableData,
  deletePayment,
} from "@/common/entities/finances/api";

import { usePermissionStore } from "@/admin/app/store/permission";

import { dynamicTableMixin } from "@/common/shared/mixins/dynamicTableMixin";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { MAP_DYNAMIC_TABLE_SOURCES } from "@/admin/entities/dynamicTables/constants";
import { MAP_ENTITY_SOURCE } from "@/common/shared/lib/constants";

export default {
  name: "FinancesPaymentsPage",
  mixins: [dynamicTableMixin, contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcButton,
    NcActions,
    NcActionButton,
    Cash,
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
      return MAP_DYNAMIC_TABLE_SOURCES["finances"];
    },
    columnsWithControls: {
      get() {
        return [
          {
            title: "",
            key: "controls",
            draggable: false,
            sortable: false,
            filterable: false,
            hideable: false,
            customClass: "w-[100px]",
          },
          ...this.allColumnsOrdering,
        ];
      },
      set(value) {
        this.allColumnsOrdering = value.filter(
          (item) => item.key !== "controls"
        );
      },
    },
  },
  methods: {
    t,
    handleClickEdit(slug) {
      this.$router.push({ name: "finances-payment-edit", params: { slug } });
    },
    async handleFetchData() {
      try {
        this.tableIsLoading = true;

        const { data } = await fetchPaymentsTableData();

        this.initDynamicTable(data);
      } catch (e) {
        console.log(e);
      } finally {
        this.tableIsLoading = false;
      }
    },
    async handleDelete({ slug, slug_type }) {
      if (!confirm(t("done", "Do you really want to delete the payment?"))) {
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
