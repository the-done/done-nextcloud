/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const getProStatus = async () => {
  const { data } = await axios.get("/pro/status");

  return data;
};

// Activate (or, with an empty key, re-activate with the stored key). Does not
// install the modules — that is a separate step.
export const activatePro = async (key = "") => {
  const { data } = await axios.post("/pro/activate", { key });

  return data;
};

// Install or reinstall the licensed package. force=true reinstalls in place.
export const installPro = async (force = false) => {
  const { data } = await axios.post("/pro/install", { force });

  return data;
};

export const updatePro = async () => {
  const { data } = await axios.post("/pro/update");

  return data;
};
