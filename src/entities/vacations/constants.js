/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */
import {format} from "date-fns";

// Import status constants from Agreement module
import { REQUEST_STATUS } from "@/entities/agreement/constants";
import { MONTHS } from "@/shared/lib/constants/date";

// Vacation status IDs - aligned with Agreement module
export const VACATION_STATUS = {
    PENDING: REQUEST_STATUS.PENDING,
    APPROVED: REQUEST_STATUS.APPROVED,
    REJECTED: REQUEST_STATUS.REJECTED,
    RETURNED: REQUEST_STATUS.RETURNED,
    CANCELED: REQUEST_STATUS.CANCELED,
    // Legacy alias
    SUBMITTED: REQUEST_STATUS.PENDING,
};

// Status translation keys (for contextTranslate)
export const VACATION_STATUS_KEYS = {
    [VACATION_STATUS.PENDING]: "Pending",
    [VACATION_STATUS.APPROVED]: "Approved",
    [VACATION_STATUS.REJECTED]: "Rejected",
    [VACATION_STATUS.RETURNED]: "Returned",
    [VACATION_STATUS.CANCELED]: "Canceled",
};

// Status colors
export const VACATION_STATUS_COLORS = {
    [VACATION_STATUS.PENDING]: "#FFC107",     // Yellow/Orange
    [VACATION_STATUS.APPROVED]: "#4CAF50",    // Green
    [VACATION_STATUS.REJECTED]: "#F44336",    // Red
    [VACATION_STATUS.RETURNED]: "#2196F3",    // Blue
    [VACATION_STATUS.CANCELED]: "#9E9E9E",    // Gray
};

// Get status translation key
export const getStatusKey = (status) => {
    return VACATION_STATUS_KEYS[status] || status;
};

// Get status color
export const getStatusColor = (status) => {
    return VACATION_STATUS_COLORS[status] || "#9E9E9E";
};

// Default vacation type colors
export const DEFAULT_TYPE_COLORS = {
    vacation: "#4CAF50",    // Green
    dayoff: "#2196F3",      // Blue
    timeoff: "#9C27B0",     // Purple
    sick: "#FF5722",        // Orange
    unpaid: "#607D8B",      // Gray
};

// Get month translation key by month key (01, 02, etc.)
export const getMonthKey = (monthKey) => {
    const monthIndex = parseInt(monthKey, 10) - 1;
    if (monthIndex >= 0 && monthIndex < 12) {
        return MONTHS[monthIndex];
    }
    return monthKey;
};

// Check if status is pending (can be approved/rejected)
export const isPendingStatus = (status) => {
    return status === VACATION_STATUS.PENDING;
};

// Check if status is returned for revision
export const isReturnedStatus = (status) => {
    return status === VACATION_STATUS.RETURNED;
};

// Check if status is approved
export const isApprovedStatus = (status) => {
    return status === VACATION_STATUS.APPROVED;
};

// Check if vacation can be processed (pending or returned)
export const canBeProcessed = (status) => {
    return status === VACATION_STATUS.PENDING || status === VACATION_STATUS.RETURNED;
};

// Format date from Y-m-d to d.m.Y
export const formatDateDisplay = (dateString) => {
    if (!dateString) return '';

    return format(new Date(dateString), "dd.MM.yyyy");
};

// Format date from d.m.Y to Y-m-d for API
export const formatDateApi = (dateString) => {
    if (!dateString) return '';

    // If already in Y-m-d format
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
        return dateString;
    }

    // Convert from d.m.Y
    const parts = dateString.split('.');
    if (parts.length === 3) {
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }

    return dateString;
};