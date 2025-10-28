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
            <Account />
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
import { NcBreadcrumbs, NcBreadcrumb, NcListItem } from "@nextcloud/vue";
import { mapState } from "pinia";

import Account from "vue-material-design-icons/Account.vue";
import Cog from "vue-material-design-icons/Cog.vue";
import ShieldAccount from "vue-material-design-icons/ShieldAccount.vue";
import DirectionsFork from "vue-material-design-icons/DirectionsFork.vue";

import {
  VPage,
  VPageLayout,
  VPageAsideNavigation,
} from "@/common/widgets/VPage";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";

import { usePermissionStore } from "@/admin/app/store/permission";

import { fetchUserPublicDataBySlug } from "@/common/entities/users/api";

import { getJoinString } from "@/common/shared/lib/helpers";

import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "DictionaryPositionsPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcListItem,
    Account,
    Cog,
    ShieldAccount,
    DirectionsFork,
    VPage,
    VPageLayout,
    VPageAsideNavigation,
    VToolbar,
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
    t,
    async handleFetchData() {
      this.isLoading = true;

      try {
        const { data } = await fetchUserPublicDataBySlug({ slug: this.slug });

        this.user = data;
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
      if (this.user) {
        return;
      }

      this.init();
    },
  },
};
</script>
