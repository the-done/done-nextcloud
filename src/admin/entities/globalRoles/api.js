import axios from "@nextcloud/axios";

import { fetchDictionaryItemBySlug } from "@/admin/entities/dictionaries/api";

export const fetchGlobalRolesByUserSlug = async (slug) => {
  const { data } = await axios.post("/getUserGlobalRoles", {
    slug,
  });

  return { data };
};

export const fetchUsersByGlobalRoleSlug = async ({ slug, slug_type }) => {
  const { data } = await axios.post("/getUsersByGlobalRole", {
    slug,
    slug_type,
  });

  return { data };
};

export const fetchGlobalRoleBySlug = async ({ slug, slug_type }) => {
  const response = await fetchDictionaryItemBySlug("globalRolesDictionary", {
    slug,
    slug_type,
  });

  return { data: response };
};

export const assignRoleToUser = async ({ user, role }) => {
  const response = await axios.post("saveGlobalRoleToUser", {
    user,
    role,
  });

  return response;
};

export const removeRoleFromUser = async ({ slug, slug_type }) => {
  const response = await axios.post("deleteGlobalRoleFromUser", {
    slug,
    slug_type,
  });

  return response;
};
