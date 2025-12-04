/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

const instance = axios.create({
  baseURL: "http://localhost:7104",
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
});

export const fetchToken = async () => {
  const { data } = await axios.post("/module/doneai", {
    method: "generateJwtToken",
  });

  // const { token } = data;

  const token =
    "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiJhbGVrc2FuZHIuYSIsInVzZXJfaWQiOiJlNDEzMmRlZWIzYWJkYWUyZDJiMzEyYjAyNzI3YmRkZiIsImlhdCI6MTc2NDY1ODI2MiwiZXhwIjoxNzY5ODQyMjYyLCJpc3MiOiJkb25lLW5leHRjbG91ZCJ9.6xGFY70ZPHdn5Fya6mVI6HvMx9AIr8R56Bj0Tv43yog";

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
