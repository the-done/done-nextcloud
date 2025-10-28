import axios from "@nextcloud/axios";

export const fetchDynamicFieldsForSource = async (source) => {
  const response = await axios.post("/getDynamicFieldsForSource", {
    source: Number(source),
  });

  return response;
};

export const saveDynamicField = async ({
  slug,
  title,
  field_type,
  required,
  multiple,
  source,
}) => {
  const response = await axios.post("/saveDynamicField", {
    slug,
    title,
    field_type,
    required,
    multiple,
    source,
  });

  return response;
};

export const deleteDynamicField = async (slug) => {
  const response = await axios.post("/deleteDynamicField", {
    slug,
  });

  return response;
};

export const saveDynamicFieldsDataMultiple = async ({ record_id, data }) => {
  /**
   * data: [{
   *  dyn_field_id: string;
   *  value: any;
   * }]
   */
  const response = await axios.post("/saveDynamicFieldsDataMultiple", {
    record_id,
    data,
  });

  return response;
};

export const fetchDynamicFieldValuesByRecordId = async ({ record_id }) => {
  const response = await axios.post("/getDinFieldsDataForRecord", {
    record_id,
  });

  return response;
};

export const fetchDropdownOptions = async (dyn_field_id) => {
  const response = await axios.post("/getDropdownOptions", {
    dyn_field_id,
  });
  return response;
};

export const saveDropdownOption = async ({
  slug,
  dyn_field_id,
  option_label,
  ordering,
}) => {
  const { data } = await axios.post("/saveDropdownOption", {
    slug,
    dyn_field_id,
    option_label,
    ordering,
  });

  return data;
};

export const deleteDropdownOption = async ({ slug }) => {
  const response = await axios.post("/deleteDropdownOption", {
    slug,
  });

  return response;
};

export const reorderDropdownOptions = async ({ dyn_field_id, option_ids }) => {
  const response = await axios.post("/reorderDropdownOptions", {
    dyn_field_id,
    option_ids,
  });

  return response;
};
