/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

const baseURL = generateUrl("/apps/done");

const instance = axios.create({
  baseURL: "https://done-chat.softmus.ru",
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
});

export const fetchToken = async () => {
  const { data } = await axios.post("/module/doneai", {
    method: "generateJwtToken",
  });

  const { token } = data;

  instance.defaults.headers.common.Authorization = `Bearer ${token}`;

  return data;
};

export const healthCheck = async () => {
  const { data } = await instance.get("/healthcheck");

  return data;
};

export const fetchHistory = async () => {
  const { data } = await instance.get("/api/v1/chat/messages");

  return { data };
};

export const submitMessage = async (text) => {
  const { data } = await instance.post("/api/v1/chat/messages", { text });

  return data;
};

export const resetHistory = async () => {
  const { data } = await instance.post("/api/v1/chat/reset");

  return data;
};
