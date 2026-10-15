/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

// All calls go through the module dispatcher (module key "huly").

export const getHulyConfig = async () => {
  const { data } = await axios.post("/module/huly", { method: "getConfig" });

  return data;
};

/**
 * The backend overwrites the stored token only when a non-empty value is sent,
 * so `token` is dropped from the payload when empty.
 */
export const saveHulyConfig = async (config) => {
  const payload = { ...config };

  if (payload.token === undefined || payload.token === null || payload.token === "") {
    delete payload.token;
  }

  const { data } = await axios.post("/module/huly", {
    method: "saveConfig",
    config: payload,
  });

  return data;
};

// Optional unsaved form values (baseUrl, token, workspace); blanks fall back to stored ones.
export const testHulyConnection = async (config = {}) => {
  const { data } = await axios.post("/module/huly", { method: "testConnection", config });

  return data;
};

export const getHulyProjects = async () => {
  const { data } = await axios.post("/module/huly", { method: "getHulyProjects" });

  return data;
};
