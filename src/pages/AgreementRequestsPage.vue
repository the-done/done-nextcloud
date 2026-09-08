/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage>
    <!-- Toolbar -->
    <VToolbar>
      <NcBreadcrumbs v-if="breadcrumbs?.length > 0">
        <NcBreadcrumb
          v-for="(item, index) in breadcrumbs"
          :key="index"
          :name="contextTranslate(item.title, context)"
          :to="item.path"
          :forceIconText="index === 0"
        >
          <template v-if="item.icon" #icon>
            <component v-if="item.icon" :is="item.icon" />
            <CheckDecagram v-else />
          </template>
        </NcBreadcrumb>
      </NcBreadcrumbs>

      <template #actions>
        <VButton variant="secondary" @click="fetchDataWithFilters">
          <template #icon>
            <Refresh :size="20" />
          </template>
          {{ contextTranslate("Refresh", context) }}
        </VButton>
      </template>
    </VToolbar>

    <VPageLayout>
      <!-- Sidebar with filters -->
      <TimeTrackingAside
        :active-date="activeDate"
        :range-type="activeRangeType"
        :filter-descriptor="filterDescriptor"
        :available-range-types="['month', 'year']"
        @update:activeDate="handleUpdateActiveDate"
        @update:rangeType="handleUpdateActiveRangeType"
        @update:filter="handleUpdateFilter"
      />

      <!-- Main content -->
      <VPageContent>
        <VScrollArea class="agreement-requests__content">
          <!-- Loading state -->
          <div v-if="isLoading" class="agreement-requests__loader">
            <VLoader />
          </div>

          <template v-else>

        <!-- Tabs -->
        <div class="agreement-requests__tabs">
          <button
            class="agreement-requests__tab"
            :class="{ 'agreement-requests__tab--active': activeTab === 'active' }"
            @click="switchTab('active')"
          >
            {{ contextTranslate("Active", context) }}
            <span v-if="requests.length > 0" class="agreement-requests__tab-count">
              {{ requests.length }}
            </span>
          </button>
          <button
            class="agreement-requests__tab"
            :class="{ 'agreement-requests__tab--active': activeTab === 'history' }"
            @click="switchTab('history')"
          >
            {{ contextTranslate("History", context) }}
          </button>
        </div>

        <!-- Active tab -->
        <template v-if="activeTab === 'active'">
          <!-- No requests -->
          <div v-if="requests.length === 0" class="agreement-requests__empty">
            <CheckCircleOutline :size="48" class="agreement-requests__empty-icon" />
            <p class="agreement-requests__empty-text">
              {{ contextTranslate("No pending requests for your approval", context) }}
            </p>
          </div>

          <!-- Requests list -->
          <div v-else class="agreement-requests__list">
          <div
            v-for="request in requests"
            :key="request.id"
            class="agreement-requests__card"
          >
            <div class="agreement-requests__card-header">
              <div class="agreement-requests__card-info">
                <div class="agreement-requests__card-title-row">
                  <h3 class="agreement-requests__card-title">
                    {{ request.cached_title || contextTranslate("Request", context) + " #" + request.id.substring(0, 8) }}
                  </h3>
                  <span
                    class="agreement-requests__status-badge"
                    :style="{ backgroundColor: getStatusColor(request.status) }"
                  >
                    {{ getStatusName(request.status) }}
                  </span>
                </div>
                <div class="agreement-requests__card-meta">
                  <span class="agreement-requests__card-entity">
                    <FileDocumentOutline :size="14" />
                    {{ getEntityTypeName(request.entity_type) }}
                  </span>
                  <span v-if="request.project_id && getProjectName(request.project_id)" class="agreement-requests__card-entity">
                    <FolderOutline :size="14" />
                    {{ getProjectName(request.project_id) }}
                  </span>
                  <span v-if="request.created_at" class="agreement-requests__card-date">
                    <CalendarClock :size="14" />
                    {{ formatDate(request.created_at) }}
                  </span>
                  <span v-if="request.deadline_at" class="agreement-requests__card-deadline" :class="{ 'agreement-requests__card-deadline--urgent': isDeadlineUrgent(request.deadline_at) }">
                    <AlertCircleOutline :size="14" />
                    {{ contextTranslate("Deadline", context) }}: {{ formatDate(request.deadline_at) }}
                  </span>
                </div>
              </div>
              <div class="agreement-requests__card-actions">
                <VButton variant="primary" size="small" @click="openRequestDetails(request)">
                  {{ contextTranslate("Review", context) }}
                </VButton>
              </div>
            </div>

            <!-- Cached data preview -->
            <div v-if="getCachedData(request)" class="agreement-requests__preview">
              <div v-if="getCachedData(request).employee" class="agreement-requests__preview-item">
                <span class="agreement-requests__preview-label">{{ contextTranslate("Employee", context) }}:</span>
                <span class="agreement-requests__preview-value">{{ getCachedData(request).employee }}</span>
              </div>
              <div v-if="getCachedData(request).type" class="agreement-requests__preview-item">
                <span class="agreement-requests__preview-label">{{ contextTranslate("Type", context) }}:</span>
                <span class="agreement-requests__preview-value">{{ contextTranslate(getCachedData(request).type ?? '', context) }}</span>
              </div>
              <div v-if="getCachedData(request).days" class="agreement-requests__preview-item">
                <span class="agreement-requests__preview-label">{{ contextTranslate("Days", context) }}:</span>
                <span class="agreement-requests__preview-value">{{ getCachedData(request).days }}</span>
              </div>
              <div v-if="getCachedData(request).comment" class="agreement-requests__preview-item">
                <span class="agreement-requests__preview-label">{{ contextTranslate("Comment", context) }}:</span>
                <span class="agreement-requests__preview-value">{{ getCachedData(request).comment }}</span>
              </div>
            </div>

            <!-- Quick actions -->
            <div class="agreement-requests__quick-actions">
              <VButton
                variant="success"
                size="small"
                @click="quickApprove(request)"
              >
                <template #icon>
                  <Check :size="16" />
                </template>
                {{ contextTranslate("Approve", context) }}
              </VButton>
              <VButton
                variant="secondary"
                size="small"
                @click="openRejectModal(request)"
              >
                <template #icon>
                  <Close :size="16" />
                </template>
                {{ contextTranslate("Reject", context) }}
              </VButton>
              <VButton
                variant="secondary"
                size="small"
                @click="openReturnModal(request)"
              >
                <template #icon>
                  <Undo :size="16" />
                </template>
                {{ contextTranslate("Return", context) }}
              </VButton>
            </div>
          </div>
        </div>
        </template>

        <!-- History tab -->
        <template v-if="activeTab === 'history'">
          <div v-if="historyRequests.length === 0" class="agreement-requests__empty">
            <CheckCircleOutline :size="48" class="agreement-requests__empty-icon" />
            <p class="agreement-requests__empty-text">
              {{ contextTranslate("No completed requests", context) }}
            </p>
          </div>

          <div v-else class="agreement-requests__list">
            <div
              v-for="request in historyRequests"
              :key="request.id"
              class="agreement-requests__card"
            >
              <div class="agreement-requests__card-header">
                <div class="agreement-requests__card-info">
                  <div class="agreement-requests__card-title-row">
                    <h3 class="agreement-requests__card-title">
                      {{ request.cached_title || contextTranslate("Request", context) + " #" + request.id.substring(0, 8) }}
                    </h3>
                    <span
                      class="agreement-requests__status-badge"
                      :style="{ backgroundColor: getStatusColor(request.status) }"
                    >
                      {{ getStatusName(request.status) }}
                    </span>
                  </div>
                  <div class="agreement-requests__card-meta">
                    <span class="agreement-requests__card-entity">
                      <FileDocumentOutline :size="14" />
                      {{ getEntityTypeName(request.entity_type) }}
                    </span>
                    <span v-if="request.project_id && getProjectName(request.project_id)" class="agreement-requests__card-entity">
                      <FolderOutline :size="14" />
                      {{ getProjectName(request.project_id) }}
                    </span>
                    <span v-if="request.created_at" class="agreement-requests__card-date">
                      <CalendarClock :size="14" />
                      {{ formatDate(request.created_at) }}
                    </span>
                    <span v-if="request.completed_at" class="agreement-requests__card-date">
                      <Check :size="14" />
                      {{ formatDate(request.completed_at) }}
                    </span>
                  </div>
                </div>
                <div class="agreement-requests__card-actions">
                  <VButton variant="secondary" size="small" @click="openRequestDetails(request)">
                    {{ contextTranslate("Details", context) }}
                  </VButton>
                </div>
              </div>

              <!-- Cached data preview -->
              <div v-if="getCachedData(request)" class="agreement-requests__preview">
                <div v-if="getCachedData(request).employee" class="agreement-requests__preview-item">
                  <span class="agreement-requests__preview-label">{{ contextTranslate("Employee", context) }}:</span>
                  <span class="agreement-requests__preview-value">{{ getCachedData(request).employee }}</span>
                </div>
                <div v-if="getCachedData(request).type" class="agreement-requests__preview-item">
                  <span class="agreement-requests__preview-label">{{ contextTranslate("Type", context) }}:</span>
                  <span class="agreement-requests__preview-value">{{ contextTranslate(getCachedData(request).type ?? '', context) }}</span>
                </div>
                <div v-if="getCachedData(request).days" class="agreement-requests__preview-item">
                  <span class="agreement-requests__preview-label">{{ contextTranslate("Days", context) }}:</span>
                  <span class="agreement-requests__preview-value">{{ getCachedData(request).days }}</span>
                </div>
                <div v-if="getCachedData(request).comment" class="agreement-requests__preview-item">
                  <span class="agreement-requests__preview-label">{{ contextTranslate("Comment", context) }}:</span>
                  <span class="agreement-requests__preview-value">{{ getCachedData(request).comment }}</span>
                </div>
              </div>
            </div>
          </div>
        </template>
          </template>
        </VScrollArea>
      </VPageContent>
    </VPageLayout>

    <!-- Request Details Sidebar -->
    <VAside :value="showDetailsModal" :width="620" @input="closeDetailsModal">
      <template #title>
        {{ contextTranslate('Request details', context) }}
      </template>

      <div v-if="selectedRequest" class="agreement-requests__sidebar-content">
        <!-- Request info -->
        <div class="agreement-requests__details-section">
          <h4 class="agreement-requests__details-title">
            {{ contextTranslate("Request information", context) }}
          </h4>
          <div class="agreement-requests__details-grid">
            <div class="agreement-requests__details-item">
              <span class="agreement-requests__details-label">{{ contextTranslate("Title", context) }}</span>
              <span class="agreement-requests__details-value">
                {{ selectedRequest.cached_title || contextTranslate("Request", context) + " #" + selectedRequest.id.substring(0, 8) }}
              </span>
            </div>
            <div class="agreement-requests__details-item">
              <span class="agreement-requests__details-label">{{ contextTranslate("Request type", context) }}</span>
              <span class="agreement-requests__details-value">{{ getEntityTypeName(selectedRequest.entity_type) }}</span>
            </div>
            <div class="agreement-requests__details-item">
              <span class="agreement-requests__details-label">{{ contextTranslate("Status", context) }}</span>
              <span
                class="agreement-requests__status-badge"
                :style="{ backgroundColor: getStatusColor(selectedRequest.status) }"
              >
                {{ getStatusName(selectedRequest.status) }}
              </span>
            </div>
            <div class="agreement-requests__details-item">
              <span class="agreement-requests__details-label">{{ contextTranslate("Created", context) }}</span>
              <span class="agreement-requests__details-value">{{ formatDateTime(selectedRequest.created_at) }}</span>
            </div>
            <div v-if="selectedRequest.project_id && getProjectName(selectedRequest.project_id)" class="agreement-requests__details-item">
              <span class="agreement-requests__details-label">{{ contextTranslate("Project", context) }}</span>
              <span class="agreement-requests__details-value">{{ getProjectName(selectedRequest.project_id) }}</span>
            </div>
            <div v-if="selectedRequest.deadline_at" class="agreement-requests__details-item">
              <span class="agreement-requests__details-label">{{ contextTranslate("Deadline", context) }}</span>
              <span class="agreement-requests__details-value" :class="{ 'agreement-requests__details-value--urgent': isDeadlineUrgent(selectedRequest.deadline_at) }">
                {{ formatDateTime(selectedRequest.deadline_at) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Cached data -->
        <div v-if="getCachedData(selectedRequest)" class="agreement-requests__details-section">
          <h4 class="agreement-requests__details-title">
            {{ contextTranslate("Request data", context) }}
          </h4>
          <div class="agreement-requests__details-grid">
            <div v-if="getCachedData(selectedRequest).employee" class="agreement-requests__details-item">
              <span class="agreement-requests__details-label">{{ contextTranslate("Employee", context) }}</span>
              <span class="agreement-requests__details-value">{{ getCachedData(selectedRequest).employee }}</span>
            </div>
            <div v-if="getCachedData(selectedRequest).type" class="agreement-requests__details-item">
              <span class="agreement-requests__details-label">{{ contextTranslate("Type", context) }}</span>
              <span class="agreement-requests__details-value">{{ getCachedData(selectedRequest).type }}</span>
            </div>
            <div v-if="getCachedData(selectedRequest).date_start && getCachedData(selectedRequest).date_end" class="agreement-requests__details-item">
              <span class="agreement-requests__details-label">{{ contextTranslate("Period", context) }}</span>
              <span class="agreement-requests__details-value">
                {{ formatDate(getCachedData(selectedRequest).date_start) }} — {{ formatDate(getCachedData(selectedRequest).date_end) }}
              </span>
            </div>
            <div v-if="getCachedData(selectedRequest).days" class="agreement-requests__details-item">
              <span class="agreement-requests__details-label">{{ contextTranslate("Days", context) }}</span>
              <span class="agreement-requests__details-value">{{ getCachedData(selectedRequest).days }}</span>
            </div>
            <div v-if="getCachedData(selectedRequest).comment" class="agreement-requests__details-item">
              <span class="agreement-requests__details-label">{{ contextTranslate("Comment", context) }}</span>
              <span class="agreement-requests__details-value">{{ getCachedData(selectedRequest).comment }}</span>
            </div>
          </div>
        </div>

        <!-- Current approval step -->
        <div v-if="selectedRequestDetails?.current_line" class="agreement-requests__details-section">
          <h4 class="agreement-requests__details-title">
            {{ contextTranslate("Current approval step", context) }}
          </h4>
          <div class="agreement-requests__step-info">
            <span class="agreement-requests__step-name">{{ selectedRequestDetails.current_line.name }}</span>
            <span v-if="selectedRequestDetails.current_line.require_all" class="agreement-requests__step-badge">
              {{ contextTranslate("All must approve", context) }}
            </span>
            <span v-else class="agreement-requests__step-badge">
              {{ contextTranslate("One is enough", context) }}
            </span>
          </div>
        </div>

        <!-- Approval progress -->
        <div v-if="selectedRequestDetails?.scheme?.lines" class="agreement-requests__details-section">
          <h4 class="agreement-requests__details-title">
            {{ contextTranslate("Approval progress", context) }}
          </h4>
          <div class="agreement-requests__progress">
            <div
              v-for="(line, index) in selectedRequestDetails.scheme.lines"
              :key="line.id"
              class="agreement-requests__progress-step"
              :class="{
                'agreement-requests__progress-step--completed': isStepCompleted(line, selectedRequestDetails),
                'agreement-requests__progress-step--current': line.id === selectedRequest.current_line_id,
                'agreement-requests__progress-step--pending': !isStepCompleted(line, selectedRequestDetails) && line.id !== selectedRequest.current_line_id
              }"
            >
              <div class="agreement-requests__progress-number">{{ index + 1 }}</div>
              <div class="agreement-requests__progress-info">
                <span class="agreement-requests__progress-name">{{ line.name }}</span>
                <span class="agreement-requests__progress-status">
                  <template v-if="isStepCompleted(line, selectedRequestDetails)">
                    <Check :size="14" /> {{ contextTranslate("Completed", context) }}
                  </template>
                  <template v-else-if="line.id === selectedRequest.current_line_id">
                    <ClockOutline :size="14" /> {{ contextTranslate("In progress", context) }}
                  </template>
                  <template v-else>
                    {{ contextTranslate("Pending", context) }}
                  </template>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- History -->
        <div v-if="requestHistory.length > 0" class="agreement-requests__details-section">
          <h4 class="agreement-requests__details-title">
            {{ contextTranslate("History", context) }}
          </h4>
          <div class="agreement-requests__history">
            <div
              v-for="historyItem in requestHistory"
              :key="historyItem.id"
              class="agreement-requests__history-item"
            >
              <div class="agreement-requests__history-icon">
                <component :is="getHistoryIcon(historyItem.event_type)" :size="16" />
              </div>
              <div class="agreement-requests__history-content">
                <span class="agreement-requests__history-event">{{ getHistoryEventText(historyItem) }}</span>
                <span v-if="historyItem.comment && historyItem.event_type !== 'status_changed'"
                      class="agreement-requests__history-comment"
                >
                  <span class="agreement-requests__history-event">{{ contextTranslate("Comment", context) }}:</span>
                  {{ historyItem.comment }}
                </span>
                <span class="agreement-requests__history-date">{{ formatDateTime(historyItem.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Action comment -->
        <div v-if="selectedRequest.status === 'pending'" class="agreement-requests__details-section">
          <div class="agreement-requests__form-group">
            <label class="agreement-requests__label">
              {{ contextTranslate("Comment", context) }}
              <span v-if="actionRequiresComment" class="agreement-requests__required">*</span>
            </label>
            <VTextArea
              v-model="actionComment"
              :placeholder="contextTranslate('Add a comment (required for reject/return)', context)"
              rows="3"
            />
          </div>
        </div>

        <!-- Action buttons -->
        <div class="agreement-requests__sidebar-actions">
          <NcButton type="tertiary" @click="closeDetailsModal">
            {{ contextTranslate("Close", context) }}
          </NcButton>
          <template v-if="selectedRequest?.status === 'pending'">
            <NcButton type="error" @click="handleReject">
              <template #icon>
                <Close :size="20" />
              </template>
              {{ contextTranslate("Reject", context) }}
            </NcButton>
            <NcButton type="secondary" @click="handleReturn">
              <template #icon>
                <Undo :size="20" />
              </template>
              {{ contextTranslate("Return", context) }}
            </NcButton>
            <NcButton type="primary" @click="handleApprove">
              <template #icon>
                <Check :size="20" />
              </template>
              {{ contextTranslate("Approve", context) }}
            </NcButton>
          </template>
        </div>
      </div>
    </VAside>

    <!-- Reject Sidebar -->
    <VAside :value="showRejectModal" :width="450" @input="closeRejectModal">
      <template #title>
        {{ contextTranslate('Reject request', context) }}
      </template>

      <div class="agreement-requests__sidebar-content">
        <p class="agreement-requests__modal-text">
          {{ contextTranslate("Please provide a reason for rejection. This comment is required.", context) }}
        </p>
        <div class="agreement-requests__form-group">
          <label class="agreement-requests__label">
            {{ contextTranslate("Reason for rejection", context) }} *
          </label>
          <VTextArea
            v-model="rejectComment"
            :placeholder="contextTranslate('Enter rejection reason', context)"
            rows="4"
          />
        </div>

        <div class="agreement-requests__sidebar-actions">
          <NcButton type="tertiary" @click="closeRejectModal">
            {{ contextTranslate("Cancel", context) }}
          </NcButton>
          <NcButton type="error" :disabled="!rejectComment?.trim()" @click="confirmReject">
            {{ contextTranslate("Reject", context) }}
          </NcButton>
        </div>
      </div>
    </VAside>

    <!-- Return Sidebar -->
    <VAside :value="showReturnModal" :width="450" @input="closeReturnModal">
      <template #title>
        {{ contextTranslate('Return for revision', context) }}
      </template>

      <div class="agreement-requests__sidebar-content">
        <p class="agreement-requests__modal-text">
          {{ contextTranslate("Please provide a reason for returning this request. The author will be able to make changes and resubmit.", context) }}
        </p>
        <div class="agreement-requests__form-group">
          <label class="agreement-requests__label">
            {{ contextTranslate("Reason for return", context) }} *
          </label>
          <VTextArea
            v-model="returnComment"
            :placeholder="contextTranslate('Enter reason for return', context)"
            rows="4"
          />
        </div>

        <div class="agreement-requests__sidebar-actions">
          <NcButton type="tertiary" @click="closeReturnModal">
            {{ contextTranslate("Cancel", context) }}
          </NcButton>
          <NcButton type="warning" :disabled="!returnComment?.trim()" @click="confirmReturn">
            {{ contextTranslate("Return for revision", context) }}
          </NcButton>
        </div>
      </div>
    </VAside>
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb, NcButton } from "@nextcloud/vue";
import { format } from "date-fns";

import CheckDecagram from "vue-material-design-icons/CheckDecagram.vue";
import Refresh from "vue-material-design-icons/Refresh.vue";
import CheckCircleOutline from "vue-material-design-icons/CheckCircleOutline.vue";
import FileDocumentOutline from "vue-material-design-icons/FileDocumentOutline.vue";
import CalendarClock from "vue-material-design-icons/CalendarClock.vue";
import AlertCircleOutline from "vue-material-design-icons/AlertCircleOutline.vue";
import Check from "vue-material-design-icons/Check.vue";
import Close from "vue-material-design-icons/Close.vue";
import Undo from "vue-material-design-icons/Undo.vue";
import ClockOutline from "vue-material-design-icons/ClockOutline.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import ArrowRight from "vue-material-design-icons/ArrowRight.vue";
import FolderOutline from "vue-material-design-icons/FolderOutline.vue";

import { VPage, VPageLayout, VPageContent, TimeTrackingAside } from "@/widgets";
import {
  VToolbar,
  VScrollArea,
  VLoader,
  VButton,
  VTextArea,
  VAside,
} from "@/shared/components";

import {
  fetchMyRequests,
  fetchMyRequestsHistory,
  fetchRequest,
  fetchRequestHistory,
  approveRequest,
  rejectRequest,
  returnRequest,
  fetchOptions,
  fetchUsersForApprover,
  fetchProjectsForFilter,
} from "@/entities/agreement/api";

import { REQUEST_STATUS } from "@/entities/agreement/constants";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { timeTrackingPageMixin } from "@/shared/lib/mixins/timeTrackingPageMixin";
import { initFilterDescriptor } from "@/shared/lib/helpers/filter";
import { redirectNotFoundPage } from "@/shared/lib/helpers/navigation";

export default {
  name: "AgreementRequestsPage",
  mixins: [contextualTranslationsMixin, timeTrackingPageMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcButton,
    CheckDecagram,
    Refresh,
    CheckCircleOutline,
    FileDocumentOutline,
    CalendarClock,
    AlertCircleOutline,
    Check,
    Close,
    Undo,
    ClockOutline,
    Plus,
    ArrowRight,
    FolderOutline,
    VPage,
    VPageLayout,
    VPageContent,
    TimeTrackingAside,
    VToolbar,
    VScrollArea,
    VLoader,
    VButton,
    VTextArea,
    VAside,
  },
  props: {
    additionalProps: {
      type: Object,
      default: () => ({}),
    },
  },
  data: () => ({
    context: "agreement",
    isLoading: false,
    requests: [],

    // Tabs
    activeTab: "active",
    historyRequests: [],

    // Filter
    filterDescriptor: [],
    employees: [],
    projectsMap: {},

    // Options from backend
    statusOptions: [],
    entityTypeOptions: [],
    actionTypeOptions: [],
    eventTypeOptions: {},

    // Details modal
    showDetailsModal: false,
    selectedRequest: null,
    selectedRequestDetails: null,
    requestHistory: [],
    actionComment: "",

    // Reject modal
    showRejectModal: false,
    requestToReject: null,
    rejectComment: "",

    // Return modal
    showReturnModal: false,
    requestToReturn: null,
    returnComment: "",
  }),
  computed: {
    breadcrumbs() {
      return this.additionalProps.breadcrumbs;
    },
    actionRequiresComment() {
      return false; // For approve, comment is optional
    },
  },
  methods: {
    async handleFetchData({ date_from, date_to, filters = {} }) {
      try {
        this.isLoading = true;

        const params = { date_from, date_to, ...filters };

        const [requestsResponse, historyResponse] = await Promise.all([
          fetchMyRequests(params),
          fetchMyRequestsHistory(params),
        ]);

        this.requests = requestsResponse.data || [];
        this.historyRequests = historyResponse.data || [];
      } catch (e) {
        console.error("Failed to load requests:", e);
        redirectNotFoundPage(this.$router);
      } finally {
        this.isLoading = false;
      }
    },

    switchTab(tab) {
      this.activeTab = tab;
    },

    initFilterDescriptor() {
      const filters = [
        {
          key: "entity_type",
          type: "select",
          multiple: false,
          options: this.entityTypeOptions,
          value: null,
          placeholder: t("done", "Request type"),
        },
        {
          key: "created_by",
          type: "select",
          multiple: true,
          options: this.employees.map(emp => ({
            id: emp.id,
            name: emp.name || "",
          })),
          value: [],
          placeholder: t("done", "Employee"),
          userSelect: true,
        },
        {
          key: "project_ids",
          type: "select",
          multiple: true,
          fetchOptionsFunction: async () => {
            const { data } = await fetchProjectsForFilter();
            return { data: data || [] };
          },
          value: [],
          placeholder: t("done", "Project"),
        },
      ];

      this.filterDescriptor = initFilterDescriptor(filters);
    },

    async loadEmployees() {
      try {
        const promises = [fetchUsersForApprover()];
        const [{ data: data }] = await Promise.all(promises);

        if (data?.ocs?.meta?.status === 'failure') {
          throw new Error(data.ocs.meta.message);
        }

        this.employees = data || {};
      } catch (e) {
        console.error("Failed to load employees:", e);
        this.employees = [];
        redirectNotFoundPage(this.$router);
      }
    },

    async loadProjects() {
      try {
        const { data } = await fetchProjectsForFilter();
        const projects = data || [];
        this.projectsMap = Object.fromEntries(projects.map(p => [p.id, p.name]));
      } catch (e) {
        this.projectsMap = {};
      }
    },

    getProjectName(projectId) {
      return this.projectsMap[projectId] || null;
    },

    async loadOptions() {
      try {
        const response = await fetchOptions();
        const options = response.data || {};
        this.statusOptions = options.requestStatuses || [];
        this.entityTypeOptions = options.entityTypes || [];
        this.actionTypeOptions = options.actionTypes || [];
        this.eventTypeOptions = options.eventTypes || {};
      } catch (e) {
        console.error("Failed to load options:", e);
        redirectNotFoundPage(this.$router);
      }
    },

    getStatusColor(status) {
      const option = this.statusOptions.find(o => o.id === status);
      return option?.color || "#9E9E9E";
    },

    getStatusName(status) {
      const option = this.statusOptions.find(o => o.id === status);
      return option?.name || status;
    },

    getEntityTypeName(entityType) {
      const option = this.entityTypeOptions.find(o => o.id === entityType);
      let result = option?.name || entityType;
      return this.contextTranslate(result, this.context);
    },

    getActionTypeName(actionType) {
      const option = this.actionTypeOptions.find(o => o.id === actionType);
      let result = option?.name || actionType;
      return this.contextTranslate(result, this.context);
    },

    formatDate(dateString) {
      if (!dateString) return "";
      try {
        return format(new Date(dateString), "dd.MM.yyyy");
      } catch {
        return dateString;
      }
    },

    formatDateTime(dateString) {
      if (!dateString) return "";
      try {
        return format(new Date(dateString), "dd.MM.yyyy HH:mm");
      } catch {
        return dateString;
      }
    },

    isDeadlineUrgent(deadlineAt) {
      if (!deadlineAt) return false;
      const deadline = new Date(deadlineAt);
      const now = new Date();
      const diffDays = (deadline - now) / (1000 * 60 * 60 * 24);
      return diffDays <= 1;
    },

    getCachedData(request) {
      const cachedData = request?.cached_data;
      if (!cachedData) return null;
      if (typeof cachedData === "object") return cachedData;
      try {
        return JSON.parse(cachedData);
      } catch {
        return null;
      }
    },

    isStepCompleted(line, details) {
      if (!details?.actions) return false;
      // A step is completed if there are approved actions for it
      return details.actions.some(
        action => action.line_id === line.id && action.action === "approved"
      );
    },

    getHistoryIcon(eventType) {
      switch (eventType) {
        case "created":
          return Plus;
        case "action_performed":
          return Check;
        case "status_changed":
          return ArrowRight;
        case "line_changed":
          return ArrowRight;
        default:
          return ClockOutline;
      }
    },

    getEventTypeName(eventType) {
      let result = this.eventTypeOptions[eventType] || eventType;
      return this.contextTranslate(result, this.context);
    },

    getHistoryEventText(historyItem) {
      const eventLabel = this.getEventTypeName(historyItem.event_type);

      switch (historyItem.event_type) {
        case "created":
          return eventLabel;
        case "action_performed":
          if (historyItem.action) {
            return `${eventLabel}: ${this.getActionTypeName(historyItem.action)}`;
          }
          return eventLabel;
        case "status_changed":
          if (historyItem.status_before && historyItem.status_after) {
            return `${eventLabel}: ${this.getStatusName(historyItem.status_before)} → ${this.getStatusName(historyItem.status_after)}`;
          }
          if (historyItem.status_after) {
            return `${eventLabel}: ${this.getStatusName(historyItem.status_after)}`;
          }
          return eventLabel;
        case "line_changed":
          return eventLabel;
        default:
          return eventLabel;
      }
    },

    // Details modal
    async openRequestDetails(request) {
      this.selectedRequest = request;
      this.actionComment = "";
      this.showDetailsModal = true;

      try {
        const [detailsResponse, historyResponse] = await Promise.all([
          fetchRequest(request.id),
          fetchRequestHistory(request.id),
        ]);

        this.selectedRequestDetails = detailsResponse.data;
        this.requestHistory = historyResponse.data || [];
      } catch (e) {
        console.error("Failed to load request details:", e);
        redirectNotFoundPage(this.$router);
      }
    },

    closeDetailsModal() {
      this.showDetailsModal = false;
      this.selectedRequest = null;
      this.selectedRequestDetails = null;
      this.requestHistory = [];
      this.actionComment = "";
    },

    async handleApprove() {
      if (!this.selectedRequest) return;

      try {
        await approveRequest(this.selectedRequest.id, this.actionComment || null);
        this.closeDetailsModal();
        await this.fetchDataWithFilters();
      } catch (e) {
        console.error("Failed to approve request:", e);
      }
    },

    async handleReject() {
      if (!this.actionComment?.trim()) {
        this.rejectComment = "";
        this.requestToReject = this.selectedRequest;
        this.showRejectModal = true;
        return;
      }

      try {
        await rejectRequest(this.selectedRequest.id, this.actionComment);
        this.closeDetailsModal();
        await this.fetchDataWithFilters();
      } catch (e) {
        console.error("Failed to reject request:", e);
      }
    },

    async handleReturn() {
      if (!this.actionComment?.trim()) {
        this.returnComment = "";
        this.requestToReturn = this.selectedRequest;
        this.showReturnModal = true;
        return;
      }

      try {
        await returnRequest(this.selectedRequest.id, this.actionComment);
        this.closeDetailsModal();
        await this.fetchDataWithFilters();
      } catch (e) {
        console.error("Failed to return request:", e);
      }
    },

    // Quick actions
    async quickApprove(request) {
      try {
        await approveRequest(request.id, null);
        await this.fetchDataWithFilters();
      } catch (e) {
        console.error("Failed to approve request:", e);
      }
    },

    // Reject modal
    openRejectModal(request) {
      this.requestToReject = request;
      this.rejectComment = "";
      this.showRejectModal = true;
    },

    closeRejectModal() {
      this.showRejectModal = false;
      this.requestToReject = null;
      this.rejectComment = "";
    },

    async confirmReject() {
      if (!this.requestToReject || !this.rejectComment?.trim()) return;

      try {
        await rejectRequest(this.requestToReject.id, this.rejectComment);
        this.closeRejectModal();
        this.closeDetailsModal();
        await this.fetchDataWithFilters();
      } catch (e) {
        console.error("Failed to reject request:", e);
      }
    },

    // Return modal
    openReturnModal(request) {
      this.requestToReturn = request;
      this.returnComment = "";
      this.showReturnModal = true;
    },

    closeReturnModal() {
      this.showReturnModal = false;
      this.requestToReturn = null;
      this.returnComment = "";
    },

    async confirmReturn() {
      if (!this.requestToReturn || !this.returnComment?.trim()) return;

      try {
        await returnRequest(this.requestToReturn.id, this.returnComment);
        this.closeReturnModal();
        this.closeDetailsModal();
        await this.fetchDataWithFilters();
      } catch (e) {
        console.error("Failed to return request:", e);
      }
    },

    // Override init from mixin
    async init() {
      try {
        // Load dictionaries in parallel
        await Promise.all([
          this.loadOptions(),
          this.loadEmployees(),
          this.loadProjects(),
        ]);

        // Initialize filter descriptor AFTER loading data
        this.initFilterDescriptor();

        // Set range type from localStorage if not in query
        const query = this.$route.query;
        if (!query.active_range_type) {
          this.setActiveRangeTypeFromLocalStorage();
        }

        // Fetch data with filters from query
        await this.initFetchDataWithFilters();
      } catch (e) {
        console.error("Failed to initialize:", e);
      }
    },
  },
  mounted() {
    this.init();
  },
};
</script>

<style scoped>
.agreement-requests__content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding: 24px;
  overflow-y: auto;
}

.agreement-requests__loader {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
}

.agreement-requests__header {
  margin-bottom: 8px;
}

.agreement-requests__title {
  font-size: 24px;
  font-weight: 600;
  margin: 0 0 8px 0;
  color: var(--color-main-text);
}

.agreement-requests__description {
  font-size: 14px;
  color: var(--color-text-maxcontrast);
  margin: 0;
}

.agreement-requests__tabs {
  display: flex;
  gap: 0;
  border-bottom: 2px solid var(--color-border);
  margin-bottom: 8px;
}

.agreement-requests__tab {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  background: none;
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  cursor: pointer;
  transition: color 0.15s, border-color 0.15s;
}

.agreement-requests__tab:hover {
  color: var(--color-main-text);
}

.agreement-requests__tab--active {
  color: var(--color-primary-element);
  border-bottom-color: var(--color-primary-element);
}

.agreement-requests__tab-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: 10px;
  background-color: var(--color-primary-element);
  color: var(--color-primary-element-text);
  font-size: 12px;
  font-weight: 600;
}

.agreement-requests__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  text-align: center;
}

.agreement-requests__empty-icon {
  color: var(--color-success);
  margin-bottom: 16px;
}

.agreement-requests__empty-text {
  font-size: 14px;
  color: var(--color-text-maxcontrast);
  margin: 0;
}

.agreement-requests__list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.agreement-requests__card {
  background-color: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  overflow: hidden;
}

.agreement-requests__card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 16px;
}

.agreement-requests__card-info {
  flex: 1;
}

.agreement-requests__card-title-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.agreement-requests__card-title {
  font-size: 16px;
  font-weight: 600;
  margin: 0;
  color: var(--color-main-text);
}

.agreement-requests__status-badge {
  font-size: 11px;
  font-weight: 500;
  padding: 2px 8px;
  border-radius: var(--border-radius);
  color: white;
}

.agreement-requests__card-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.agreement-requests__card-entity,
.agreement-requests__card-date,
.agreement-requests__card-deadline {
  display: flex;
  align-items: center;
  gap: 4px;
}

.agreement-requests__card-deadline--urgent {
  color: var(--color-error);
  font-weight: 500;
}

.agreement-requests__preview {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  padding: 12px 16px;
  background-color: var(--color-background-dark);
  border-top: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);
}

.agreement-requests__preview-item {
  font-size: 13px;
}

.agreement-requests__preview-label {
  color: var(--color-text-maxcontrast);
  margin-right: 4px;
}

.agreement-requests__preview-value {
  color: var(--color-main-text);
  font-weight: 500;
}

.agreement-requests__quick-actions {
  display: flex;
  gap: 8px;
  padding: 12px 16px;
}

.agreement-requests__sidebar-content {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 16px;
  overflow-y: auto;
}

.agreement-requests__sidebar-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: auto;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.agreement-requests__details-section {
  margin-bottom: 24px;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--color-border);
}

.agreement-requests__details-section:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.agreement-requests__details-title {
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 12px 0;
  color: var(--color-main-text);
}

.agreement-requests__details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
}

.agreement-requests__details-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.agreement-requests__details-label {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.agreement-requests__details-value {
  font-size: 14px;
  color: var(--color-main-text);
}

.agreement-requests__details-value--urgent {
  color: var(--color-error);
  font-weight: 500;
}

.agreement-requests__step-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.agreement-requests__step-name {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-main-text);
}

.agreement-requests__step-badge {
  font-size: 11px;
  padding: 2px 8px;
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius);
  color: var(--color-text-maxcontrast);
}

.agreement-requests__progress {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.agreement-requests__progress-step {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius);
}

.agreement-requests__progress-step--current {
  background-color: var(--color-primary-element-light);
  border: 1px solid var(--color-primary-element);
}

.agreement-requests__progress-number {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--color-text-maxcontrast);
  color: white;
  border-radius: 50%;
  font-size: 12px;
  font-weight: 600;
}

.agreement-requests__progress-step--current .agreement-requests__progress-number {
  background-color: var(--color-primary-element);
}

.agreement-requests__progress-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.agreement-requests__progress-name {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-main-text);
}

.agreement-requests__progress-status {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.agreement-requests__history {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.agreement-requests__history-item {
  display: flex;
  gap: 12px;
  padding: 12px;
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius);
}

.agreement-requests__history-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--color-background-hover);
  border-radius: 50%;
  color: var(--color-text-maxcontrast);
}

.agreement-requests__history-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.agreement-requests__history-event {
  font-size: 14px;
  color: var(--color-main-text);
}

.agreement-requests__history-comment {
  font-size: 13px;
  color: var(--color-text-maxcontrast);
}

.agreement-requests__history-date {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.agreement-requests__modal {
  padding: 16px 0;
}

.agreement-requests__modal-text {
  font-size: 14px;
  color: var(--color-main-text);
  margin: 0 0 16px 0;
}

.agreement-requests__form-group {
  margin-bottom: 16px;
}

.agreement-requests__label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 4px;
  color: var(--color-main-text);
}

.agreement-requests__required {
  color: var(--color-error);
}
</style>
