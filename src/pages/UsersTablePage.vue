/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage>
    <DynamicTable :source="source" @on-fetch="handleFetchData">
      <template #toolbar-left>
        <NcBreadcrumbs>
          <NcBreadcrumb
            :name="contextTranslate('Employees', context)"
            :to="{ name: 'staff-table' }"
            forceIconText
          >
            <template #icon>
              <AccountMultiple />
            </template>
          </NcBreadcrumb>
        </NcBreadcrumbs>
      </template>
      <template #toolbar-controls>
        <NcCheckboxRadioSwitch v-model="showDeleted" type="switch">
          {{ contextTranslate("Deleted", context) }}
        </NcCheckboxRadioSwitch>
        <NcButton
          v-if="getCommonPermission('canCreateUsers') === true"
          :to="{ name: 'staff-new' }"
        >
          <template #icon>
            <Plus />
          </template>
          {{ contextTranslate("Create", context) }}
        </NcButton>
        <ExportButton :source="source" context-name="admin/users" />
      </template>
      <template #controls="{ row: { slug, slug_type } }">
        <NcActions :inline="1">
          <NcActionButton
            v-if="
              getCommonPermission('canReadUsersList') === true ||
              getCommonPermission('canViewEmployeesListRelated') === true
            "
            :aria-label="contextTranslate('Preview', context)"
            @click="() => handleClickPreview(slug)"
          >
            <template #icon>
              <EyeOutline />
              {{ contextTranslate("Preview", context) }}
            </template>
          </NcActionButton>
          <NcActionButton
            v-if="
              getCommonPermission('canReadStatisticsAllUsers', context) === true
            "
            :aria-label="contextTranslate('View time', context)"
            @click="() => handleClickViewTime(slug)"
          >
            <template #icon>
              <ClockTimeEightOutline />
              {{ contextTranslate("View time", context) }}
            </template>
          </NcActionButton>
          <NcActionButton
            v-if="getCommonPermission('canReadUsersProfile') === true"
            :aria-label="contextTranslate('Edit', context)"
            @click="() => handleClickEdit({ slug })"
          >
            <template #icon>
              <Pencil />
              {{ contextTranslate("Edit", context) }}
            </template>
          </NcActionButton>
          <NcActionButton
            v-if="
              !showDeleted && getCommonPermission('canDismissUsers') === true
            "
            :force-name="true"
            :aria-label="contextTranslate('Delete employee', context)"
            @click="() => handleDelete({ slug, slug_type })"
          >
            <template #icon>
              <TrashCanOutline />
              {{ contextTranslate("Delete", context) }}
            </template>
          </NcActionButton>
          <NcActionButton
            v-if="
              showDeleted && getCommonPermission('canDismissUsers') === true
            "
            :force-name="true"
            :aria-label="contextTranslate('Restore employee', context)"
            @click="() => handleRestore({ slug })"
          >
            <template #icon>
              <Restore />
              {{ contextTranslate("Restore", context) }}
            </template>
          </NcActionButton>
        </NcActions>
      </template>
      <template #emptyContentActions>
        <NcButton
          :to="{ name: 'staff-new' }"
          v-if="getCommonPermission('canCreateUsers') === true"
          type="primary"
          class="v-mt-1"
        >
          <template #icon> <Plus /> </template>
          {{ contextTranslate("Create", context) }}
        </NcButton>
      </template>
    </DynamicTable>
  </VPage>
</template>

<script>
import { mapState } from "pinia";
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcActions,
  NcActionButton,
  NcButton,
  NcCheckboxRadioSwitch,
} from "@nextcloud/vue";

import AccountMultiple from "vue-material-design-icons/AccountMultiple.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";
import EyeOutline from "vue-material-design-icons/EyeOutline.vue";
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";
import ClockTimeEightOutline from "vue-material-design-icons/ClockTimeEightOutline.vue";
import Restore from "vue-material-design-icons/Restore.vue";

import { VPage, DynamicTable, ExportButton } from "@/widgets";

import { usePermissionStore } from "@/app/store/permission";

import {
  fetchUsersTableData,
  deleteUser,
  restoreUser,
} from "@/entities/users/api";

import { dynamicTableMixin } from "@/shared/lib/mixins/dynamicTableMixin";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { MAP_DYNAMIC_TABLE_SOURCES } from "@/entities/dynamicTables/constants";

export default {
  name: "UsersTablePage",
  mixins: [dynamicTableMixin, contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    NcButton,
    NcCheckboxRadioSwitch,
    ExportButton,
    AccountMultiple,
    Plus,
    Pencil,
    EyeOutline,
    TrashCanOutline,
    ClockTimeEightOutline,
    Restore,
    VPage,
    DynamicTable,
  },
  data() {
    const savedShowDeleted = localStorage.getItem("usersTableShowDeleted");
    return {
      showDeleted: savedShowDeleted === "true",
    };
  },
  computed: {
    ...mapState(usePermissionStore, ["canReadField", "getCommonPermission"]),
    source() {
      return MAP_DYNAMIC_TABLE_SOURCES["user"];
    },
  },
  methods: {
    handleClickPreview(slug) {
      this.$router.push({ name: "staff-preview", params: { slug } });
    },
    handleClickViewTime(slug) {
      this.$router.push({ name: "staff-statistics", params: { slug } });
    },
    handleClickEdit({ slug }) {
      this.$router.push({ name: "staff-edit", params: { slug } });
    },
    async handleFetchData() {
      try {
        this.setTableLoading(true);

        const { data } = await fetchUsersTableData({
          need_deleted: this.showDeleted,
        });

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
          this.contextTranslate(
            "Do you really want to delete the employee?",
            this.context,
          ),
        )
      ) {
        return;
      }

      try {
        await deleteUser({ slug, slug_type });

        this.handleFetchData();
      } catch (e) {
        console.log(e);
      }
    },
    async handleRestore({ slug }) {
      if (
        !confirm(
          this.contextTranslate(
            "Do you really want to restore the employee?",
            this.context,
          ),
        )
      ) {
        return;
      }

      try {
        await restoreUser({ slug });

        this.handleFetchData();
      } catch (e) {
        console.log(e);
      }
    },
    init() {
      this.handleFetchData();
    },
  },
  watch: {
    showDeleted(newValue) {
      localStorage.setItem("usersTableShowDeleted", newValue);

      this.handleFetchData();
    },
  },
  mounted() {
    this.init();
  },
};
</script>
