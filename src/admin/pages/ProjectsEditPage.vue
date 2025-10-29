/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('Projects', context)"
          :to="{ name: 'project-table' }"
          forceIconText
        >
          <template #icon>
            <BookCog />
          </template>
        </NcBreadcrumb>
        <NcBreadcrumb :name="pageTitle" :to="{ name: 'project-preview' }" />
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
import { NcBreadcrumbs, NcBreadcrumb, NcListItem } from "@nextcloud/vue";
import { mapState } from "pinia";

import BookCog from "vue-material-design-icons/BookCog.vue";
import Cog from "vue-material-design-icons/Cog.vue";
import Account from "vue-material-design-icons/Account.vue";

import { usePermissionStore } from "@/admin/app/store/permission";

import { fetchProjectPublicDataBySlug } from "@/common/entities/projects/api";

import {
  VPage,
  VPageContent,
  VPageLayout,
  VPageAsideNavigation,
} from "@/common/widgets/VPage";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "ProjectsEditPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcListItem,
    BookCog,
    Cog,
    Account,
    VPage,
    VPageLayout,
    VPageAsideNavigation,
    VPageContent,
    VToolbar,
  },
  data() {
    return {
      isLoading: false,
      project: null,
      contextualTranslations: {},
      context: "admin/projects",
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
        return this.contextTranslate("Create project", this.context);
      }

      if (this.isLoading === true) {
        return "";
      }

      if (this.project) {
        return this.project.name;
      }

      return String(this.slug);
    },
    navigation() {
      return [
        {
          key: "edit",
          label: this.contextTranslate("Main", this.context),
          to: { name: "project-edit", params: { slug: this.slug } },
          exact: true,
          icon: Cog,
          visible: this.getCommonPermission("canEditProjects") === true,
        },
        {
          key: "staff",
          label: this.contextTranslate("Employees", this.context),
          to: { name: "project-staff", params: { slug: this.slug } },
          exact: false,
          icon: Account,
          visible: this.getCommonPermission("canAddUsersToProjects") === true,
        },
      ];
    },
  },
  methods: {
    t,
    async handleFetchData() {
      this.isLoading = true;

      try {
        const { data } = await fetchProjectPublicDataBySlug({
          slug: this.slug,
        });

        this.project = data;
      } catch (e) {
        console.log(e);
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
  async mounted() {
    this.init();
  },
  watch: {
    $route() {
      if (this.project) {
        return;
      }

      this.init();
    },
  },
};
</script>

<style scoped>
.projects-edit-page-navigation {
  list-style: none;
  padding: 0;
  margin: 0;
}
</style>
