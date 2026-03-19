/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage>
    <VToolbar>
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
        <NcBreadcrumb :name="pageTitle" :to="{ name: 'staff-preview' }" />
        <NcBreadcrumb
          v-if="isLoading === false && isEdit === true"
          :name="contextTranslate('Edit', context)"
        />
      </NcBreadcrumbs>
    </VToolbar>
    <VPageLayout>
      <VPageAsideNavigation v-if="isEdit === true" :items="navigation" />
      <RouterView />
    </VPageLayout>
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb } from "@nextcloud/vue";
import { mapState } from "pinia";

import AccountMultiple from "vue-material-design-icons/AccountMultiple.vue";
import Cog from "vue-material-design-icons/Cog.vue";
import ShieldAccount from "vue-material-design-icons/ShieldAccount.vue";
import DirectionsFork from "vue-material-design-icons/DirectionsFork.vue";

import { VPage, VPageLayout, VPageAsideNavigation } from "@/widgets";
import { VToolbar, VTabs } from "@/shared/components";

import { usePermissionStore } from "@/app/store/permission";

import { fetchUserPublicDataBySlug } from "@/entities/users/api";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { getJoinString } from "@/shared/lib/helpers/string";
import { redirectNotFoundPage } from "@/shared/lib/helpers/navigation";

export default {
  name: "DictionaryPositionsPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    AccountMultiple,
    Cog,
    ShieldAccount,
    DirectionsFork,
    VPage,
    VPageLayout,
    VPageAsideNavigation,
    VToolbar,
    VTabs,
  },
  data() {
    return {
      isLoading: false,
      user: null,
    };
  },
  computed: {
    ...mapState(usePermissionStore, ["getCommonPermission"]),
    slug() {
      return this.$route.params.slug;
    },
    isEdit() {
      return Boolean(this.slug);
    },
    pageTitle() {
      if (this.isEdit === false) {
        return this.contextTranslate("Create employee", this.context);
      }

      if (this.isLoading === true) {
        return "";
      }

      if (this.user) {
        const { lastname, name, middle_name, pname } = this.user;
        const fullName = getJoinString([lastname, name, middle_name]);
        const position = pname?.toLowerCase();

        if (position) {
          return `${fullName}, ${position}`;
        }

        return fullName;
      }

      return String(this.slug);
    },
    navigation() {
      return [
        {
          key: "edit",
          label: this.contextTranslate("Main", this.context),
          to: { name: "staff-edit", params: { slug: this.slug } },
          exact: true,
          icon: Cog,
          visible: this.getCommonPermission("canReadUsersProfile") === true,
        },
        {
          key: "roles",
          label: this.contextTranslate("Roles", this.context),
          to: { name: "staff-roles", params: { slug: this.slug } },
          exact: false,
          icon: ShieldAccount,
          visible: this.getCommonPermission("canEditUsersGlobalRoles") === true,
        },
        {
          key: "directions",
          label: this.contextTranslate("Directions", this.context),
          to: { name: "staff-directions", params: { slug: this.slug } },
          exact: false,
          icon: DirectionsFork,
          visible: this.getCommonPermission("canAddUsersToDirections") === true,
        },
      ];
    },
  },
  methods: {

    async handleFetchData() {
      this.isLoading = true;

      try {
        const { data } = await fetchUserPublicDataBySlug({ slug: this.slug });

        this.user = data;
      } catch (e) {
        console.error(e);

        redirectNotFoundPage(this.$router);
      } finally {
        this.isLoading = false;
      }
    },
    init() {
      if (this.isEdit === false) {
        return;
      }

      this.handleFetchData();
    },
  },
  mounted() {
    this.init();
  },
  watch: {
    $route() {
      if (this.user) {
        return;
      }

      this.init();
    },
  },
};
</script>
