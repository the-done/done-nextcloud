/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

/**
 * Whether saving a new time record should first ask "Send to Huly?".
 *
 * Only when the Huly integration is usable (installed, switched on and configured),
 * the record is new, it is not downtime and it carries a task link.
 *
 * @param {{ isEdit: boolean, hulyActive: boolean, payload: object }} params
 * @returns {boolean}
 */
export const shouldAskToSendToHuly = ({ isEdit, hulyActive, payload }) =>
  isEdit === false &&
  hulyActive === true &&
  payload?.is_downtime !== true &&
  Boolean(payload?.task_link) === true;
