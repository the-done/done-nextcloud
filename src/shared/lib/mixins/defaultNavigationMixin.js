/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { mapState } from "pinia";

import Plus from "vue-material-design-icons/Plus.vue";
import Account from "vue-material-design-icons/Account.vue";
import ClockTimeEight from "vue-material-design-icons/ClockTimeEight.vue";
import FileChart from "vue-material-design-icons/FileChart.vue";
import CircleMultiple from "vue-material-design-icons/CircleMultiple.vue";
import AccountMultiple from "vue-material-design-icons/AccountMultiple.vue";
import Book from "vue-material-design-icons/Book.vue";
import Flag from "vue-material-design-icons/Flag.vue";

import { usePermissionStore } from "@/app/store/permission";
import { useModulesStore } from "@/app/store/modules";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export const defaultNavigationMixin = {
  mixins: [contextualTranslationsMixin],
  components: {},
  computed: {
    ...mapState(usePermissionStore, ["getCommonPermission"]),
    ...mapState(useModulesStore, ["moduleExist"]),
    reportsNavigation() {
      return [
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
          children: [
            {
              key: "report-common",
              label: "Common",
              to: {
                name: "report-common",
              },
              exact: false,
              visible:
                this.getCommonPermission("canReadCommonReport") === true &&
                this.moduleExist("reports") === true,
            },
            {
              key: "report-projects",
              label: "By projects",
              to: {
                name: "report-projects",
              },
              exact: false,
              visible:
                this.getCommonPermission("canReadProjectsReport") === true &&
                this.moduleExist("reports") === true,
            },
            {
              key: "report-staff",
              label: "By employees",
              to: {
                name: "report-staff",
              },
              exact: false,
              visible:
                this.getCommonPermission("canReadStaffReport") === true &&
                this.moduleExist("reports") === true,
            },
          ],
        },
      ];
    },
    financesNavigation() {
      return [
        {
          key: "finances",
          label: this.contextTranslate("Finances", this.context),
          to: {
            name: "finances-home",
          },
          exact: true,
          open: true,
          icon: CircleMultiple,
          visible:
            this.getCommonPermission("canReadFinances") === true &&
            this.moduleExist("finances") === true,
          children: [
            {
              key: "finances-payment-table",
              label: this.contextTranslate("Payments", this.context),
              to: {
                name: "finances-payment-table",
              },
              exact: false,
              visible:
                this.getCommonPermission("canReadFinances") === true &&
                this.moduleExist("finances") === true,
            },
            {
              key: "finances-contract-table",
              label: this.contextTranslate("Contracts", this.context),
              to: {
                name: "finances-contract-table",
              },
              exact: false,
              visible:
                this.getCommonPermission("canReadFinances") === true &&
                this.moduleExist("finances") === true,
            },
            {
              key: "finances-contract-parameter-table",
              label: this.contextTranslate("Contract parameters", this.context),
              to: {
                name: "finances-contract-parameter-table",
              },
              exact: false,
              visible:
                this.getCommonPermission("canReadFinances") === true &&
                this.moduleExist("finances") === true,
            },
          ],
        },
      ];
    },
    defaultNavigation() {
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
          key: "profile",
          label: this.contextTranslate("Profile", this.context),
          icon: Account,
          to: {
            name: "profile",
          },
          exact: true,
        },
        ...this.reportsNavigation,
        ...this.financesNavigation,
        {
          key: "staff",
          label: this.contextTranslate("Employees", this.context),
          to: {
            name: "staff-table",
          },
          exact: false,
          icon: AccountMultiple,
          visible:
            this.getCommonPermission("canReadUsersList", this.context) === true ||
              this.getCommonPermission("canViewEmployeesListRelated", this.context) === true,
        },
        {
          key: "projects",
          label: this.contextTranslate("Projects", this.context),
          to: {
            name: "project-table",
          },
          exact: false,
          icon: Book,
          visible: this.getCommonPermission("canReadProjectsList") === true ||
              this.getCommonPermission("canViewProjectsListRelated") === true,
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
};
