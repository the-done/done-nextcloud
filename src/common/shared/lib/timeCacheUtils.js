/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import {
  LOCALSTORAGE_LAST_REPORT_PROJECT_ID,
  LOCALSTORAGE_LAST_REPORT_MINUTES,
} from "@/common/shared/lib/constants";

/**
 * Clears time cache from localStorage
 */
export const clearTimeCache = () => {
  try {
    localStorage.removeItem(LOCALSTORAGE_LAST_REPORT_PROJECT_ID);
    localStorage.removeItem(LOCALSTORAGE_LAST_REPORT_MINUTES);
  } catch (e) {
    console.error('Error clearing time cache:', e);
  }
};

/**
 * Checks if there are saved time values in localStorage
 */
export const hasTimeCache = () => {
  try {
    const minutes = localStorage.getItem(LOCALSTORAGE_LAST_REPORT_MINUTES);
    const projectId = localStorage.getItem(LOCALSTORAGE_LAST_REPORT_PROJECT_ID);
    return !!(minutes || projectId);
  } catch (e) {
    console.error('Error checking time cache:', e);
    return false;
  }
};

/**
 * Gets saved time values from localStorage
 */
export const getTimeCache = () => {
  try {
    return {
      minutes: localStorage.getItem(LOCALSTORAGE_LAST_REPORT_MINUTES),
      projectId: localStorage.getItem(LOCALSTORAGE_LAST_REPORT_PROJECT_ID),
    };
  } catch (e) {
    console.error('Error getting time cache:', e);
    return { minutes: null, projectId: null };
  }
}; 