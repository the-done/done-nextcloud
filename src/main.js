/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import Vue from "vue";
import VueRouter from "vue-router";
import Vuelidate from "vuelidate";
import axios from "@nextcloud/axios";
import { createPinia } from "pinia";
import { generateUrl } from "@nextcloud/router";
import { t } from "@nextcloud/l10n";
import Notifications from "vue-notification";

import App from "./App.vue";

import { router } from "./app/router/router";
import { useDemoBannerStore } from "./app/store/demoBanner";

import { clickOutside } from "./shared/lib/directives/clickOutside";

const pinia = createPinia();

const baseURL = generateUrl("/apps/done/ajax");

axios.defaults.baseURL = baseURL;

// Dev/test hook to preview demo mode: append ?demoModules=vacations,agreement
// to any app URL and the selected modules are forwarded to the backend on every
// ajax request via the X-Done-Demo-Modules header. The value persists across
// in-app (SPA) navigation via sessionStorage, so it survives once the query
// param disappears from the URL; pass ?demoModules= (empty) or ?demoModules=off
// to clear it. The backend gates this to admin/director, so it only affects
// privileged users.
const DEMO_MODULES_KEY = "done:demoModules";

if (typeof window !== "undefined") {
  const urlDemo = new URLSearchParams(window.location.search).get(
    "demoModules",
  );

  if (urlDemo !== null) {
    const trimmed = urlDemo.trim();

    if (trimmed === "" || trimmed === "off") {
      sessionStorage.removeItem(DEMO_MODULES_KEY);
    } else {
      sessionStorage.setItem(DEMO_MODULES_KEY, trimmed);
    }
  }

  axios.interceptors.request.use((config) => {
    const demo = sessionStorage.getItem(DEMO_MODULES_KEY);

    if (demo) {
      config.headers = config.headers || {};
      config.headers["X-Done-Demo-Modules"] = demo;
    }

    return config;
  });
}

// In demo mode a write request is answered with a read-only marker
// ({ demo: true, readOnly: true, message }) and HTTP 200. Surface it as a
// centered banner explaining that the action needs the full version, and
// reject the promise so callers do not treat it as a successful write.
// handleRestErrors() swallows this rejection so no extra corner toast appears.
axios.interceptors.response.use((response) => {
  const payload = response?.data;

  if (payload && payload.demo === true && payload.readOnly === true) {
    useDemoBannerStore(pinia).showDemoNotice(
      t("done", payload.message || "Available in the full version"),
    );

    return Promise.reject(
      Object.assign(new Error("demo-read-only"), {
        demoReadOnly: true,
        response,
      }),
    );
  }

  return response;
});

Vue.use(VueRouter).use(pinia).use(Vuelidate).use(Notifications);

Vue.directive("click-outside", clickOutside);

new Vue({
  router,
  render: (h) => h(App),
  el: "#content",
});
