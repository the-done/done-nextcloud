/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const fetchProfile = async () => {
  const { data } = await axios.post("/getMy");

  return data;
};
