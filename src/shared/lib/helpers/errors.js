/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */
import Vue from "vue";

export const handleRestErrors = (e) => {
  const { response } = e;

  console.error(e);

  if (
    response?.data?.error_type === "validation" &&
    Array.isArray(response?.data?.message) &&
    response?.data?.message.length > 0
  ) {
    response.data.message.forEach((text) => {
      Vue.notify({
        text,
        type: "error",
        duration: 5 * 1000,
      });
    });

    return;
  }

  if (response?.data?.error && typeof response.data.error === "string") {
    Vue.notify({
      text: response.data.error,
      type: "error",
      duration: 5 * 1000,
    });

    return;
  }

  if (e.message) {
    Vue.notify({
      text: e.message,
      type: "error",
      duration: 5 * 1000,
    });

    return;
  }

  Vue.notify({
    text: "An unexpected error occurred",
    type: "error",
    duration: 5 * 1000,
  });
};
