import VueRouter from "vue-router";
import { generateUrl } from "@nextcloud/router";

import { routes } from "./routes";

export const router = new VueRouter({
  mode: "history",
  base: generateUrl("apps/done"),
  routes,
});
