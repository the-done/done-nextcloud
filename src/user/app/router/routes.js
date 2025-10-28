import Error404 from "@/common/pages/Error404.vue";

import SimpleRouterPage from "@/common/pages/SimpleRouterPage.vue";

import SelfTimeTrackingPage from "@/common/pages/SelfTimeTrackingPage.vue";
import SelfTimeTrackingEditPage from "@/common/pages/SelfTimeTrackingEditPage.vue";

export const routes = [
  {
    path: "/",
    component: SimpleRouterPage,
    children: [
      {
        name: "time-tracking-new",
        path: "",
        component: SelfTimeTrackingEditPage,
      },
      {
        name: "time-tracking-edit",
        path: "time-tracking/:slug",
        component: SelfTimeTrackingEditPage,
      },
    ],
  },
  {
    name: "time-tracking-statistics",
    path: "/statistics",
    component: SelfTimeTrackingPage,
  },
  {
    path: "404",
    component: Error404,
  },
];
