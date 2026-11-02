/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

/**
 * Who may edit or cancel a vacation (status rules aside): its employee, its author, or a
 * vacations manager (common permission `canManageVacations`: login admin, Done ADMIN or
 * "View settings"). Plain approvers may not; they act through the Agreement actions.
 * Mirrors the server check in VacationsController.
 *
 * @param {{ user_id?: string, created_by?: string } | null} vacation
 * @param {string | null} currentUserId
 * @param {boolean} canManage the `canManageVacations` common permission
 * @returns {boolean}
 */
export const canChangeVacation = (vacation, currentUserId, canManage) => {
  if (!vacation) {
    return false;
  }

  if (canManage === true) {
    return true;
  }

  return Boolean(currentUserId)
    && (vacation.user_id === currentUserId || vacation.created_by === currentUserId);
};
