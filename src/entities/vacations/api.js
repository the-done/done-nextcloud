/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

const MODULE_URL = "/module/vacations";

// ============================================================================
// Vacation Requests
// ============================================================================

/**
 * Get vacations data for table view
 * @param {Object} params - Filter parameters
 * @param {string} params.date_from - Start date (Y-m-d format)
 * @param {string} params.date_to - End date (Y-m-d format)
 * @param {string[]} params.employee_ids - Filter by employee IDs
 * @param {string[]} params.project_ids - Filter by project IDs
 * @param {string[]} params.vacation_type_ids - Filter by vacation type IDs
 * @param {number} params.vacation_status - Filter by status (1=Submitted, 2=Approved, 3=Rejected)
 */
export const fetchVacationsTableData = async (params = {}) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getVacationsTableData",
        ...params,
    });
    return { data };
};

/**
 * Create a new vacation request
 * @param {Object} payload - Vacation data
 * @param {string} payload.user_id - Employee ID (who will be absent)
 * @param {string} payload.type_id - Vacation type ID
 * @param {string} payload.date_start - Start date (Y-m-d format)
 * @param {string} payload.date_end - End date (Y-m-d format)
 * @param {string} [payload.project_id] - Optional project ID
 * @param {string} [payload.comment] - Optional comment
 */
export const createVacation = async (payload) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "addVacation",
        data: payload,
    });
    return data;
};

/**
 * Update a vacation request
 * @param {string} slug - Vacation ID
 * @param {Object} payload - Updated data
 */
export const updateVacation = async (slug, payload) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "editVacation",
        slug,
        data: payload,
    });
    return data;
};

/**
 * Get a single vacation enriched for the request card view.
 * @param {string} slug - Vacation ID (32-char hash)
 */
export const fetchVacation = async (slug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getVacation",
        slug,
    });
    return { data };
};

/**
 * Cancel a vacation request (owner only). The record is preserved with
 * status_id = canceled; the linked agreement request is also canceled.
 * @param {string} slug - Vacation ID
 */
export const cancelVacation = async (slug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "cancelVacation",
        slug,
    });
    return data;
};

/**
 * Get vacation request history
 * @param {string} slug - Vacation ID
 */
export const fetchVacationHistory = async (slug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getVacationHistory",
        slug,
    });
    return { data };
};

// ============================================================================
// Balance Management
// ============================================================================

/**
 * Get remaining days for current user or specific employee
 * @param {Object} params
 * @param {number} [params.year] - Year (defaults to current)
 * @param {string} [params.user_id] - Employee ID (defaults to current user)
 */
export const fetchRemainingDays = async (params = {}) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getRemainingDays",
        ...params,
    });
    return { data };
};

/**
 * Get balances for multiple employees
 * @param {string[]} userIds - Employee IDs
 * @param {number} [year] - Year (defaults to current)
 */
export const fetchEmployeeBalances = async (userIds, year = null) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getEmployeeBalances",
        user_ids: userIds,
        year,
    });
    return { data };
};

/**
 * Set individual balance for an employee (HR function)
 * @param {Object} params
 * @param {string} params.user_id - Employee ID
 * @param {string} params.type_id - Vacation type ID
 * @param {number} params.year - Year
 * @param {number} params.days_total - Total days available
 * @param {string} [params.comment] - Optional comment
 */
export const setEmployeeBalance = async (params) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "setEmployeeBalance",
        ...params,
    });
    return data;
};

// ============================================================================
// Vacation Types
// ============================================================================

/**
 * Get all vacation types
 */
export const fetchVacationTypes = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getVacationTypes",
    });
    return { data };
};

/**
 * Get vacation statuses
 */
export const fetchVacationStatuses = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getVacationStatuses",
    });
    return { data };
};

/**
 * Add a new vacation type
 * @param {Object} payload
 * @param {string} payload.name - Type name
 * @param {string} [payload.color] - Color in HEX format
 * @param {number} [payload.sort] - Sort order
 */
export const createVacationType = async (payload) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "addVacationType",
        data: payload,
    });
    return data;
};

/**
 * Update a vacation type
 * @param {string} slug - Type ID
 * @param {Object} payload - Updated data
 */
export const updateVacationType = async (slug, payload) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "editVacationType",
        slug,
        data: payload,
    });
    return data;
};

/**
 * Delete a vacation type (soft delete)
 * @param {string} slug - Type ID
 */
export const deleteVacationType = async (slug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "deleteVacationType",
        slug,
    });
    return data;
};

// ============================================================================
// Vacation Type Settings
// ============================================================================

/**
 * Get vacation type settings (default limits)
 * @param {string} [typeId] - Optional type ID to filter
 */
export const fetchVacationTypeSettings = async (typeId = null) => {
    const params = {
        method: "getVacationTypeSettings",
    };
    if (typeId) {
        params.type_id = typeId;
    }
    const { data } = await axios.post(MODULE_URL, params);
    return { data };
};

/**
 * Set vacation type settings
 * @param {Object} params
 * @param {string} params.type_id - Vacation type ID
 * @param {number} params.value - Default days limit
 * @param {boolean} [params.display_warning] - Show warning when limit reached
 */
export const setVacationTypeSetting = async (params) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "setVacationTypeSetting",
        ...params,
    });
    return data;
};

/**
 * Delete vacation type setting
 * @param {string} slug - Setting ID
 */
export const deleteVacationTypeSetting = async (slug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "deleteVacationTypeSetting",
        slug,
    });
    return data;
};

export const fetchProjectsOptionsForVacations = async () => {
    const { data } = await axios.post(MODULE_URL ,{
        method: 'getProjectsOptionsForVacations'
    });

    return { data };
};

/**
 * Get the full project list (id + name) for the vacations report badges.
 * Not head-scoped — returns all projects so every badge can resolve its id.
 */
export const fetchProjectsOptionsForVacationsReport = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: 'getProjectsOptionsForVacationsReport'
    });

    return { data };
};

export const fetchEmployeesOptionsForVacations = async () => {
    const { data } = await axios.post(MODULE_URL ,{
        method: 'getEmployeesOptionsForVacations'
    });

    return { data };
};

/**
 * Get upcoming approved paid leave vacations for the current user
 */
export const fetchUpcomingPaidVacations = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getUpcomingPaidVacations",
    });
    return { data };
};

/**
 * Get all vacations in a period grouped by employee for Gantt display (approvers only)
 * @param {Object} params
 * @param {string} params.date_from - Period start (Y-m-d)
 * @param {string} params.date_to   - Period end (Y-m-d)
 */
export const fetchVacationsForGantt = async (params = {}) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getVacationsForGantt",
        ...params,
    });
    return { data };
};

// ============================================================================
// Vacation Coefficients
// ============================================================================

/**
 * List vacation coefficient records.
 * @param {Object} [params]
 * @param {string} [params.user_id] - Optional filter by employee
 * @param {string} [params.type_id] - Optional filter by vacation type
 */
export const fetchVacationCoefficients = async (params = {}) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getCoefficients",
        ...params,
    });
    return { data };
};

/**
 * Create a vacation coefficient record.
 * @param {Object} payload
 * @param {string} payload.user_id
 * @param {string} payload.type_id
 * @param {number} payload.value
 * @param {string} payload.effective_from - Y-m-d
 * @param {string} [payload.effective_to] - Y-m-d, optional
 * @param {string} [payload.comment]
 */
export const createVacationCoefficient = async (payload) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "createCoefficient",
        ...payload,
    });
    return data;
};

/**
 * Update an existing vacation coefficient record.
 * @param {string} id
 * @param {Object} data - any of value, effective_from, effective_to, comment
 */
export const updateVacationCoefficient = async (id, data) => {
    const { data: resp } = await axios.post(MODULE_URL, {
        method: "updateCoefficient",
        id,
        data,
    });
    return resp;
};

/**
 * Delete a vacation coefficient record.
 * @param {string} id
 */
export const deleteVacationCoefficient = async (id) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "deleteCoefficient",
        id,
    });
    return data;
};

// ============================================================================
// CEO Vacations Report
// ============================================================================

/**
 * Get the configurable-table payload for the CEO vacations report
 * (one row per employee with computed columns).
 * @param {Object} [params]
 * @param {number} [params.year] - Year of the report (defaults to current)
 */
export const fetchVacationsReportTableData = async (params = {}) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getVacationsReportTableData",
        ...params,
    });
    return { data };
};

/**
 * List all vacations of a single employee, newest-first, for the "Details"
 * side panel of the report.
 * @param {string} userId - Done internal user id (32-char hash)
 */
export const fetchEmployeeVacationsList = async (userId) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getEmployeeVacationsList",
        user_id: userId,
    });
    return { data };
};

/**
 * Get the configurable separator used between "used" and "limit" in the
 * report's usage columns (defaults to "/").
 */
export const fetchVacationReportSeparator = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getReportSeparator",
    });
    return { data };
};

/**
 * Set the report usage separator. Empty value resets it to the default.
 * @param {string} separator
 */
export const setVacationReportSeparator = async (separator) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "setReportSeparator",
        separator,
    });
    return data;
};

/**
 * Get the minimum length (days) of a mandatory leave (defaults to 14).
 */
export const fetchMandatoryLeaveMinDays = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getMandatoryLeaveMinDays",
    });
    return { data };
};

/**
 * Set the minimum length (days) of a mandatory leave.
 * @param {number} minDays
 */
export const setMandatoryLeaveMinDays = async (minDays) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "setMandatoryLeaveMinDays",
        min_days: minDays,
    });
    return data;
};