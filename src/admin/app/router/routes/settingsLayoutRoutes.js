import AccountMultiple from "vue-material-design-icons/AccountMultiple.vue";
import DirectionsFork from "vue-material-design-icons/DirectionsFork.vue";
import Book from "vue-material-design-icons/Book.vue";
import AccountCash from "vue-material-design-icons/AccountCash.vue";
import Flag from "vue-material-design-icons/Flag.vue";

import SettingsLayout from "@/admin/layouts/SettingsLayout.vue";

import SimpleRouterPage from "@/common/pages/SimpleRouterPage.vue";
import SectionNavigationPage from "@/admin/pages/SectionNavigationPage.vue";
/* import PlaceholderPage from "@/common/pages/PlaceholderPage.vue"; */

import GlobalRolesTablePage from "@/admin/pages/GlobalRolesTablePage.vue";
import GlobalRolesPreviewPage from "@/admin/pages/GlobalRolesPreviewPage.vue";

import GlobalRolePermissionsEditPage from "@/admin/pages/GlobalRolePermissionsEditPage.vue";

import DictionaryTablePage from "@/admin/pages/DictionaryTablePage.vue";
import DictionaryEditPage from "@/admin/pages/DictionaryEditPage.vue";

import DynamicFieldsSourcePage from "@/admin/pages/DynamicFieldsSourcePage.vue";

import UserSettingsPage from "@/admin/pages/UserSettingsPage.vue";

import { MAP_ENTITY_SOURCE } from "@/common/shared/lib/constants";

class DictionaryRoute {
  constructor({ name, path, dictionaryTitle, breadcrumbs }) {
    this.path = path;
    this.component = SimpleRouterPage;
    this.meta = {
      permissions: ["canReadDictionaries"],
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
              permissions: ["canReadRightsMatrix"],
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
              permissions: ["canReadRightsMatrix"],
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
              permissions: ["canReadRightsMatrix"],
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
              permissions: ["canReadRightsMatrix"],
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
    ],
  },
];
