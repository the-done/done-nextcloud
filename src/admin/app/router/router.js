import VueRouter from "vue-router";
import { generateUrl } from "@nextcloud/router";
import { emit } from "@nextcloud/event-bus";

import { usePermissionStore } from "@/admin/app/store/permission";
import { useModulesStore } from "@/admin/app/store/modules";
import { fetchUserPermissions } from "@/common/entities/users/api";
import { fetchAvailableModules } from "@/common/entities/users/api";

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
    const { data: permissionsData } = await fetchUserPermissions();

    permissionStore.list = { ...permissionsData };
    permissionStore.isFetched = true;
  }

  if (meta.permissions && meta.permissions.length > 0) {
    const result = meta.permissions.reduce((accum, key) => {
      return [...accum, permissionStore.getCommonPermission(key)];
    }, []);

    const isAllowed = result.every((value) => value === true);

    if (isAllowed === false) {
      goNext({ name: "error-404" });

      return;
    }

    goNext();

    return;
  }

  goNext();
});
