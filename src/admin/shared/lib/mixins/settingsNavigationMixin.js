/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { mapState } from "pinia";

import AccountCog from "vue-material-design-icons/AccountCog.vue";
import AccountMultiple from "vue-material-design-icons/AccountMultiple.vue";
import Book from "vue-material-design-icons/Book.vue";
import AccountCash from "vue-material-design-icons/AccountCash.vue";
import DirectionsFork from "vue-material-design-icons/DirectionsFork.vue";
import Flag from "vue-material-design-icons/Flag.vue";

import { usePermissionStore } from "@/admin/app/store/permission";
import { useModulesStore } from "@/admin/app/store/modules";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export const settingsNavigationMixin = {
  mixins: [contextualTranslationsMixin],
  components: {},
  computed: {
    ...mapState(usePermissionStore, ["getCommonPermission"]),
    ...mapState(useModulesStore, ["moduleExist"]),
    employeeSettingsNavigation() {
      return [
        {
          key: "settings-staff",
          label: this.contextTranslate("Employees", this.context),
          to: {
            name: "settings-staff",
          },
          exact: true,
          icon: AccountMultiple,
          visible: this.getCommonPermission("canReadSettings") === true,
          children: [
            {
              key: "settings-staff-global-roles-table",
              label: "Employee roles",
              to: {
                name: "settings-staff-global-roles-table",
              },
              exact: false,
              visible: this.getCommonPermission("canReadSettings") === true,
            },
            {
              key: "settings-staff-positions-table",
              label: "Employee positions",
              to: {
                name: "settings-staff-positions-table",
              },
              exact: false,
              visible: this.getCommonPermission("canReadDictionaries") === true,
            },
            {
              key: "settings-staff-contract-types",
              label: "Contract type",
              to: {
                name: "settings-staff-contract-types-table",
              },
              exact: false,
              visible: this.getCommonPermission("canReadDictionaries") === true,
            },
            {
              key: "settings-staff-dynamic-fields",
              label: "Dynamic fields",
              to: {
                name: "settings-staff-dynamic-fields",
              },
              exact: false,
              visible: this.getCommonPermission("canReadRightsMatrix") === true,
            },
            {
              key: "settings-staff-global-role-permissions",
              label: "Rights matrix",
              to: {
                name: "settings-staff-global-role-permissions",
              },
              exact: false,
              visible: this.getCommonPermission("canReadRightsMatrix") === true,
            },
          ],
        },
      ];
    },
    projectSettingsNavigation() {
      return [
        {
          key: "settings-project",
          label: this.contextTranslate("Projects", this.context),
          to: {
            name: "settings-project",
          },
          exact: true,
          icon: Book,
          visible: this.getCommonPermission("canReadDictionaries") === true,
          children: [
            {
              key: "settings-project-roles-table",
              label: "Project roles",
              to: {
                name: "settings-project-roles-table",
              },
              exact: false,
              visible: this.getCommonPermission("canReadSettings") === true,
            },
            {
              key: "settings-project-stages-table",
              label: "Project stages",
              to: {
                name: "settings-project-stages-table",
              },
              exact: false,
              visible: this.getCommonPermission("canReadSettings") === true,
            },
            {
              key: "settings-project-dynamic-fields",
              label: "Dynamic fields",
              to: {
                name: "settings-project-dynamic-fields",
              },
              exact: false,
              // TODO: Need new permission on backend
              visible: this.getCommonPermission("canReadRightsMatrix") === true,
            },
            {
              key: "settings-project-global-role-permissions",
              label: "Rights matrix",
              to: {
                name: "settings-project-global-role-permissions",
              },
              exact: false,
              visible: this.getCommonPermission("canReadRightsMatrix") === true,
            },
          ],
        },
      ];
    },
    teamSettingsNavigation() {
      return [
        {
          key: "teams",
          label: this.contextTranslate("Teams", this.context),
          to: {
            name: "settings-teams",
          },
          exact: true,
          visible: this.getCommonPermission("canReadSettings") === true && this.moduleExist("teams") === true,
          icon: Flag,
          children: [
            {
              key: "settings-team-roles",
              label: "Team roles",
              to: {
                name: "settings-team-roles-table",
              },
              exact: false,
              visible:
                this.moduleExist("teams") === true &&
                this.getCommonPermission("canReadSettings") === true,
            },
          ],
        },
      ];
    },
    settingsNavigation() {
      return [
        {
          key: "settings-user",
          label: this.contextTranslate("User", this.context),
          to: {
            name: "settings-user",
          },
          exact: true,
          icon: AccountCog,
        },
        ...this.employeeSettingsNavigation,
        {
          key: "directions",
          label: this.contextTranslate("Directions", this.context),
          to: {
            name: "settings-directions-table",
          },
          exact: false,
          visible: this.getCommonPermission("canReadSettings") === true,
          icon: DirectionsFork,
        },
        ...this.projectSettingsNavigation,
        {
          key: "customers",
          label: this.contextTranslate("Customers", this.context),
          to: {
            name: "settings-customers-table",
          },
          exact: false,
          visible: this.getCommonPermission("canReadSettings") === true,
          icon: AccountCash,
        },
        ...this.teamSettingsNavigation,
      ];
    },
  },
};
