<template>
  <VPageContent>
    <VPagePadding>
      <AssignUserToProjectForm
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
      <template #controls="{ row: { id } }">
        <NcActions :inline="1">
          <NcActionButton
            :aria-label="contextTranslate('Delete', context)"
            @click="() => handleDelete({ id })"
          >
            <template #icon>
              <TrashCanOutline />
            </template>
          </NcActionButton>
        </NcActions>
      </template>
      <template #rname="{ row }">
        <VTableEditCellDropdown
          :value="{ name: row.rname, id: row.role_id }"
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

import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import { VPageContent, VPagePadding } from "@/common/widgets/VPage";
import { VTable, VTableEditCellDropdown } from "@/common/features/VTable";

import AssignUserToProjectForm from "@/admin/widgets/AssignUserToProjectForm/AssignUserToProjectForm.vue";

import {
  getUsersInProject,
  assignUserToProject,
  removeUserFromProject,
  editUserRoleInProject,
} from "@/common/entities/projects/api";
import { fetchUsersForProject } from "@/common/entities/users/api";
import { fetchRolesDictionary } from "@/admin/entities/dictionaries/api";

import { handleRestErrors } from "@/common/shared/lib/helpers";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "ProjectsEditUsersPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcActions,
    NcActionButton,
    TrashCanOutline,
    VPageContent,
    VPagePadding,
    VTable,
    VTableEditCellDropdown,
    AssignUserToProjectForm,
  },
  data() {
    return {
      context: "admin/projects",
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
          label: this.contextTranslate("Employee", this.context),
          key: "uname",
        },
        {
          label: this.contextTranslate("Position", this.context),
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
    t,
    async fetchDictionaries() {
      this.isDictionaryLoading = true;

      try {
        const promises = [
          fetchUsersForProject({ slug: this.slug }),
          fetchRolesDictionary(),
        ];

        const [{ data: userOptions }, { data: roleOptions }] =
          await Promise.all(promises);

        this.userOptions = userOptions.map((item) => {
          const { id, full_name, position_id } = item;
          const position = position_id?.toLowerCase();

          if (position) {
            return {
              ...item,
              id: String(id),
              displayName: full_name,
              name: full_name,
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
        console.log(e);
      } finally {
        this.isDictionaryLoading = false;
      }
    },
    async handleFetchData() {
      this.isLoading = true;

      try {
        const { data } = await getUsersInProject({ slug: this.slug });

        const result = data.map((item) => ({
          ...item,
          isSubmitLoading: false,
        }));

        this.tableData = result;
      } catch (e) {
        console.log(e);
      } finally {
        this.isLoading = false;
      }
    },
    async handleDelete({ id }) {
      if (!confirm(t("done", "Are you sure you want to delete the record?"))) {
        return;
      }

      try {
        await removeUserFromProject({ slug: id });

        this.handleFetchData();
      } catch (e) {
        console.log(e);
      }
    },
    async handleSubmitRoleChange({ value, row }) {
      try {
        row.isSubmitLoading = true;
        row.rname = value.name;
        row.role_id = value.id;

        await editUserRoleInProject({
          slug: row.id,
          role: {
            slug: value.id,
          },
        });

        this.$notify({
          text: t("done", "Role updated successfully"),
          type: "success",
          duration: 2 * 1000,
        });
      } catch (e) {
        console.log(e);

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
        await assignUserToProject({
          project: {
            slug: this.slug,
          },
          user: {
            slug: user.slug,
            slug_type: user.slug_type,
          },
          role: {
            slug: role.slug,
            slug_type: role.slug_type,
          },
        });

        this.handleFetchData();
      } catch (e) {
        console.log(e);

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
