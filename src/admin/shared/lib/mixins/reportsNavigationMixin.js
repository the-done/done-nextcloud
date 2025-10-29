/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { mapState } from "pinia";

import { usePermissionStore } from "@/admin/app/store/permission";
import { useModulesStore } from "@/admin/app/store/modules";

export const reportsNavigationMixin = {
  computed: {
    ...mapState(usePermissionStore, ["getCommonPermission"]),
    ...mapState(useModulesStore, ["moduleExist"]),
    reportsNavigation() {
      return [
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
      ];
    },
  },
};
