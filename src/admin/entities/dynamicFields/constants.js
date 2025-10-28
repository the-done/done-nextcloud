export const DYNAMIC_FIELD_TYPES = {
  1: "integer",
  2: "decimal",
  3: "string",
  4: "textarea",
  5: "date",
  6: "datetime",
  7: "select",
};

export const DYNAMIC_FIELDS_BY_ID = Object.keys(DYNAMIC_FIELD_TYPES).reduce(
  (accum, id) => {
    const value = DYNAMIC_FIELD_TYPES[id];

    return {
      ...accum,
      [value]: id,
    };
  },
  {}
);

export const DYNAMIC_FIELD_TYPE_LABELS = {
  1: "Integer",
  2: "Float",
  3: "String",
  4: "Text",
  5: "Date",
  6: "Date and time",
  7: "Drop down list",
};
