/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

/**
 * Approve / Reject / Return in the approver request list. Hidden for the user's own requests,
 * i.e. requests about the user (flagged by the server as `is_own`; filing a request for someone
 * else does not make it your own): the server refuses them anyway.
 *
 * In the "all" scope (managers) the list has no per-item right to act, so it shows no quick
 * actions; the request card decides through `can_act`.
 *
 * @param {{ is_own?: boolean } | null} request
 * @param {string} [scope] "mine" (default) or "all"
 * @returns {boolean}
 */
export const canShowListActions = (request, scope = "mine") =>
  Boolean(request) && request.is_own !== true && scope !== "all";

/**
 * Approve / Reject / Return in the request card: a pending request that is not the user's own
 * and that the server says the user can act on (`getRequest` → `can_act.can_act`).
 *
 * @param {{ status?: string, is_own?: boolean } | null} request the list item
 * @param {{ can_act?: { can_act?: boolean } } | null} details the loaded request details
 * @returns {boolean}
 */
export const canShowCardActions = (request, details) =>
  request?.status === "pending" &&
  canShowListActions(request) &&
  details?.can_act?.can_act === true;

/** Requests page scopes (server: AgreementService::SCOPE_MINE / SCOPE_ALL). */
export const SCOPE_MINE = "mine";
export const SCOPE_ALL = "all";

/**
 * The scope to request: "all" (every request of the company) only for managers
 * (`canManageAgreementSchemes`), "mine" otherwise. The server enforces the same rule.
 *
 * @param {string | undefined} selected
 * @param {boolean} canManage
 * @returns {string}
 */
export const requestsScope = (selected, canManage) =>
  canManage === true && selected === SCOPE_ALL ? SCOPE_ALL : SCOPE_MINE;

/**
 * Resubmit button on the request card: shown when `getRequest` reports `can_resubmit`.
 *
 * @param {{ can_resubmit?: boolean } | null} details
 * @returns {boolean}
 */
export const canShowResubmit = (details) => details?.can_resubmit === true;

/**
 * Names of the approvers the request's current line is waiting for (users and roles).
 *
 * @param {{ current_line_id?: string, scheme?: { lines?: Array } } | null} details
 * @returns {string[]}
 */
export const currentLineApproverNames = (details) => {
  const line = (details?.scheme?.lines || []).find((item) => item.id === details?.current_line_id);

  if (!details?.current_line_id || !line) {
    return [];
  }

  return (line.approvers || []).map((approver) => approver.name).filter(Boolean);
};

/**
 * Untranslated text of the empty "Active" tab: in the "all" scope there is nothing pending in
 * the whole company, in "mine" nothing waits for the user's approval.
 *
 * @param {string} [scope]
 * @returns {string}
 */
export const activeEmptyStateText = (scope) =>
  scope === SCOPE_ALL ? "No pending requests" : "No pending requests for your approval";
