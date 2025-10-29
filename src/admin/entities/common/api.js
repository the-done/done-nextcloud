/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const getDataToViewEntity = async ({ source, slug }) => {
  const { data } = await axios.post("/getDataToViewEntity", { source, slug });

  return { data };
};

export const saveEntityImage = async ({ slug, source, fieldName, image }) => {
  const formData = new FormData();

  formData.append("slug", slug);
  formData.append("source", source);
  formData.append("field_name", fieldName);
  formData.append("image", image);

  const response = await axios.post("/saveEntityImage", formData, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
  });

  return response;
};

export const saveFieldsOrdering = async ({ source, fields }) => {
  /**
   * data: [{
   *  source: string;
   *  fields: {
   *    name: 1,
   *    annotation: 2
   *  };
   * }]
   */
  const response = await axios.post("/saveFieldsOrdering", {
    source,
    fields,
  });

  return response;
};

export const getFieldsOrdering = async ({ source }) => {
  const { data } = await axios.post("/getFieldsOrdering", {
    source,
  });

  return data;
};

export const deleteFieldsOrdering = async ({ source }) => {
  const { data } = await axios.post("/resetToDefaultOrdering", {
    source,
  });

  return data;
};

export const exportToExcel = async ({ source, options = {} }) => {
  const response = await axios.post(
    "/exportToExcel",
    {
      source,
      options,
    },
    {
      responseType: "blob",
      timeout: 60000, // 60 seconds timeout for large exports
    }
  );

  return response;
};
