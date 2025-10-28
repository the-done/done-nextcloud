import axios from "@nextcloud/axios";

export const saveTableSortColumns = async ({
  source,
  column,
  ordering,
  for_all = false,
  slug,
}) => {
  const { data } = await axios.post("/saveTableSortColumns", {
    source,
    column,
    ordering,
    for_all,
    slug,
  });

  return data;
};

export const saveTableColumnView = async ({
  source,
  column,
  width,
  hide,
  for_all = false,
  slug,
}) => {
  const { data } = await axios.post("/saveTableColumnView", {
    source,
    column,
    width,
    hide,
    for_all,
    slug,
  });

  return data;
};

export const saveTableSortColumnsMultiple = async ({
  sort_data,
  source,
  for_all = false,
}) => {
  const { data } = await axios.post("/saveTableSortColumnsMultiple", {
    sort_data,
    source,
    for_all,
  });

  return data;
};

export const deleteTableSortColumns = async ({ slug, source }) => {
  const { data } = await axios.post("/deleteTableSortColumns", {
    slug,
    source,
  });

  return data;
};

export const saveTableSortWithinColumns = async ({
  source,
  column,
  sort,
  sort_ordering,
  for_all = false,
  slug,
}) => {
  const { data } = await axios.post("/saveTableSortWithinColumns", {
    source,
    column,
    sort,
    sort_ordering,
    for_all,
    slug,
  });

  return data;
};

export const deleteTableSortWithinColumns = async ({ slug, source }) => {
  const { data } = await axios.post("/deleteTableSortWithinColumns", {
    slug,
    source,
  });

  return data;
};

export const getConditionsForFilter = async () => {
  const { data } = await axios.post("/getConditionsForFilter");

  return { data };
};

export const saveTableFilter = async ({
  source,
  column,
  condition,
  value,
  for_all = false,
  slug,
}) => {
  const { data } = await axios.post("/saveTableFilter", {
    source,
    column,
    condition,
    value,
    for_all,
    slug,
  });

  return data;
};

export const deleteTableFilter = async ({ slug, source }) => {
  const { data } = await axios.post("/deleteTableFilter", { slug, source });

  return data;
};

export const saveTableSortWithinColumnsMultiple = async ({
  source,
  sort_data,
}) => {
  const { data } = await axios.post("/saveTableSortWithinColumnsMultiple", {
    source,
    sort_data,
  });

  return data;
};
