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
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import {
  fetchGlobalRolesByUserSlug,
  assignRoleToUser,
  removeRoleFromUser,
} from "@/admin/entities/globalRoles/api";
import { fetchGlobalRolesDictionary } from "@/admin/entities/dictionaries/api";

import { VPageContent, VPagePadding } from "@/common/widgets/VPage";

import { VTable } from "@/common/features/VTable";

import AssignRoleToUserForm from "@/admin/widgets/AssignRoleToUserForm/AssignRoleToUserForm.vue";

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
      context: 'admin/users',
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
          label: this.contextTranslate('Role', this.context),
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
        const promises = [fetchGlobalRolesDictionary()];

        const [{ data: roleOptions }] = await Promise.all(promises);

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
        const { data } = await fetchGlobalRolesByUserSlug(this.slug);

        this.tableData = data;

        this.fetchDictionaries();
      } catch (e) {
        console.log(e);
      } finally {
        this.isLoading = false;
      }
    },
    async handleDelete({ slug, slug_type }) {
      if (!confirm(this.contextTranslate('Are you sure you want to delete the role?', this.context))) {
        return;
      }

      try {
        await removeRoleFromUser({ slug, slug_type });

        this.handleFetchData();
      } catch (e) {
        console.log(e);
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
        console.log(e);

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
