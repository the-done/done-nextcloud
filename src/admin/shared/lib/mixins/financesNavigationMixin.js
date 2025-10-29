/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { mapState } from "pinia";

import { usePermissionStore } from "@/admin/app/store/permission";
import { useModulesStore } from "@/admin/app/store/modules";

export const financesNavigationMixin = {
  computed: {
    ...mapState(usePermissionStore, ["getCommonPermission"]),
    ...mapState(useModulesStore, ["moduleExist"]),
    financesNavigation() {
      return [
        {
          key: "finances-payments",
          label: "Payments",
          to: {
            name: "finances-payments",
          },
          exact: false,
          visible:
            this.getCommonPermission("canReadFinances") === true &&
            this.moduleExist("finances") === true,
        },
      ];
    },
  },
};
