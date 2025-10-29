/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import Vue from "vue";
import VueRouter from "vue-router";
import Vuelidate from "vuelidate";
import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";
import Notifications from "vue-notification";

import App from "./App.vue";

import { router } from "./app/router/router";

const baseURL = generateUrl("/apps/done/ajax");

axios.defaults.baseURL = baseURL;

Vue.use(VueRouter).use(Vuelidate).use(Notifications);

new Vue({
  router,
  render: (h) => h(App),
  el: "#content",
});
