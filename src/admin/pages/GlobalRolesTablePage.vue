/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('Employees', context)"
          :to="{ name: 'settings-staff' }"
          forceIconText
        >
          <template #icon>
            <AccountMultiple />
          </template>
        </NcBreadcrumb>
        <NcBreadcrumb :name="contextTranslate('Employee roles', context)" />
      </NcBreadcrumbs>
    </VToolbar>
    <VTable
      v-model="tableData"
      :columns="columns"
      :loading="isLoading"
      empty-content-description=""
    >
      <template #controls="{ row }">
        <NcActions :inline="1">
          <NcActionButton
            :aria-label="contextTranslate('Employees', context)"
            :to="{
              name: 'settings-staff-global-roles-edit',
              params: { slug: row.slug },
            }"
          >
            <template #icon>
              <AccountFileText />
            </template>
          </NcActionButton>
        </NcActions>
      </template>

      <template #emptyStateDescription>
        <p class="v-text-center">
          {{
            contextTranslate(
              "Employee roles cannot be created through the application UI.",
              context
            )
          }}
        </p>
        <p class="v-text-center">
          {{ contextTranslate("Please contact the administrator.", context) }}
        </p>
      </template>
    </VTable>
  </VPage>
</template>

<script>
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcActions,
  NcActionButton,
} from "@nextcloud/vue";

import AccountMultiple from "vue-material-design-icons/AccountMultiple.vue";
import AccountFileText from "vue-material-design-icons/AccountFileText.vue";

import { fetchGlobalRolesDictionary } from "@/admin/entities/dictionaries/api";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { VPage } from "@/common/widgets/VPage";
import { VTable } from "@/common/features/VTable";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";

export default {
  name: "UsersTablePage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    AccountMultiple,
    AccountFileText,
    VPage,
    VToolbar,
    VTable,
  },
  data: () => ({
    isLoading: false,
    columns: [
      {
        label: "",
        key: "controls",
      },
      {
        label: t("done", "Title"),
        key: "name",
      },
      {
        label: t("done", "Sorting"),
        key: "sort",
      },
    ],
    tableData: [],
  }),
  methods: {
    t,
    async handleFetchData() {
      try {
        this.isLoading = true;

        const { data } = await fetchGlobalRolesDictionary();

        this.tableData = data;
      } catch (e) {
        console.log(e);
      } finally {
        this.isLoading = false;
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
