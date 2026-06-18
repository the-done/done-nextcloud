/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

const MODULE_URL = "/module/agreement";

// ============================================================================
// Schemes
// ============================================================================

/**
 * Get all agreement schemes
 * @param {Object} params
 * @param {string} [params.entity_type] - Filter by entity type
 * @param {boolean} [params.active_only] - Only return active schemes
 */
export const fetchSchemes = async (params = {}) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getSchemes",
        ...params,
    });
    return { data };
};

/**
 * Check if any active agreement scheme exists for an entity type.
 * Entity modules call this to block record creation and show a warning when
 * no approval workflow is configured.
 * @param {string} entityType - Entity type (e.g., 'vacation')
 */
export const fetchHasActiveScheme = async (entityType) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "hasActiveScheme",
        entity_type: entityType,
    });
    return { data };
};

/**
 * Get scheme with full details (lines and approvers)
 * @param {string} slug - Scheme ID
 */
export const fetchScheme = async (slug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getScheme",
        slug,
    });
    return { data };
};

/**
 * Create a new agreement scheme
 * @param {Object} payload
 * @param {string} payload.name - Scheme name
 * @param {string} payload.entity_type - Entity type (e.g., 'vacation')
 * @param {string} [payload.description] - Optional description
 * @param {boolean} [payload.is_default] - Set as default for entity type
 * @param {boolean} [payload.is_active] - Whether scheme is active
 */
export const createScheme = async (payload) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "addScheme",
        data: payload,
    });
    return data;
};

/**
 * Update an agreement scheme
 * @param {string} slug - Scheme ID
 * @param {Object} payload - Updated data
 */
export const updateScheme = async (slug, payload) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "editScheme",
        slug,
        data: payload,
    });
    return data;
};

/**
 * Delete an agreement scheme (soft delete)
 * @param {string} slug - Scheme ID
 */
export const deleteScheme = async (slug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "deleteScheme",
        slug,
    });
    return data;
};

/**
 * Set scheme as default for its entity type
 * @param {string} slug - Scheme ID
 */
export const setDefaultScheme = async (slug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "setDefaultScheme",
        slug,
    });
    return data;
};

// ============================================================================
// Scheme Lines
// ============================================================================

/**
 * Add a line to a scheme
 * @param {string} schemeSlug - Parent scheme ID
 * @param {Object} payload
 * @param {string} payload.name - Line name
 * @param {number} [payload.sort_order] - Order in approval process
 * @param {boolean} [payload.require_all] - All approvers must approve
 * @param {boolean} [payload.deadline_enabled] - Enable deadline
 * @param {number} [payload.deadline_days] - Days until auto-rejection
 */
export const createSchemeLine = async (schemeSlug, payload) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "addSchemeLine",
        scheme_slug: schemeSlug,
        data: payload,
    });
    return data;
};

/**
 * Update a scheme line
 * @param {string} slug - Line ID
 * @param {Object} payload - Updated data
 */
export const updateSchemeLine = async (slug, payload) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "editSchemeLine",
        slug,
        data: payload,
    });
    return data;
};

/**
 * Delete a scheme line (soft delete)
 * @param {string} slug - Line ID
 */
export const deleteSchemeLine = async (slug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "deleteSchemeLine",
        slug,
    });
    return data;
};

// ============================================================================
// Approvers
// ============================================================================

/**
 * Add approver to a line
 * @param {string} lineSlug - Parent line ID
 * @param {string} approverType - 'user' or 'role'
 * @param {string} [userId] - User ID (if approverType is 'user')
 * @param {number} [roleId] - Role ID (if approverType is 'role')
 */
export const addApprover = async (lineSlug, approverType, userId = null, roleId = null) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "addApprover",
        line_slug: lineSlug,
        approver_type: approverType,
        user_id: userId,
        role_id: roleId,
    });
    return data;
};

/**
 * Remove approver from a line
 * @param {string} slug - Approver ID
 */
export const removeApprover = async (slug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "removeApprover",
        slug,
    });
    return data;
};

/**
 * Get users list for approver selection
 */
export const fetchUsersForApprover = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getUsersForApprover",
    });
    return { data };
};

/**
 * Get roles list for approver selection
 */
export const fetchRolesForApprover = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getRolesForApprover",
    });
    return { data };
};

// ============================================================================
// Agreement Requests
// ============================================================================

/**
 * Get requests for current user to approve
 * @param {Object} [params] - Optional filter parameters
 * @param {string} [params.entity_type] - Filter by entity type
 * @param {string[]} [params.created_by] - Filter by creator user IDs
 * @param {string} [params.date_from] - Filter by date from (Y-m-d)
 * @param {string} [params.date_to] - Filter by date to (Y-m-d)
 */
export const fetchMyRequests = async (params = {}) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getMyRequests",
        ...params,
    });
    return { data };
};

/**
 * Get completed requests where current user acted as approver (history)
 * @param {Object} [params] - Optional filter parameters (same as fetchMyRequests)
 */
export const fetchMyRequestsHistory = async (params = {}) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getMyRequestsHistory",
        ...params,
    });
    return { data };
};

/**
 * Get request with full details
 * @param {string} slug - Request ID
 */
export const fetchRequest = async (slug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getRequest",
        slug,
    });
    return { data };
};

/**
 * Get request history
 * @param {string} slug - Request ID
 */
export const fetchRequestHistory = async (slug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getRequestHistory",
        slug,
    });
    return { data };
};

/**
 * Approve a request
 * @param {string} slug - Request ID
 * @param {string} [comment] - Optional comment
 */
export const approveRequest = async (slug, comment = null) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "approveRequest",
        slug,
        comment,
    });
    return data;
};

/**
 * Reject a request
 * @param {string} slug - Request ID
 * @param {string} comment - Comment (required)
 */
export const rejectRequest = async (slug, comment) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "rejectRequest",
        slug,
        comment,
    });
    return data;
};

/**
 * Return a request for revision
 * @param {string} slug - Request ID
 * @param {string} comment - Comment (required)
 */
export const returnRequest = async (slug, comment) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "returnRequest",
        slug,
        comment,
    });
    return data;
};

/**
 * Resubmit a returned request
 * @param {string} slug - Request ID
 */
export const resubmitRequest = async (slug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "resubmitRequest",
        slug,
    });
    return data;
};

// ============================================================================
// Options / Dictionaries
// ============================================================================

/**
 * Get all options for agreement module (request statuses, approver types, entity types, action types)
 * @returns {Object} { requestStatuses, approverTypes, entityTypes, actionTypes }
 */
export const fetchOptions = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getOptions",
    });
    return { data };
};

/**
 * Get request status options
 */
export const fetchRequestStatuses = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getRequestStatuses",
    });
    return { data };
};

/**
 * Get approver type options
 */
export const fetchApproverTypes = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getApproverTypes",
    });
    return { data };
};

/**
 * Get entity type options
 */
export const fetchEntityTypes = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getEntityTypes",
    });
    return { data };
};

/**
 * Get action type options
 */
export const fetchActionTypes = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getActionTypes",
    });
    return { data };
};

// ============================================================================
// Scheme Assignments
// ============================================================================

/**
 * Get all assignments for an entity type
 * @param {string} entityType - Entity type (e.g., 'vacation')
 */
export const fetchAssignments = async (entityType) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getAssignments",
        entity_type: entityType,
    });
    return { data };
};

/**
 * Get assignments for a specific scheme
 * @param {string} schemeSlug - Scheme ID
 */
export const fetchSchemeAssignments = async (schemeSlug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getSchemeAssignments",
        scheme_slug: schemeSlug,
    });
    return { data };
};

/**
 * Create or update a scheme assignment
 * @param {string} schemeSlug - Scheme ID
 * @param {string} entityType - Entity type
 * @param {string} subEntityType - 'user' or 'project'
 * @param {string} subEntityId - User or project ID
 */
export const createAssignment = async (schemeSlug, entityType, subEntityType, subEntityId) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "addAssignment",
        scheme_slug: schemeSlug,
        entity_type: entityType,
        sub_entity_type: subEntityType,
        sub_entity_id: subEntityId,
    });
    return data;
};

/**
 * Delete a scheme assignment
 * @param {string} slug - Assignment ID
 */
export const deleteAssignment = async (slug) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "deleteAssignment",
        slug,
    });
    return data;
};

/**
 * Get sub-entity type options
 */
export const fetchSubEntityTypes = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getSubEntityTypes",
    });
    return { data };
};

/**
 * Get available entities for assignment (users or projects)
 * @param {string} subEntityType - 'user' or 'project'
 */
export const fetchEntitiesForAssignment = async (subEntityType) => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getEntitiesForAssignment",
        sub_entity_type: subEntityType,
    });
    return { data };
};

/**
 * Get projects list for the requests filter
 */
export const fetchProjectsForFilter = async () => {
    const { data } = await axios.post(MODULE_URL, {
        method: "getProjectsForFilter",
    });
    return { data };
};