/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

// Request status IDs (for logic checks in frontend)
export const REQUEST_STATUS = {
    PENDING: "pending",
    APPROVED: "approved",
    REJECTED: "rejected",
    RETURNED: "returned",
    CANCELED: "canceled",
};

// Approver type IDs
export const APPROVER_TYPE = {
    USER: "user",
    ROLE: "role",
};

// Action type IDs
export const ACTION_TYPE = {
    APPROVED: "approved",
    REJECTED: "rejected",
    RETURNED: "returned",
};

// Entity type IDs
export const ENTITY_TYPE = {
    VACATION: "vacation",
};

// Sub-entity type IDs (for scheme assignments)
export const SUB_ENTITY_TYPE = {
    USER: "user",
    PROJECT: "project",
};

// Check if request status is pending (can be approved/rejected/returned)
export const isPendingStatus = (status) => {
    return status === REQUEST_STATUS.PENDING;
};

// Check if request status allows actions
export const canActOnStatus = (status) => {
    return status === REQUEST_STATUS.PENDING;
};

// Check if request was returned for revision
export const isReturnedStatus = (status) => {
    return status === REQUEST_STATUS.RETURNED;
};

// Check if request is completed (approved or rejected)
export const isCompletedStatus = (status) => {
    return status === REQUEST_STATUS.APPROVED || status === REQUEST_STATUS.REJECTED;
};