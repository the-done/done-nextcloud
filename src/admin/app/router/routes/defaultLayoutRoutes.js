/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import FileChart from "vue-material-design-icons/FileChart.vue";
import CashMultiple from "vue-material-design-icons/CashMultiple.vue";

import DefaultLayout from "@/admin/layouts/DefaultLayout.vue";

import SimpleRouterPage from "@/common/pages/SimpleRouterPage.vue";
import SectionNavigationPage from "@/admin/pages/SectionNavigationPage.vue";
import PlaceholderPage from "@/common/pages/PlaceholderPage.vue";
import Error404 from "@/common/pages/Error404.vue";

import UsersTablePage from "@/admin/pages/UsersTablePage.vue";
import UsersPreviewPage from "@/admin/pages/UsersPreviewPage.vue";
import UsersEditPage from "@/admin/pages/UsersEditPage.vue";
import UsersEditFormPage from "@/admin/pages/UsersEditFormPage.vue";
import UsersEditRolesPage from "@/admin/pages/UsersEditRolesPage.vue";
import UsersEditDirectionsPage from "@/admin/pages/UsersEditDirectionsPage.vue";
import UsersTimeTrackingPage from "@/admin/pages/UsersTimeTrackingPage.vue";

import ProjectsTablePage from "@/admin/pages/ProjectsTablePage.vue";
import ProjectsPreviewPage from "@/admin/pages/ProjectsPreviewPage.vue";
import ProjectsEditPage from "@/admin/pages/ProjectsEditPage.vue";
import ProjectsEditFormPage from "@/admin/pages/ProjectsEditFormPage.vue";
import ProjectsEditUsersPage from "@/admin/pages/ProjectsEditUsersPage.vue";

import SelfTimeTrackingPage from "@/common/pages/SelfTimeTrackingPage.vue";
import SelfTimeTrackingEditPage from "@/common/pages/SelfTimeTrackingEditPage.vue";

import ProfilePage from "@/admin/pages/ProfilePage.vue";

import TeamsTablePage from "@/admin/pages/TeamsTablePage.vue";
import TeamsPreviewPage from "@/admin/pages/TeamsPreviewPage.vue";
import TeamsEditPage from "@/admin/pages/TeamsEditPage.vue";
import TeamsEditFormPage from "@/admin/pages/TeamsEditFormPage.vue";
import TeamsEditDirectionsPage from "@/admin/pages/TeamsEditDirectionsPage.vue";
import TeamsEditProjectsPage from "@/admin/pages/TeamsEditProjectsPage.vue";
import TeamsEditUsersPage from "@/admin/pages/TeamsEditUsersPage.vue";

import ReportsCommonPage from "@/admin/pages/ReportsCommonPage.vue";
import ReportsProjectPage from "@/admin/pages/ReportsProjectPage.vue";
import ReportsStaffPage from "@/admin/pages/ReportsStaffPage.vue";

import FinancesPaymentsPage from "@/admin/pages/FinancesPaymentsPage.vue";
import FinancesPaymentEditPage from "@/admin/pages/FinancesPaymentEditPage.vue";

export const defaultLayoutRoutes = [
  {
    path: "/",
    component: DefaultLayout,
    children: [
      {
        path: "",
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
        name: "error-404",
        path: "404",
        component: Error404,
      },
      {
        name: "profile",
        path: "me",
        component: ProfilePage,
      },
      {
        name: "time-tracking-statistics",
        path: "statistics",
        component: SelfTimeTrackingPage,
      },
      {
        path: "reports",
        component: SimpleRouterPage,
        children: [
          {
            name: "report-home",
            path: "",
            component: SectionNavigationPage,
            props: {
              additionalProps: {
                navigationName: "reportsNavigation",
                breadcrumbs: [
                  {
                    path: { name: "report-home" },
                    title: "Reports",
                    icon: FileChart,
                  },
                ],
              },
            },
          },
          {
            name: "report-common",
            path: "common",
            component: ReportsCommonPage,
          },
          {
            name: "report-projects",
            path: "projects",
            component: ReportsProjectPage,
          },
          {
            name: "report-staff",
            path: "staff",
            component: ReportsStaffPage,
          },
        ],
      },
      {
        path: "finances",
        component: SimpleRouterPage,
        children: [
          {
            name: "finances-home",
            path: "",
            component: SectionNavigationPage,
            props: {
              additionalProps: {
                navigationName: "financesNavigation",
                breadcrumbs: [
                  {
                    path: { name: "finances-home" },
                    title: "Finances",
                    icon: CashMultiple,
                  },
                ],
              },
            },
          },
          {
            path: "payments",
            component: SimpleRouterPage,
            children: [
              {
                name: "finances-payments-table",
                path: "",
                component: FinancesPaymentsPage,
                meta: {
                  permissions: ["canReadFinances"],
                },
              },
              {
                name: "finances-payment-new",
                path: "payments/new",
                component: FinancesPaymentEditPage,
                meta: {
                  permissions: ["canReadFinances"],
                },
              },
              {
                name: "finances-payment-edit",
                path: "payments/:slug",
                component: FinancesPaymentEditPage,
                meta: {
                  permissions: ["canReadFinances"],
                },
              },
            ],
          },
          /* {
            path: "finances-contracts",
            component: SimpleRouterPage,
            children: [
              {
                name: "finances-contracts-table",
                path: "",
                component: PlaceholderPage,
              },
            ],
          }, */
        ],
      },
      {
        path: "staff",
        component: SimpleRouterPage,
        children: [
          {
            path: "",
            name: "staff-table",
            component: UsersTablePage,
            meta: {
              permissions: ["canReadUsersList"],
            },
          },
          {
            path: "new",
            component: UsersEditPage,
            children: [
              {
                path: "",
                name: "staff-new",
                component: UsersEditFormPage,
                meta: {
                  permissions: ["canCreateUsers"],
                },
              },
            ],
          },
          {
            path: ":slug",
            component: SimpleRouterPage,
            children: [
              {
                path: "",
                name: "staff-preview",
                component: UsersPreviewPage,
                meta: {
                  permissions: ["canReadUsersList"],
                },
              },
              {
                path: "edit",
                component: UsersEditPage,
                children: [
                  {
                    path: "",
                    name: "staff-edit",
                    component: UsersEditFormPage,
                    meta: {
                      permissions: ["canReadUsersProfile"],
                    },
                  },
                  {
                    path: "roles",
                    name: "staff-roles",
                    component: UsersEditRolesPage,
                    meta: {
                      permissions: ["canEditUsersGlobalRoles"],
                    },
                  },
                  {
                    path: "directions",
                    name: "staff-directions",
                    component: UsersEditDirectionsPage,
                    meta: {
                      permissions: ["canAddUsersToDirections"],
                    },
                  },
                ],
              },
            ],
          },
          {
            path: "statistics/:slug",
            name: "staff-statistics",
            component: UsersTimeTrackingPage,
            meta: {
              permissions: ["canReadStatisticsAllUsers"],
            },
          },
        ],
      },
      {
        path: "projects",
        component: SimpleRouterPage,
        children: [
          {
            path: "",
            name: "project-table",
            component: ProjectsTablePage,
            meta: {
              permissions: ["canReadProjectsList"],
            },
          },
          {
            path: "new",
            component: ProjectsEditPage,
            children: [
              {
                path: "",
                name: "project-new",
                component: ProjectsEditFormPage,
                meta: {
                  permissions: ["canCreateProjects"],
                },
              },
            ],
          },
          {
            path: ":slug",
            component: SimpleRouterPage,
            children: [
              {
                path: "",
                name: "project-preview",
                component: ProjectsPreviewPage,
                meta: {
                  permissions: ["canReadProjectsList"],
                },
              },
              {
                path: "edit",
                component: ProjectsEditPage,
                children: [
                  {
                    path: "",
                    name: "project-edit",
                    component: ProjectsEditFormPage,
                    meta: {
                      permissions: ["canEditProjects"],
                    },
                  },
                  {
                    path: "staff",
                    name: "project-staff",
                    component: ProjectsEditUsersPage,
                    meta: {
                      permissions: ["canAddUsersToProjects"],
                    },
                  },
                ],
              },
            ],
          },
        ],
      },
      {
        path: "teams",
        component: SimpleRouterPage,
        children: [
          {
            path: "",
            name: "team-table",
            component: TeamsTablePage,
            meta: {
              permissions: ["canReadTeamsList"],
            },
          },
          {
            path: "new",
            component: TeamsEditPage,
            children: [
              {
                path: "",
                name: "team-new",
                component: TeamsEditFormPage,
                meta: {
                  permissions: ["canCreateTeams"],
                },
              },
            ],
          },
          {
            path: ":slug",
            component: SimpleRouterPage,
            children: [
              {
                path: "",
                name: "team-preview",
                component: TeamsPreviewPage,
                meta: {
                  permissions: ["canReadTeamsList"],
                },
              },
              {
                path: "edit",
                component: TeamsEditPage,
                children: [
                  {
                    path: "",
                    name: "team-edit",
                    component: TeamsEditFormPage,
                    meta: {
                      permissions: ["canEditTeams"],
                    },
                  },
                  {
                    path: "directions",
                    name: "team-directions",
                    component: TeamsEditDirectionsPage,
                    meta: {
                      permissions: ["canAddTeamsToDirections"],
                    },
                  },
                  {
                    path: "projects",
                    name: "team-projects",
                    component: TeamsEditProjectsPage,
                    meta: {
                      permissions: ["canAddTeamsToProjects"],
                    },
                  },
                  {
                    path: "staff",
                    name: "team-staff",
                    component: TeamsEditUsersPage,
                    meta: {
                      permissions: ["canAddUsersToTeams"],
                    },
                  },
                ],
              },
            ],
          },
        ],
      },
    ],
  },
];
