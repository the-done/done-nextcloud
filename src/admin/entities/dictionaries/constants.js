export const dictionariesNavigation = (_this) => [
  {
    key: "dictionaries-global-roles",
    label: "Employee roles",
    to: {
      name: "dictionary-global-roles-table",
    },
    exact: false,
    visible: true,
  },
  {
    key: "dictionaries-positions",
    label: "Employee positions",
    to: {
      name: "dictionary-positions-table",
    },
    exact: false,
    visible: true,
  },
  {
    key: "dictionaries-roles",
    label: "Project roles",
    to: {
      name: "dictionary-roles-table",
    },
    exact: false,
    visible: true,
  },
  {
    key: "dictionaries-team-roles",
    label: "Team roles",
    to: {
      name: "dictionary-team-roles-table",
    },
    exact: false,
    visible: _this.moduleExist("teams") === true
  },
  {
    key: "dictionaries-contract-types",
    label: "Contract type",
    to: {
      name: "dictionary-contract-types-table",
    },
    exact: false,
    visible: true,
  },
  {
    key: "dictionaries-directions",
    label: "Directions",
    to: {
      name: "dictionary-directions-table",
    },
    exact: false,
    visible: true,
  },
  {
    key: "dictionaries-project-stages",
    label: "Project stages",
    to: {
      name: "dictionary-project-stages-table",
    },
    exact: false,
    visible: true,
  },
  {
    key: "dictionaries-customers",
    label: "Customers",
    to: {
      name: "dictionary-customers-table",
    },
    exact: false,
    visible: true,
  },
];
