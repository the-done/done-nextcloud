/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const fetchDictionary = async (title) => {
  const {
    data: { body, header },
  } = await axios.get("/getDictionaryData", {
    params: {
      title,
    },
  });

  return { data: body, headers: header };
};

export const fetchDictionaryItemBySlug = async (title, { slug, slug_type }) => {
  const { data } = await axios.post("/getDictionaryItemData", {
    title,
    slug,
    slug_type
  });

  return data;
};

export const createDictionaryItem = async (title, payload) => {
  const response = await axios.post("/saveDict", {
    title,
    data: payload,
  });

  return response;
};

export const updateDictionaryItem = async (title, slug, slugType, data) => {
  const response = await axios.post("/saveDict", {
    title,
    slug,
    slug_type: slugType,
    data,
  });

  return response;
};

export const deleteDictionaryItem = async (title, { slug, slug_type }) => {
  const response = await axios.post("/deleteDictItem", {
    title,
    slug,
    slug_type,
  });

  return response;
};

export const getNextSortInDict = async (title) => {
  const { data } = await axios.post("/getNextSortInDict", {
    title,
  });

  return data;
};

export const fetchPositionsDictionary = () => {
  return fetchDictionary("positionsDictionary");
};

export const fetchContractTypesDictionary = () => {
  return fetchDictionary("contractTypesDictionary");
};

export const fetchRolesDictionary = () => {
  return fetchDictionary("rolesDictionary");
};

export const fetchDirectionsDictionary = () => {
  return fetchDictionary("directionsDictionary");
};

export const fetchProjectStagesDictionary = () => {
  return fetchDictionary("projectStagesDictionary");
};

export const fetchCustomersDictionary = () => {
  return fetchDictionary("customersDictionary");
};

export const fetchGlobalRolesDictionary = () => {
  return fetchDictionary("globalRolesDictionary");
};

export const fetchUsersGlobalRolesDictionary = () => {
  return fetchDictionary("usersGlobalRolesDictionary");
};

export const fetchUsersTeamRolesDictionary = () => {
  return fetchDictionary("rolesInTeamDictionary");
};
