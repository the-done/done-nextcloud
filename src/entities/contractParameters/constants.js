export const CONTRACT_PARAMETER_TYPES = {
  number: 1,
  percent: 2,
  formula: 3,
};

export const CONTRACT_PARAMETER_TYPE_OPTIONS = [
  {
    key: "number",
    label: "Number",
    value: CONTRACT_PARAMETER_TYPES["number"],
  },
  {
    key: "percent",
    label: "Percent",
    value: CONTRACT_PARAMETER_TYPES["percent"],
  },
  {
    key: "formula",
    label: "Formula",
    value: CONTRACT_PARAMETER_TYPES["formula"],
  },
];

export const MAP_CONTRACT_PARAMETER_TYPES =
  CONTRACT_PARAMETER_TYPE_OPTIONS.reduce((accum, item) => {
    return {
      ...accum,
      [item.value]: {
        ...item,
      },
    };
  }, {});
