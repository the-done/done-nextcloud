/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import AccountMultiple from "vue-material-design-icons/AccountMultiple.vue";
import DirectionsFork from "vue-material-design-icons/DirectionsFork.vue";
import Book from "vue-material-design-icons/Book.vue";
import AccountCash from "vue-material-design-icons/AccountCash.vue";
import Flag from "vue-material-design-icons/Flag.vue";
import CheckDecagram from "vue-material-design-icons/CheckDecagram.vue";
import PalmTree from "vue-material-design-icons/PalmTree.vue";
import ArrowUpBold from "vue-material-design-icons/ArrowUpBold.vue";

import SettingsLayout from "@/layouts/SettingsLayout.vue";

import AgreementSchemesPage from "@/pages/AgreementSchemesPage.vue";
import VacationsSettingsPage from "@/pages/VacationsSettingsPage.vue";
import ProVersionSettingsPage from "@/pages/ProVersionSettingsPage.vue";

import SimpleRouterPage from "@/pages/SimpleRouterPage.vue";
import SectionNavigationPage from "@/pages/SectionNavigationPage.vue";
/* import PlaceholderPage from "@/pages/PlaceholderPage.vue"; */

import GlobalRolesTablePage from "@/pages/GlobalRolesTablePage.vue";
import GlobalRolesPreviewPage from "@/pages/GlobalRolesPreviewPage.vue";

import GlobalRolePermissionsEditPage from "@/pages/GlobalRolePermissionsEditPage.vue";

import DictionaryTablePage from "@/pages/DictionaryTablePage.vue";
import DictionaryEditPage from "@/pages/DictionaryEditPage.vue";

import DynamicFieldsSourcePage from "@/pages/DynamicFieldsSourcePage.vue";

import UserSettingsPage from "@/pages/UserSettingsPage.vue";

import { MAP_ENTITY_SOURCE } from "@/shared/lib/constants/entity";

class DictionaryRoute {
  constructor({ name, path, dictionaryTitle, breadcrumbs }) {
    this.path = path;
    this.component = SimpleRouterPage;
    this.meta = {
      permissions: { list: ["canReadDictionaries"], operator: "AND" },
    };

    this.props = {
      additionalProps: {
        dictionaryTitle,
        breadcrumbs,
      },
    };

    const parentTableName = `${name}-table`;

    this.children = [
      {
        path: "",
        name: parentTableName,
        component: DictionaryTablePage,
      },
      {
        path: "new",
        name: `${name}-new`,
        component: DictionaryEditPage,
        meta: {
          parentTableName,
        },
      },
      {
        path: ":slug",
        name: `${name}-edit`,
        component: DictionaryEditPage,
        meta: {
          parentTableName,
        },
      },
    ];
  }
}

export const settingsLayoutRoutes = [
  {
    path: "/settings",
    component: SettingsLayout,
    children: [
      {
        path: "",
        component: SimpleRouterPage,
        children: [
          {
            name: "settings-user",
            path: "",
            component: UserSettingsPage,
          },
        ],
      },
      {
        path: "staff",
        component: SimpleRouterPage,
        children: [
          {
            name: "settings-staff",
            path: "",
            component: SectionNavigationPage,
            props: {
              additionalProps: {
                navigationName: "employeeSettingsNavigation",
                breadcrumbs: [
                  {
                    path: { name: "settings-staff" },
                    title: "Employees",
                    icon: AccountMultiple,
                  },
                ],
              },
            },
          },
          {
            path: "settings-staff-global-roles",
            component: SimpleRouterPage,
            children: [
              {
                name: "settings-staff-global-roles-table",
                path: "",
                component: GlobalRolesTablePage,
              },
              {
                name: "settings-staff-global-roles-edit",
                path: ":slug",
                component: GlobalRolesPreviewPage,
              },
            ],
          },
          new DictionaryRoute({
            name: "settings-staff-positions",
            path: "positions",
            dictionaryTitle: "positionsDictionary",
            breadcrumbs: [
              {
                path: { name: "settings-staff" },
                title: "Employees",
                icon: AccountMultiple,
              },
              {
                title: "Employee positions",
                path: { name: "settings-staff-positions-table" },
              },
            ],
          }),
          new DictionaryRoute({
            name: "settings-staff-contract-types",
            path: "contract-types",
            dictionaryTitle: "contractTypesDictionary",
            breadcrumbs: [
              {
                path: { name: "settings-staff" },
                title: "Employees",
                icon: AccountMultiple,
              },
              {
                title: "Contract types",
                path: { name: "settings-staff-contract-types-table" },
              },
            ],
          }),
          {
            name: "settings-staff-dynamic-fields",
            path: "dynamic-fields",
            component: DynamicFieldsSourcePage,
            meta: {
              // TODO: Need new permission on backend
              permissions: { list: ["canReadRightsMatrix"], operator: "AND" },
            },
            props: {
              additionalProps: {
                source: MAP_ENTITY_SOURCE["user"],
                breadcrumbs: [
                  {
                    path: { name: "settings-staff" },
                    title: "Employees",
                    icon: AccountMultiple,
                  },
                  {
                    title: "Dynamic fields",
                  },
                ],
              },
            },
          },
          {
            name: "settings-staff-global-role-permissions",
            path: "global-role-permissions",
            component: GlobalRolePermissionsEditPage,
            meta: {
              permissions: { list: ["canReadRightsMatrix"], operator: "AND" },
            },
            props: {
              additionalProps: {
                source: MAP_ENTITY_SOURCE["user"],
                breadcrumbs: [
                  {
                    path: { name: "settings-staff" },
                    title: "Employees",
                    icon: AccountMultiple,
                  },
                  {
                    title: "Rights matrix",
                  },
                ],
              },
            },
          },
        ],
      },
      new DictionaryRoute({
        name: "settings-directions",
        path: "directions",
        dictionaryTitle: "directionsDictionary",
        breadcrumbs: [
          {
            title: "Directions",
            path: { name: "settings-directions-table" },
            icon: DirectionsFork,
          },
        ],
      }),
      {
        path: "projects",
        component: SimpleRouterPage,
        children: [
          {
            name: "settings-project",
            path: "",
            component: SectionNavigationPage,
            props: {
              additionalProps: {
                navigationName: "projectSettingsNavigation",
                breadcrumbs: [
                  {
                    path: { name: "settings-project" },
                    title: "Projects",
                    icon: Book,
                  },
                ],
              },
            },
          },
          new DictionaryRoute({
            name: "settings-project-roles",
            path: "roles",
            dictionaryTitle: "rolesDictionary",
            breadcrumbs: [
              {
                path: { name: "settings-project" },
                title: "Projects",
                icon: Book,
              },
              {
                title: "Project roles",
                path: { name: "settings-project-roles-table" },
              },
            ],
          }),
          new DictionaryRoute({
            name: "settings-project-stages",
            path: "stages",
            dictionaryTitle: "projectStagesDictionary",
            breadcrumbs: [
              {
                path: { name: "settings-project" },
                title: "Projects",
                icon: Book,
              },
              {
                title: "Project stages",
                path: { name: "settings-project-stages-table" },
              },
            ],
          }),
          {
            name: "settings-project-dynamic-fields",
            path: "dynamic-fields",
            component: DynamicFieldsSourcePage,
            meta: {
              // TODO: Need new permission on backend
              permissions: { list: ["canReadRightsMatrix"], operator: "AND" },
            },
            props: {
              additionalProps: {
                source: MAP_ENTITY_SOURCE["project"],
                breadcrumbs: [
                  {
                    path: { name: "settings-project" },
                    title: "Projects",
                    icon: Book,
                  },
                  {
                    title: "Dynamic fields",
                  },
                ],
              },
            },
          },
          {
            name: "settings-project-global-role-permissions",
            path: "global-role-permissions",
            component: GlobalRolePermissionsEditPage,
            meta: {
              permissions: { list: ["canReadRightsMatrix"], operator: "AND" },
            },
            props: {
              additionalProps: {
                source: MAP_ENTITY_SOURCE["project"],
                breadcrumbs: [
                  {
                    path: { name: "settings-project" },
                    title: "Projects",
                    icon: Book,
                  },
                  {
                    title: "Rights matrix",
                  },
                ],
              },
            },
          },
        ],
      },
      new DictionaryRoute({
        name: "settings-customers",
        path: "customers",
        dictionaryTitle: "customersDictionary",
        breadcrumbs: [
          {
            title: "Customers",
            path: { name: "settings-customers-table" },
            icon: AccountCash,
          },
        ],
      }),
      {
        path: "teams",
        component: SimpleRouterPage,
        children: [
          {
            name: "settings-teams",
            path: "",
            component: SectionNavigationPage,
            props: {
              additionalProps: {
                navigationName: "teamSettingsNavigation",
                breadcrumbs: [
                  {
                    path: { name: "settings-teams" },
                    title: "Teams",
                    icon: Flag,
                  },
                ],
              },
            },
          },
          new DictionaryRoute({
            name: "settings-team-roles",
            path: "roles",
            dictionaryTitle: "rolesInTeamDictionary",
            breadcrumbs: [
              {
                path: { name: "settings-teams" },
                title: "Teams",
                icon: Flag,
              },
              {
                title: "Team roles",
                path: { name: "settings-team-roles-table" },
              },
            ],
          }),
        ],
      },
      {
        path: "agreement",
        component: SimpleRouterPage,
        children: [
          {
            name: "settings-agreement",
            path: "",
            component: SectionNavigationPage,
            props: {
              additionalProps: {
                navigationName: "agreementSettingsNavigation",
                breadcrumbs: [
                  {
                    path: { name: "settings-agreement" },
                    title: "Agreement",
                    icon: CheckDecagram,
                  },
                ],
              },
            },
          },
          {
            name: "settings-agreement-schemes",
            path: "schemes",
            component: AgreementSchemesPage,
            props: {
              additionalProps: {
                breadcrumbs: [
                  {
                    path: { name: "settings-agreement" },
                    title: "Agreement",
                    icon: CheckDecagram,
                  },
                  {
                    title: "Schemes",
                    path: { name: "settings-agreement-schemes" },
                  },
                ],
              },
            },
          },
        ],
      },
      {
        path: "vacations",
        component: SimpleRouterPage,
        children: [
          {
            name: "settings-vacations",
            path: "",
            component: VacationsSettingsPage,
            props: {
              additionalProps: {
                breadcrumbs: [
                  {
                    path: { name: "settings-vacations" },
                    title: "Vacations",
                    icon: PalmTree,
                  },
                ],
              },
            },
          },
        ],
      },
      {
        path: "pro",
        component: SimpleRouterPage,
        children: [
          {
            name: "settings-pro",
            path: "",
            component: ProVersionSettingsPage,
            // Admin-only screen: non-admins are sent to the 404 view by the
            // router guard (the backend also returns 404 for this URL).
            meta: { requiresAdmin: true },
            props: {
              additionalProps: {
                breadcrumbs: [
                  {
                    path: { name: "settings-pro" },
                    title: "Pro version",
                    icon: ArrowUpBold,
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
