<template>
  <section class="v-app-layout">
    <AppNavigation :items="navigation">
      <template #footer>
        <NcButton :to="{ name: 'settings-user' }" alignment="start" wide>
          <template #icon>
            <Cog />
          </template>
          {{ contextTranslate("Settings", context) }}
        </NcButton>
      </template>
    </AppNavigation>
    <NcAppContent>
      <router-view />
    </NcAppContent>
  </section>
</template>

<script>
import { NcAppContent, NcButton } from "@nextcloud/vue";
import { mapState } from "pinia";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { usePermissionStore } from "@/admin/app/store/permission";
import { useModulesStore } from "@/admin/app/store/modules";

import Plus from "vue-material-design-icons/Plus.vue";
import ClockTimeEight from "vue-material-design-icons/ClockTimeEight.vue";
import AccountMultiple from "vue-material-design-icons/AccountMultiple.vue";
import Book from "vue-material-design-icons/Book.vue";
import Flag from "vue-material-design-icons/Flag.vue";
import FileChart from "vue-material-design-icons/FileChart.vue";
import Cash from "vue-material-design-icons/Cash.vue";
import Cog from "vue-material-design-icons/Cog.vue";

import AppNavigation from "@/common/widgets/AppNavigation/AppNavigation.vue";

import { reportsNavigationMixin } from "@/admin/shared/lib/mixins/reportsNavigationMixin";
import { financesNavigationMixin } from "@/admin/shared/lib/mixins/financesNavigationMixin";

export default {
  name: "DefaultLayout",
  mixins: [
    reportsNavigationMixin,
    financesNavigationMixin,
    contextualTranslationsMixin,
  ],
  components: {
    NcAppContent,
    NcButton,
    AppNavigation,
    Cog,
  },
  computed: {
    ...mapState(usePermissionStore, ["getCommonPermission"]),
    ...mapState(useModulesStore, ["moduleExist"]),
    navigation() {
      return [
        {
          key: "time-tracking-new",
          label: this.contextTranslate("Add record", this.context),
          icon: Plus,
          to: {
            name: "time-tracking-new",
          },
          exact: true,
        },
        {
          key: "time-tracking-statistics",
          label: this.contextTranslate("Statistics", this.context),
          icon: ClockTimeEight,
          to: {
            name: "time-tracking-statistics",
          },
          exact: true,
        },
        {
          key: "reports",
          label: this.contextTranslate("Reports", this.context),
          icon: FileChart,
          to: {
            name: "report-home",
          },
          exact: true,
          open: true,
          visible:
            this.getCommonPermission("canReadReports") === true &&
            this.moduleExist("reports") === true,
          children: this.reportsNavigation,
        },
        {
          key: "finances",
          label: this.contextTranslate("Finances", this.context),
          icon: Cash,
          to: {
            name: "finances-home",
          },
          exact: true,
          open: true,
          visible:
            this.getCommonPermission("canReadFinances") === true &&
            this.moduleExist("finances") === true,
          children: this.financesNavigation,
        },
        {
          key: "staff",
          label: this.contextTranslate("Employees", this.context),
          to: {
            name: "staff-table",
          },
          exact: false,
          icon: AccountMultiple,
          visible:
            this.getCommonPermission("canReadUsersList", this.context) === true,
        },
        {
          key: "projects",
          label: this.contextTranslate("Projects", this.context),
          to: {
            name: "project-table",
          },
          exact: false,
          icon: Book,
          visible: this.getCommonPermission("canReadProjectsList") === true,
        },
        {
          key: "teams",
          label: this.contextTranslate("Teams", this.context),
          to: {
            name: "team-table",
          },
          exact: false,
          icon: Flag,
          visible:
            this.getCommonPermission("canReadTeamsList") === true &&
            this.moduleExist("teams") === true,
        },
      ];
    },
  },
  methods: {
    t,
  },
};
</script>
