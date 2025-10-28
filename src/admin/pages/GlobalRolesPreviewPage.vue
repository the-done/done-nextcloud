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
        <NcBreadcrumb
          :name="contextTranslate('Employee roles', context)"
          :to="{ name: 'settings-staff-global-roles-table' }"
        />
        <NcBreadcrumb
          :name="pageTitle"
          :to="{ name: 'settings-staff-global-roles-table' }"
        />
        <NcBreadcrumb :name="contextTranslate('Employee list', context)" />
      </NcBreadcrumbs>
    </VToolbar>
    <VPagePadding>
      <AssignUserToRoleForm
        :user-options="userOptions"
        @onSubmit="handleSubmit"
      />
    </VPagePadding>
    <VTable v-model="tableData" :columns="columns" :loading="isLoading">
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
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import {
  fetchUsersByGlobalRoleSlug,
  fetchGlobalRoleBySlug,
  assignRoleToUser,
  removeRoleFromUser,
} from "@/admin/entities/globalRoles/api";

import { fetchUsers } from "@/common/entities/users/api";

import AssignUserToRoleForm from "@/admin/widgets/AssignUserToRoleForm/AssignUserToRoleForm.vue";
import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
} from "@/common/widgets/VPage";
import { VTable } from "@/common/features/VTable";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "GlobalRolesPreviewPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    AccountMultiple,
    TrashCanOutline,
    VPage,
    VPageLayout,
    VPageContent,
    VPagePadding,
    VToolbar,
    VTable,
    AssignUserToRoleForm,
  },
  data: () => ({
    isLoading: false,
    roleData: null,
    tableData: [],
    userOptions: [],
  }),
  computed: {
    slug() {
      return this.$route.params.slug;
    },
    pageTitle() {
      return this.roleData?.name || String(this.slug);
    },
    columns() {
      return [
        {
          label: "",
          key: "controls",
        },
        {
          label: this.contextTranslate("Full name", this.context),
          key: "uname",
        },
      ];
    },
  },
  methods: {
    t,
    async fetchDictionaries() {
      const promises = [fetchUsers()];

      const [{ data: userOptions }] = await Promise.all(promises);

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
          id: String(id),
          name: full_name,
          displayName: full_name,
        };
      });
    },
    async handleFetchData() {
      try {
        this.isLoading = true;

        const promises = [
          fetchGlobalRoleBySlug({ slug: this.slug }),
          fetchUsersByGlobalRoleSlug({ slug: this.slug }),
        ];

        const [{ data: roleData }, { data: tableData }] = await Promise.all(
          promises
        );

        this.roleData = roleData;
        this.tableData = tableData;
      } catch (e) {
        console.log(e);
      } finally {
        this.isLoading = false;
      }
    },
    async handleDelete({ slug, slug_type }) {
      if (
        !confirm(
          this.contextTranslate(
            "Are you sure you want to remove the employee's role?",
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
        console.log(e);
      }
    },
    async handleSubmit({ user }) {
      this.isLoading = true;

      try {
        await assignRoleToUser({
          user: { slug: user.slug, slug_type: user.slug_type },
          role: { slug: this.slug },
        });

        this.handleFetchData();
      } catch (e) {
        console.log(e);

        this.isLoading = false;
      }
    },
    init() {
      this.handleFetchData();
      this.fetchDictionaries();
    },
  },
  mounted() {
    this.init();
  },
};
</script>
