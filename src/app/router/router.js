/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import VueRouter from "vue-router";
import { generateUrl } from "@nextcloud/router";
import { emit } from "@nextcloud/event-bus";

import { usePermissionStore } from "@/app/store/permission";
import { useModulesStore } from "@/app/store/modules";
import { fetchUserPermissions } from "@/entities/users/api";
import { fetchAvailableModules } from "@/entities/users/api";

import { routes } from "./routes";

export const router = new VueRouter({
  mode: "history",
  base: generateUrl("apps/done"),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const { meta } = to;

  const permissionStore = usePermissionStore();
  const modulesStore = useModulesStore();

  const goNext = (params) => {
    const width = window.innerWidth;

    if (width <= 767) {
      emit("toggle-navigation", {
        open: false,
      });
    }

    next(params);
  };

  if (modulesStore.isFetched === false) {
    const { data: modulesData } = await fetchAvailableModules();

    modulesStore.list = modulesData;
    modulesStore.isFetched = true;
  }

  if (permissionStore.isFetched === false) {
    const { common, fields, isOfficer, isFinance, isApprover, currentUserId } = await fetchUserPermissions();

    permissionStore.list = { common, fields };
    permissionStore.isOfficer = isOfficer;
    permissionStore.isFinance = isFinance;
    permissionStore.isApprover = isApprover;
    permissionStore.currentUserId = currentUserId;
    permissionStore.isFetched = true;
  }

  if (meta.permissions && meta.permissions.list.length > 0) {
    const operator = meta.permissions?.operator ?? "AND";
    const result = meta.permissions.list.reduce((accum, key) => {
      return [...accum, permissionStore.getCommonPermission(key)];
    }, []);

    const isAllowed = operator === "AND" ?
        result.every((value) => value === true) :
        result.some((value) => value === true);

    if (isAllowed === false) {

      goNext({ name: "error-404" });

      return;
    }

    goNext();

    return;
  }

  goNext();
});
