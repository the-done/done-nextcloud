/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import FileChart from "vue-material-design-icons/FileChart.vue";
import CashMultiple from "vue-material-design-icons/CashMultiple.vue";
import PalmTree from "vue-material-design-icons/PalmTree.vue";
import CheckDecagram from "vue-material-design-icons/CheckDecagram.vue";
import Cog from "vue-material-design-icons/Cog.vue";

import DefaultLayout from "@/layouts/DefaultLayout.vue";

import SimpleRouterPage from "@/pages/SimpleRouterPage.vue";
import SectionNavigationPage from "@/pages/SectionNavigationPage.vue";
import PlaceholderPage from "@/pages/PlaceholderPage.vue";
import Error404 from "@/pages/Error404.vue";

import UsersTablePage from "@/pages/UsersTablePage.vue";
import UsersPreviewPage from "@/pages/UsersPreviewPage.vue";
import UsersEditPage from "@/pages/UsersEditPage.vue";
import UsersEditFormPage from "@/pages/UsersEditFormPage.vue";
import UsersEditRolesPage from "@/pages/UsersEditRolesPage.vue";
import UsersEditDirectionsPage from "@/pages/UsersEditDirectionsPage.vue";
import UsersTimeTrackingPage from "@/pages/UsersTimeTrackingPage.vue";

import ProjectsTablePage from "@/pages/ProjectsTablePage.vue";
import ProjectsPreviewPage from "@/pages/ProjectsPreviewPage.vue";
import ProjectsEditPage from "@/pages/ProjectsEditPage.vue";
import ProjectsEditFormPage from "@/pages/ProjectsEditFormPage.vue";
import ProjectsEditUsersPage from "@/pages/ProjectsEditUsersPage.vue";
import VacationsTablePage from "@/pages/VacationsTablePage.vue";
import VacationCardPage from "@/pages/VacationCardPage.vue";
import AgreementRequestsPage from "@/pages/AgreementRequestsPage.vue";

import SelfTimeTrackingPage from "@/pages/SelfTimeTrackingPage.vue";
import SelfTimeTrackingEditPage from "@/pages/SelfTimeTrackingEditPage.vue";

import ProfilePage from "@/pages/ProfilePage.vue";

import TeamsTablePage from "@/pages/TeamsTablePage.vue";
import TeamsPreviewPage from "@/pages/TeamsPreviewPage.vue";
import TeamsEditPage from "@/pages/TeamsEditPage.vue";
import TeamsEditFormPage from "@/pages/TeamsEditFormPage.vue";
import TeamsEditDirectionsPage from "@/pages/TeamsEditDirectionsPage.vue";
import TeamsEditProjectsPage from "@/pages/TeamsEditProjectsPage.vue";
import TeamsEditUsersPage from "@/pages/TeamsEditUsersPage.vue";

import ReportsCommonPage from "@/pages/ReportsCommonPage.vue";
import VacationsReportPage from "@/pages/VacationsReportPage.vue";
import ReportsProjectPage from "@/pages/ReportsProjectPage.vue";
import ReportsStaffPage from "@/pages/ReportsStaffPage.vue";

import FinancesPaymentTablePage from "@/pages/FinancesPaymentTablePage.vue";
import FinancesPaymentEditPage from "@/pages/FinancesPaymentEditPage.vue";

import FinancesContractTablePage from "@/pages/FinancesContractTablePage.vue";
import FinancesContractEditPage from "@/pages/FinancesContractEditPage.vue";
import FinancesContractEditFormPage from "@/pages/FinancesContractEditFormPage.vue";
import FinancesContractEditParameterPage from "@/pages/FinancesContractEditParameterPage.vue";

import FinancesContractParameterGroupTablePage from "@/pages/FinancesContractParameterGroupTablePage.vue";
import FinancesContractParameterGroupEditPage from "@/pages/FinancesContractParameterGroupEditPage.vue";

import FinancesContractParameterTablePage from "@/pages/FinancesContractParameterTablePage.vue";
import FinancesContractParameterEditPage from "@/pages/FinancesContractParameterEditPage.vue";

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
            meta: {
              permissions: { list: ["canReadCommonReport"], operator: "AND" },
            },
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
            meta: {
              permissions: { list: ["canReadStaffReport"], operator: "AND" },
            },
          },
          {
            name: "report-vacations",
            path: "vacations",
            component: VacationsReportPage,
            meta: {
              permissions: { list: ["canReadVacationsReport"], operator: "AND" },
              moduleName: "vacations",
            },
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
                name: "finances-payment-table",
                path: "",
                component: FinancesPaymentTablePage,
                meta: {
                  permissions: { list: ["canReadFinances"], operator: "AND" },
                },
              },
              {
                name: "finances-payment-new",
                path: "new",
                component: FinancesPaymentEditPage,
                meta: {
                  permissions: { list: ["canReadFinances"], operator: "AND" },
                },
              },
              {
                name: "finances-payment-edit",
                path: ":slug",
                component: FinancesPaymentEditPage,
                meta: {
                  permissions: { list: ["canReadFinances"], operator: "AND" },
                },
              },
            ],
          },
          {
            path: "contracts",
            component: SimpleRouterPage,
            children: [
              {
                path: "parameter-groups",
                component: SimpleRouterPage,
                meta: {
                  permissions: { list: ["canReadFinances"], operator: "AND" },
                },
                children: [
                  {
                    name: "finances-contract-parameter-group-table",
                    path: "",
                    component: FinancesContractParameterGroupTablePage,
                    meta: {
                      permissions: {
                        list: ["canReadFinances"],
                        operator: "AND",
                      },
                    },
                  },
                  {
                    name: "finances-contract-parameter-group-new",
                    path: "new",
                    component: FinancesContractParameterGroupEditPage,
                    meta: {
                      permissions: {
                        list: ["canReadFinances"],
                        operator: "AND",
                      },
                    },
                  },
                  {
                    name: "finances-contract-parameter-group-edit",
                    path: ":slug",
                    component: FinancesContractParameterGroupEditPage,
                    meta: {
                      permissions: {
                        list: ["canReadFinances"],
                        operator: "AND",
                      },
                    },
                  },
                ],
              },
              {
                name: "finances-contract-table",
                path: "",
                component: FinancesContractTablePage,
                meta: {
                  permissions: { list: ["canReadFinances"], operator: "AND" },
                },
              },
              {
                path: "new",
                component: FinancesContractEditPage,
                meta: {
                  permissions: { list: ["canReadFinances"], operator: "AND" },
                },
                children: [
                  {
                    name: "finances-contract-new",
                    path: "",
                    component: FinancesContractEditFormPage,
                    meta: {
                      permissions: {
                        list: ["canReadFinances"],
                        operator: "AND",
                      },
                    },
                  },
                ],
              },
              {
                path: ":slug",
                component: FinancesContractEditPage,
                meta: {
                  permissions: { list: ["canReadFinances"], operator: "AND" },
                },
                children: [
                  {
                    name: "finances-contract-edit",
                    path: "",
                    component: FinancesContractEditFormPage,
                    meta: {
                      permissions: {
                        list: ["canReadFinances"],
                        operator: "AND",
                      },
                    },
                  },
                  {
                    name: "finances-contract-parameter-value",
                    path: "parameters",
                    component: FinancesContractEditParameterPage,
                    meta: {
                      permissions: {
                        list: ["canReadFinances"],
                        operator: "AND",
                      },
                    },
                  },
                ],
              },
            ],
          },
          {
            path: "contract-parameters",
            component: SimpleRouterPage,
            children: [
              {
                name: "finances-contract-parameter-table",
                path: "",
                component: FinancesContractParameterTablePage,
                meta: {
                  permissions: { list: ["canReadFinances"], operator: "AND" },
                },
              },
              {
                name: "finances-contract-parameter-new",
                path: "new",
                component: FinancesContractParameterEditPage,
                meta: {
                  permissions: { list: ["canReadFinances"], operator: "AND" },
                },
              },
              {
                name: "finances-contract-parameter-edit",
                path: ":slug",
                component: FinancesContractParameterEditPage,
                meta: {
                  permissions: { list: ["canReadFinances"], operator: "AND" },
                },
              },
            ],
          },
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
              permissions: {
                list: ["canReadUsersList", "canViewEmployeesListRelated"],
                operator: "OR",
              },
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
                  permissions: { list: ["canCreateUsers"], operator: "AND" },
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
                  permissions: {
                    list: ["canReadUsersList", "canViewEmployeesListRelated"],
                    operator: "OR",
                  },
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
                      permissions: {
                        list: ["canReadUsersProfile"],
                        operator: "AND",
                      },
                    },
                  },
                  {
                    path: "roles",
                    name: "staff-roles",
                    component: UsersEditRolesPage,
                    meta: {
                      permissions: {
                        list: ["canEditUsersGlobalRoles"],
                        operator: "AND",
                      },
                    },
                  },
                  {
                    path: "directions",
                    name: "staff-directions",
                    component: UsersEditDirectionsPage,
                    meta: {
                      permissions: {
                        list: ["canAddUsersToDirections"],
                        operator: "AND",
                      },
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
              permissions: {
                list: ["canReadStatisticsAllUsers"],
                operator: "AND",
              },
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
              permissions: {
                list: ["canReadProjectsList", "canViewProjectsListRelated"],
                operator: "OR",
              },
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
                  permissions: { list: ["canCreateProjects"], operator: "AND" },
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
                  permissions: {
                    list: ["canReadProjectsList", "canViewProjectsListRelated"],
                    operator: "OR",
                  },
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
                      permissions: {
                        list: ["canEditProjects"],
                        operator: "AND",
                      },
                    },
                  },
                  {
                    path: "staff",
                    name: "project-staff",
                    component: ProjectsEditUsersPage,
                    meta: {
                      permissions: {
                        list: ["canAddUsersToProjects"],
                        operator: "AND",
                      },
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
              permissions: { list: ["canReadTeamsList"], operator: "AND" },
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
                  permissions: { list: ["canCreateTeams"], operator: "AND" },
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
                  permissions: { list: ["canReadTeamsList"], operator: "AND" },
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
                      permissions: { list: ["canEditTeams"], operator: "AND" },
                    },
                  },
                  {
                    path: "directions",
                    name: "team-directions",
                    component: TeamsEditDirectionsPage,
                    meta: {
                      permissions: {
                        list: ["canAddTeamsToDirections"],
                        operator: "AND",
                      },
                    },
                  },
                  {
                    path: "projects",
                    name: "team-projects",
                    component: TeamsEditProjectsPage,
                    meta: {
                      permissions: {
                        list: ["canAddTeamsToProjects"],
                        operator: "AND",
                      },
                    },
                  },
                  {
                    path: "staff",
                    name: "team-staff",
                    component: TeamsEditUsersPage,
                    meta: {
                      permissions: {
                        list: ["canAddUsersToTeams"],
                        operator: "AND",
                      },
                    },
                  },
                ],
              },
            ],
          },
        ],
      },
      {
        path: "vacations",
        component: SimpleRouterPage,
        children: [
          {
            name: "vacations-home",
            path: "",
            component: SectionNavigationPage,
            props: {
              additionalProps: {
                navigationName: "vacationsNavigation",
                breadcrumbs: [
                  {
                    path: { name: "vacations-home" },
                    title: "Vacations",
                    icon: PalmTree,
                  },
                ],
              },
            },
          },
          {
            name: "vacations-balance",
            path: "balance",
            component: VacationsTablePage,
            props: {
              additionalProps: {
                activeTab: "balance",
                navigationName: "vacationsNavigation",
                breadcrumbs: [
                  {
                    path: { name: "vacations-balance" },
                    title: "Vacations",
                    icon: PalmTree,
                  },
                  {
                    path: { name: "vacations-balance" },
                    title: "My balance",
                  },
                ],
              },
            },
          },
          {
            name: "vacations-requests",
            path: "requests",
            component: VacationsTablePage,
            props: {
              additionalProps: {
                activeTab: "requests",
                navigationName: "vacationsNavigation",
                breadcrumbs: [
                  {
                    path: { name: "vacations-balance" },
                    title: "Vacations",
                    icon: PalmTree,
                  },
                  {
                    path: { name: "vacations-requests" },
                    title: "Requests",
                  },
                ],
              },
            },
          },
          {
            name: "vacations-request-card",
            path: "requests/:slug",
            component: VacationCardPage,
            props: {
              additionalProps: {
                navigationName: "vacationsNavigation",
              },
            },
          },
          {
            name: "vacations-schedule",
            path: "schedule",
            component: VacationsTablePage,
            props: {
              additionalProps: {
                activeTab: "gantt",
                navigationName: "vacationsNavigation",
                breadcrumbs: [
                  {
                    path: { name: "vacations-balance" },
                    title: "Vacations",
                    icon: PalmTree,
                  },
                  {
                    path: { name: "vacations-schedule" },
                    title: "Schedule",
                  },
                ],
              },
            },
          },
        ],
      },
      {
        path: "agreement",
        component: SimpleRouterPage,
        children: [
          {
            name: "agreement-home",
            path: "",
            component: SectionNavigationPage,
            props: {
              additionalProps: {
                navigationName: "agreementNavigation",
                breadcrumbs: [
                  {
                    path: { name: "agreement-home" },
                    title: "Agreement",
                    icon: CheckDecagram,
                  },
                ],
              },
            },
          },
          {
            name: "agreement-requests",
            path: "requests",
            component: AgreementRequestsPage,
            props: {
              additionalProps: {
                breadcrumbs: [
                  {
                    path: { name: "agreement-requests" },
                    title: "Agreement",
                    icon: CheckDecagram,
                  },
                  {
                    path: { name: "agreement-requests" },
                    title: "Requests",
                  },
                ],
              },
            },
          },
        ],
      },
    ],
  },
];
