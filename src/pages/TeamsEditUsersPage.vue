/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPageContent>
    <VPagePadding>
      <AssignUserToTeamForm
        :user-options="userOptions"
        :role-options="roleOptions"
        :loading="isDictionaryLoading"
        @onSubmit="handleSubmit"
        @onSubmitCreateRoleSuccess="handleCreateRoleSuccess"
      />
    </VPagePadding>
    <VTable
      v-if="tableData && tableData.length > 0"
      v-model="tableData"
      :columns="columns"
      :loading="isLoading"
    >
      <template #controls="{ row: { slug, slug_type } }">
        <NcActions :inline="1">
          <NcActionButton
            :aria-label="contextTranslate('Delete', context)"
            @click="() => handleDelete({ slug, slug_type })"
          >
            <template #icon>
              <TrashCanOutline />
            </template>
          </NcActionButton>
        </NcActions>
      </template>
      <template #role_id="{ row }">
        <VTableEditCellDropdown
          :value="{ name: row.role_id_linked, id: row.role_id.id }"
          :options="roleOptions"
          :loading="row.isSubmitLoading"
          value-label="name"
          :aria-label="contextTranslate('Role', context)"
          @onSubmit="({ value }) => handleSubmitRoleChange({ value, row })"
        />
      </template>
    </VTable>
  </VPageContent>
</template>

<script>
import { NcActions, NcActionButton } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";

import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import { VPageContent, VPagePadding, AssignUserToTeamForm } from "@/widgets";
import { VTable } from "@/features";
import { VTableEditCellDropdown } from "@/shared/components";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { handleRestErrors } from "@/shared/lib/helpers/errors";

import {
  getEmployeesInTeams,
  addEmployeeToTeam,
  removeEmployeeFromTeam,
  editEmployeeToTeam,
} from "@/entities/teams/api";
import { fetchUsers } from "@/entities/users/api";
import { fetchUsersTeamRolesDictionary } from "@/entities/dictionaries/api";

export default {
  name: "TeamsEditUsersPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcActions,
    NcActionButton,
    TrashCanOutline,
    VPageContent,
    VPagePadding,
    VTable,
    VTableEditCellDropdown,
    AssignUserToTeamForm,
  },
  data() {
    return {
      context: "admin/users",
      isLoading: true,
      isDictionaryLoading: true,
      tableData: [],
      userOptions: [],
      roleOptions: [],
      columns: [
        {
          label: "",
          key: "controls",
        },
        {
          label: t("done", "Employee"),
          key: "user_id_linked",
        },
        {
          label: t("done", "Role"),
          key: "role_id",
        },
      ],
    };
  },
  computed: {
    slug() {
      return this.$route.params.slug;
    },
  },
  methods: {
    async fetchDictionaries() {
      this.isDictionaryLoading = true;

      try {
        const promises = [fetchUsers(), fetchUsersTeamRolesDictionary()];

        const [{ data: userOptions }, { data: roleOptions }] =
          await Promise.all(promises);

        this.userOptions = userOptions.map((item) => {
          const { id, full_name, pname } = item;
          const position = pname?.toLowerCase();

          if (position) {
            return {
              ...item,
              id: String(id),
              name: full_name,
              displayName: full_name,
              subname: position,
            };
          }

          return {
            ...item,
            name: full_name,
            displayName: full_name,
          };
        });

        this.roleOptions = roleOptions;
      } catch (e) {
        console.error(e);
      } finally {
        this.isDictionaryLoading = false;
      }
    },
    async handleFetchData() {
      this.isLoading = true;

      try {
        const { data } = await getEmployeesInTeams(this.slug);

        const result = data.map((item) => ({
          ...item,
          isSubmitLoading: false,
        }));

        this.tableData = result;
      } catch (e) {
        handleRestErrors(e);
      } finally {
        this.isLoading = false;
      }
    },
    async handleDelete({ slug, slug_type }) {
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
        await removeEmployeeFromTeam({ slug, slug_type });

        this.handleFetchData();
      } catch (e) {
        handleRestErrors(e);
      }
    },
    async handleSubmitRoleChange({ value, row }) {
      try {
        row.isSubmitLoading = true;
        row.role_id_linked = value.name;
        row.role_id = value.id;

        await editEmployeeToTeam({
          slug: row.id,
          role: {
            slug: value.id,
          },
        });

        this.$notify({
          text: this.contextTranslate("Role updated successfully"),
          type: "success",
          duration: 2 * 1000,
        });
      } catch (e) {
        console.error(e);

        handleRestErrors(e);
      } finally {
        row.isSubmitLoading = false;
      }
    },
    async handleCreateRoleSuccess(callback) {
      await this.fetchDictionaries();

      callback();
    },
    async handleSubmit({ user, role }) {
      this.isLoading = true;

      try {
        await addEmployeeToTeam({
          team: { slug: this.slug },
          user: { slug: user.slug, slug_type: user.slug_type },
          role: { slug: role.slug, slug_type: role.slug_type },
        });

        this.handleFetchData();
      } catch (e) {
        handleRestErrors(e);

        this.isLoading = false;
      }
    },
  },
  mounted() {
    this.handleFetchData();
    this.fetchDictionaries();
  },
};
</script>
