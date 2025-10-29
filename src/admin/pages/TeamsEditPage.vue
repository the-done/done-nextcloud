/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('Teams', context)"
          :to="{ name: 'team-table' }"
          forceIconText
        >
          <template #icon>
            <Flag />
          </template>
        </NcBreadcrumb>
        <NcBreadcrumb
          :name="pageTitle"
          :to="{ name: 'team-preview', params: { slug } }"
        />
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
import { t } from "@nextcloud/l10n";
import { mapState } from "pinia";

import Flag from "vue-material-design-icons/Flag.vue";
import Cog from "vue-material-design-icons/Cog.vue";
import DirectionsFork from "vue-material-design-icons/DirectionsFork.vue";
import BookCog from "vue-material-design-icons/BookCog.vue";
import Account from "vue-material-design-icons/Account.vue";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPageAsideNavigation,
} from "@/common/widgets/VPage";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";

import { usePermissionStore } from "@/admin/app/store/permission";

import { fetchTeamPublicDataBySlug } from "@/admin/entities/teams/api";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "TeamsEditPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcListItem,
    Flag,
    Cog,
    DirectionsFork,
    BookCog,
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
      team: null,
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
        return this.contextTranslate("Create team", this.context);
      }

      if (this.isLoading === true) {
        return "";
      }

      if (this.team) {
        return this.team.name;
      }

      return String(this.slug);
    },
    navigation() {
      return [
        {
          key: "edit",
          label: this.contextTranslate("Main", this.context),
          to: { name: "team-edit", params: { slug: this.slug } },
          exact: true,
          icon: Cog,
          visible: this.getCommonPermission("canEditTeams") === true,
        },
        {
          key: "staff",
          label: this.contextTranslate("Employees", this.context),
          to: { name: "team-staff", params: { slug: this.slug } },
          exact: false,
          icon: Account,
          visible: this.getCommonPermission("canAddUsersToTeams") === true,
        },
        {
          key: "directions",
          label: this.contextTranslate("Directions", this.context),
          to: { name: "team-directions", params: { slug: this.slug } },
          exact: false,
          icon: DirectionsFork,
          visible: this.getCommonPermission("canAddTeamsToDirections") === true,
        },
        {
          key: "projects",
          label: this.contextTranslate("Projects", this.context),
          to: { name: "team-projects", params: { slug: this.slug } },
          exact: false,
          icon: BookCog,
          visible: this.getCommonPermission("canAddTeamsToProjects") === true,
        },
      ];
    },
  },
  methods: {
    t,
    async handleFetchData() {
      this.isLoading = true;

      try {
        const { data } = await fetchTeamPublicDataBySlug({ slug: this.slug });

        this.team = data;
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
  mounted() {
    this.init();
  },
  watch: {
    $route() {
      if (this.team) {
        return;
      }

      this.init();
    },
  },
};
</script>
