/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPageContent>
    <VPagePadding>
      <AssignRoleToUserForm
        :role-options="roleOptions"
        :loading="isDictionaryLoading"
        @onSubmit="handleSubmit"
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
    </VTable>
  </VPageContent>
</template>

<script>
import { NcActions, NcActionButton } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";

import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import {
  fetchGlobalRolesByUserSlug,
  assignRoleToUser,
  removeRoleFromUser,
} from "@/entities/globalRoles/api";
import { fetchGlobalRolesDictionary } from "@/entities/dictionaries/api";

import { VPageContent, VPagePadding, AssignRoleToUserForm } from "@/widgets";

import { VTable } from "@/features";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "UsersEditRolesPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcActions,
    NcActionButton,
    TrashCanOutline,
    VPageContent,
    VPagePadding,
    VTable,
    AssignRoleToUserForm,
  },
  data() {
    return {
      context: "admin/users",
      isLoading: true,
      isDictionaryLoading: true,
      tableData: [],
      roleOptions: [],
      columns: [
        {
          label: "",
          key: "controls",
        },
        {
          label: t("done", "Role"),
          key: "rname",
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
        const promises = [fetchGlobalRolesDictionary()];

        const [{ data: roleOptions }] = await Promise.all(promises);

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
        const { data } = await fetchGlobalRolesByUserSlug(this.slug);

        this.tableData = data;

        this.fetchDictionaries();
      } catch (e) {
        console.error(e);
      } finally {
        this.isLoading = false;
      }
    },
    async handleDelete({ slug, slug_type }) {
      if (
        !confirm(
          this.contextTranslate(
            "Are you sure you want to delete the record?",
            this.context
          )
        )
      ) {
        return;
      }

      try {
        await removeRoleFromUser({ slug, slug_type });

        this.handleFetchData();
      } catch (e) {
        console.error(e);
      }
    },
    async handleSubmit({ role }) {
      this.isLoading = true;

      try {
        await assignRoleToUser({
          user: { slug: this.slug },
          role: { slug: role.slug, slug_type: role.slug_type },
        });

        this.handleFetchData();
      } catch (e) {
        console.error(e);

        this.isLoading = false;
      }
    },
  },
  mounted() {
    this.handleFetchData();
  },
};
</script>

<style scoped>
.users-edit-roles-page-table {
  width: 100%;
}
</style>
