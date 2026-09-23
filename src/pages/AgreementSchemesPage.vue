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
        <VButton variant="primary" @click="openCreateSchemeModal">
          <template #icon>
            <Plus :size="20" />
          </template>
          {{ contextTranslate("Add scheme", context) }}
        </VButton>
      </template>
    </VToolbar>

    <!-- Main content -->
    <VScrollArea class="agreement-schemes__content">
      <!-- Loading state -->
      <div v-if="isLoading" class="agreement-schemes__loader">
        <VLoader />
      </div>

      <template v-else>
        <!-- Page header -->
        <div class="agreement-schemes__header">
          <h1 class="agreement-schemes__title">
            {{ contextTranslate("Agreement schemes", context) }}
          </h1>
          <p class="agreement-schemes__description">
            {{ contextTranslate("Configure approval workflows for different entity types", context) }}
          </p>
        </div>

        <!-- No schemes -->
        <div v-if="schemes.length === 0" class="agreement-schemes__empty">
          <FileDocumentOutline :size="48" class="agreement-schemes__empty-icon" />
          <p class="agreement-schemes__empty-text">
            {{ contextTranslate("No agreement schemes configured yet", context) }}
          </p>
          <VButton variant="primary" @click="openCreateSchemeModal">
            {{ contextTranslate("Create first scheme", context) }}
          </VButton>
        </div>

        <!-- Schemes list -->
        <div v-else class="agreement-schemes__list">
          <div
            v-for="scheme in schemes"
            :key="scheme.id"
            class="agreement-schemes__card"
            :class="{ 'agreement-schemes__card--inactive': !scheme.is_active }"
          >
            <div class="agreement-schemes__card-header">
              <div class="agreement-schemes__card-info">
                <div class="agreement-schemes__card-title-row">
                  <h3 class="agreement-schemes__card-title">{{ scheme.name }}</h3>
                  <span
                    v-if="scheme.is_default"
                    class="agreement-schemes__badge agreement-schemes__badge--default"
                  >
                    {{ contextTranslate("Default", context) }}
                  </span>
                  <span
                    v-if="!scheme.is_active"
                    class="agreement-schemes__badge agreement-schemes__badge--inactive"
                  >
                    {{ contextTranslate("Inactive", context) }}
                  </span>
                </div>
                <p v-if="scheme.description" class="agreement-schemes__card-description">
                  {{ scheme.description }}
                </p>
                <div class="agreement-schemes__card-meta">
                  <span class="agreement-schemes__card-entity">
                    {{ getEntityTypeName(scheme.entity_type) }}
                  </span>
                  <span class="agreement-schemes__card-lines">
                    {{ n("done", "%n step", "%n steps", scheme.lines?.length || 0) }}
                  </span>
                </div>
              </div>
              <div class="agreement-schemes__card-actions">
                <NcButton
                  type="tertiary"
                  :aria-label="contextTranslate('Edit', context)"
                  @click="editScheme(scheme)"
                >
                  <template #icon>
                    <Pencil :size="20" />
                  </template>
                </NcButton>
                <NcButton
                  v-if="!scheme.is_default"
                  type="tertiary"
                  :aria-label="contextTranslate('Set as default', context)"
                  @click="setAsDefault(scheme)"
                >
                  <template #icon>
                    <Star :size="20" />
                  </template>
                </NcButton>
                <NcButton
                  type="tertiary"
                  :aria-label="contextTranslate('Delete', context)"
                  @click="confirmDeleteScheme(scheme)"
                >
                  <template #icon>
                    <Delete :size="20" />
                  </template>
                </NcButton>
              </div>
            </div>

            <!-- Lines (expandable) -->
            <div v-if="expandedScheme === scheme.id" class="agreement-schemes__lines">
              <div class="agreement-schemes__lines-header">
                <h4 class="agreement-schemes__lines-title">
                  {{ contextTranslate("Approval steps", context) }}
                </h4>
                <VButton variant="secondary" size="small" @click="openCreateLineModal(scheme)">
                  <template #icon>
                    <Plus :size="16" />
                  </template>
                  {{ contextTranslate("Add step", context) }}
                </VButton>
              </div>

              <div v-if="!scheme.lines || scheme.lines.length === 0" class="agreement-schemes__lines-empty">
                {{ contextTranslate("No approval steps configured", context) }}
              </div>

              <draggable
                v-else
                v-model="scheme.lines"
                handle=".agreement-schemes__line-drag"
                @end="onLineReorder(scheme)"
              >
                <div
                  v-for="(line, lineIndex) in scheme.lines"
                  :key="line.id"
                  class="agreement-schemes__line"
                >
                  <div class="agreement-schemes__line-drag">
                    <DragVertical :size="20" />
                  </div>
                  <div class="agreement-schemes__line-order">{{ lineIndex + 1 }}</div>
                  <div class="agreement-schemes__line-info">
                    <span class="agreement-schemes__line-name">{{ line.name }}</span>
                    <div class="agreement-schemes__line-settings">
                      <span v-if="line.require_all" class="agreement-schemes__line-badge">
                        {{ contextTranslate("All must approve", context) }}
                      </span>
                      <span v-else class="agreement-schemes__line-badge">
                        {{ contextTranslate("One is enough", context) }}
                      </span>
                      <span v-if="line.deadline_enabled" class="agreement-schemes__line-badge">
                        {{ line.deadline_days }} {{ contextTranslate("days", context) }}
                      </span>
                    </div>
                    <div class="agreement-schemes__line-approvers">
                      <span
                        v-for="approver in line.approvers"
                        :key="approver.id"
                        class="agreement-schemes__approver"
                      >
                        <Account v-if="approver.approver_type === 'user'" :size="14" />
                        <AccountGroup v-else :size="14" />
                        {{ approver.name || approver.user_id || approver.role_id }}
                      </span>
                      <VButton
                        variant="tertiary-no-background"
                        size="small"
                        @click="openApproversModal(scheme, line)"
                      >
                        <template #icon>
                          <Plus :size="14" />
                        </template>
                        {{ contextTranslate("Add", context) }}
                      </VButton>
                    </div>
                  </div>
                  <div class="agreement-schemes__line-actions">
                    <NcButton
                      type="tertiary"
                      :aria-label="contextTranslate('Edit', context)"
                      @click="editLine(scheme, line)"
                    >
                      <template #icon>
                        <Pencil :size="18" />
                      </template>
                    </NcButton>
                    <NcButton
                      type="tertiary"
                      :aria-label="contextTranslate('Delete', context)"
                      @click="confirmDeleteLine(scheme, line)"
                    >
                      <template #icon>
                        <Delete :size="18" />
                      </template>
                    </NcButton>
                  </div>
                </div>
              </draggable>

              <!-- Assignments section -->
              <div class="agreement-schemes__assignments">
                <div class="agreement-schemes__assignments-header">
                  <h4 class="agreement-schemes__assignments-title">
                    {{ contextTranslate("Custom assignments", context) }}
                  </h4>
                  <VButton variant="secondary" size="small" @click="openAssignmentsModal(scheme)">
                    <template #icon>
                      <Plus :size="16" />
                    </template>
                    {{ contextTranslate("Add assignment", context) }}
                  </VButton>
                </div>

                <p class="agreement-schemes__assignments-hint">
                  {{ contextTranslate("Assign this scheme to specific users or projects to override the default scheme", context) }}
                </p>

                <div v-if="!scheme.assignments || scheme.assignments.length === 0" class="agreement-schemes__assignments-empty">
                  {{ contextTranslate("No custom assignments. Default scheme will be used.", context) }}
                </div>

                <div v-else class="agreement-schemes__assignments-list">
                  <div
                    v-for="assignment in scheme.assignments"
                    :key="assignment.id"
                    class="agreement-schemes__assignment-item"
                  >
                    <Account v-if="assignment.sub_entity_type === 'user'" :size="18" />
                    <Book v-else :size="18" />
                    <div class="agreement-schemes__assignment-info">
                      <span class="agreement-schemes__assignment-name">{{ assignment.sub_entity_name }}</span>
                      <span class="agreement-schemes__assignment-type">
                        {{ assignment.sub_entity_type === 'user' ? contextTranslate('User', context) : contextTranslate('Project', context) }}
                      </span>
                    </div>
                    <NcButton
                      type="tertiary"
                      :aria-label="contextTranslate('Remove', context)"
                      @click="confirmDeleteAssignment(assignment)"
                    >
                      <template #icon>
                        <Close :size="16" />
                      </template>
                    </NcButton>
                  </div>
                </div>
              </div>
            </div>

            <!-- Expand/collapse button -->
            <button
              class="agreement-schemes__expand-btn"
              @click="toggleExpand(scheme.id)"
            >
              <ChevronDown
                :size="20"
                :class="{ 'agreement-schemes__expand-icon--rotated': expandedScheme === scheme.id }"
              />
              {{
                expandedScheme === scheme.id
                  ? contextTranslate("Hide steps", context)
                  : contextTranslate("Show steps", context)
              }}
            </button>
          </div>
        </div>
      </template>
    </VScrollArea>

    <!-- Create/Edit Scheme Sidebar -->
    <VAside :value="showSchemeModal" :width="500" @input="closeSchemeModal">
      <template #title>
        {{ editingScheme ? contextTranslate('Edit scheme', context) : contextTranslate('Create scheme', context) }}
      </template>

      <div class="agreement-schemes__sidebar-content">
        <div class="agreement-schemes__form-group">
          <label class="agreement-schemes__label">
            {{ contextTranslate("Name", context) }} *
          </label>
          <VTextField v-model="schemeForm.name" :placeholder="contextTranslate('Scheme name', context)" />
        </div>

        <div class="agreement-schemes__form-group">
          <label class="agreement-schemes__label">
            {{ contextTranslate("Description", context) }}
          </label>
          <VTextArea v-model="schemeForm.description" :placeholder="contextTranslate('Optional description', context)" />
        </div>

        <VDropdown
          :value="schemeForm.entity_type"
          :options="entityTypeOptions"
          :label="contextTranslate('Entity type', context)"
          :required="true"
          :placeholder="contextTranslate('Select entity type', context)"
          @input="schemeForm.entity_type = $event"
        />

        <div class="agreement-schemes__form-row">
          <div class="agreement-schemes__form-group agreement-schemes__form-group--switch">
            <VSwitch v-model="schemeForm.is_active" />
            <label class="agreement-schemes__label">
              {{ contextTranslate("Scheme active", context) }}
            </label>
          </div>

          <div class="agreement-schemes__form-group agreement-schemes__form-group--switch">
            <VSwitch v-model="schemeForm.is_default" />
            <label class="agreement-schemes__label">
              {{ contextTranslate("Default for entity type", context) }}
            </label>
          </div>
        </div>

        <div class="agreement-schemes__sidebar-actions">
          <NcButton type="tertiary" @click="closeSchemeModal">
            {{ contextTranslate("Cancel", context) }}
          </NcButton>
          <NcButton type="primary" :disabled="!isSchemeFormValid" @click="saveScheme">
            {{ contextTranslate("Save", context) }}
          </NcButton>
        </div>
      </div>
    </VAside>

    <!-- Create/Edit Line Sidebar -->
    <VAside :value="showLineModal" :width="450" @input="closeLineModal">
      <template #title>
        {{ editingLine ? contextTranslate('Edit step', context) : contextTranslate('Add step', context) }}
      </template>

      <div class="agreement-schemes__sidebar-content">
        <div class="agreement-schemes__form-group">
          <label class="agreement-schemes__label">
            {{ contextTranslate("Name", context) }} *
          </label>
          <VTextField v-model="lineForm.name" :placeholder="contextTranslate('Step name', context)" />
        </div>

        <div class="agreement-schemes__form-group agreement-schemes__form-group--switch">
          <VSwitch v-model="lineForm.require_all" />
          <label class="agreement-schemes__label">
            {{ contextTranslate("Require all approvers", context) }}
          </label>
        </div>

        <div class="agreement-schemes__form-group agreement-schemes__form-group--switch">
          <VSwitch v-model="lineForm.deadline_enabled" />
          <label class="agreement-schemes__label">
            {{ contextTranslate("Enable deadline", context) }}
          </label>
        </div>

        <div v-if="lineForm.deadline_enabled" class="agreement-schemes__form-group">
          <label class="agreement-schemes__label">
            {{ contextTranslate("Days until auto-rejection", context) }}
          </label>
          <VTextField v-model.number="lineForm.deadline_days" type="number" min="1" />
        </div>

        <div class="agreement-schemes__sidebar-actions">
          <NcButton type="tertiary" @click="closeLineModal">
            {{ contextTranslate("Cancel", context) }}
          </NcButton>
          <NcButton type="primary" :disabled="!isLineFormValid" @click="saveLine">
            {{ contextTranslate("Save", context) }}
          </NcButton>
        </div>
      </div>
    </VAside>

    <!-- Approvers Sidebar -->
    <VAside :value="showApproversModal" :width="550" @input="closeApproversModal">
      <template #title>
        {{ contextTranslate('Manage approvers', context) }}
      </template>

      <div class="agreement-schemes__sidebar-content">
        <div class="agreement-schemes__approvers-section">
          <h4 class="agreement-schemes__approvers-title">
            {{ contextTranslate("Add approver", context) }}
          </h4>

          <div class="agreement-schemes__form-row">
            <VDropdown
              :value="approverForm.type"
              :options="approverTypeOptions"
              :label="contextTranslate('Type', context)"
              @input="approverForm.type = $event"
            />

            <VDropdown
              v-if="approverForm.type?.id === 'user'"
              :value="approverForm.user"
              :options="usersForApprover"
              :label="contextTranslate('User', context)"
              :placeholder="contextTranslate('Select user', context)"
              @input="approverForm.user = $event"
            />

            <VDropdown
              v-if="approverForm.type?.id === 'role'"
              :value="approverForm.role"
              :options="rolesForApprover"
              :label="contextTranslate('Role', context)"
              :placeholder="contextTranslate('Select role', context)"
              @input="approverForm.role = $event"
            />

            <VButton
              variant="primary"
              :disabled="!canAddApprover"
              @click="addApprover"
            >
              {{ contextTranslate("Add", context) }}
            </VButton>
          </div>
        </div>

        <div class="agreement-schemes__approvers-list">
          <h4 class="agreement-schemes__approvers-title">
            {{ contextTranslate("Current approvers", context) }}
          </h4>

          <div v-if="!currentLineApprovers.length" class="agreement-schemes__approvers-empty">
            {{ contextTranslate("No approvers added yet", context) }}
          </div>

          <div
            v-for="approver in currentLineApprovers"
            :key="approver.id"
            class="agreement-schemes__approver-item"
          >
            <Account v-if="approver.approver_type === 'user'" :size="20" />
            <AccountGroup v-else :size="20" />
            <span class="agreement-schemes__approver-name">
              {{ approver.name || approver.user_id || approver.role_id }}
            </span>
            <span class="agreement-schemes__approver-type">
              {{ approver.approver_type === 'user' ? contextTranslate('User', context) : contextTranslate('Role', context) }}
            </span>
            <NcButton
              type="tertiary"
              :aria-label="contextTranslate('Remove', context)"
              @click="removeApprover(approver)"
            >
              <template #icon>
                <Close :size="18" />
              </template>
            </NcButton>
          </div>
        </div>

        <div class="agreement-schemes__sidebar-actions">
          <NcButton type="primary" @click="closeApproversModal">
            {{ contextTranslate("Done", context) }}
          </NcButton>
        </div>
      </div>
    </VAside>

    <!-- Delete Confirmation Sidebar -->
    <VAside :value="showDeleteConfirm" :width="400" @input="closeDeleteConfirm">
      <template #title>
        {{ contextTranslate('Confirm deletion', context) }}
      </template>

      <div class="agreement-schemes__sidebar-content">
        <p class="agreement-schemes__confirm-message">{{ deleteConfirmMessage }}</p>

        <div class="agreement-schemes__sidebar-actions">
          <NcButton type="tertiary" @click="closeDeleteConfirm">
            {{ contextTranslate("Cancel", context) }}
          </NcButton>
          <NcButton type="error" @click="executeDelete">
            {{ contextTranslate("Delete", context) }}
          </NcButton>
        </div>
      </div>
    </VAside>

    <!-- Assignments Sidebar -->
    <VAside :value="showAssignmentsModal" :width="500" @input="closeAssignmentsModal">
      <template #title>
        {{ contextTranslate('Assign scheme', context) }}
      </template>

      <div class="agreement-schemes__sidebar-content">
        <p class="agreement-schemes__assignments-modal-hint">
          {{ contextTranslate("Assign this scheme to specific users or projects. They will use this scheme instead of the default one.", context) }}
        </p>

        <VDropdown
          :value="assignmentForm.subEntityType"
          :options="subEntityTypeOptions"
          :label="contextTranslate('Assignment type', context)"
          :required="true"
          @input="handleSubEntityTypeChange"
        />

        <VDropdown
          v-if="assignmentForm.subEntityType"
          :value="assignmentForm.subEntity"
          :options="entitiesForAssignment"
          :label="assignmentForm.subEntityType?.id === 'user' ? contextTranslate('User', context) : contextTranslate('Project', context)"
          :required="true"
          :placeholder="contextTranslate('Select...', context)"
          @input="assignmentForm.subEntity = $event"
        />

        <div class="agreement-schemes__sidebar-actions">
          <NcButton type="tertiary" @click="closeAssignmentsModal">
            {{ contextTranslate("Cancel", context) }}
          </NcButton>
          <NcButton
            type="primary"
            :disabled="!canAddAssignment"
            @click="addAssignment"
          >
            {{ contextTranslate("Add assignment", context) }}
          </NcButton>
        </div>
      </div>
    </VAside>
  </VPage>
</template>

<script>
import {NcBreadcrumb, NcBreadcrumbs, NcButton} from "@nextcloud/vue";
import { n } from "@nextcloud/l10n";
import draggable from "vuedraggable";

import CheckDecagram from "vue-material-design-icons/CheckDecagram.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";
import Delete from "vue-material-design-icons/Delete.vue";
import Star from "vue-material-design-icons/Star.vue";
import ChevronDown from "vue-material-design-icons/ChevronDown.vue";
import DragVertical from "vue-material-design-icons/DragVertical.vue";
import Account from "vue-material-design-icons/Account.vue";
import AccountGroup from "vue-material-design-icons/AccountGroup.vue";
import Close from "vue-material-design-icons/Close.vue";
import FileDocumentOutline from "vue-material-design-icons/FileDocumentOutline.vue";
import Book from "vue-material-design-icons/Book.vue";

import {VPage} from "@/widgets";
import {
  VAside,
  VButton,
  VDropdown,
  VLoader,
  VScrollArea,
  VSwitch,
  VTextArea,
  VTextField,
  VToolbar,
} from "@/shared/components";

import {
  addApprover,
  createAssignment,
  createScheme,
  createSchemeLine,
  deleteAssignment,
  deleteScheme,
  deleteSchemeLine,
  fetchEntitiesForAssignment,
  fetchOptions,
  fetchRolesForApprover,
  fetchScheme,
  fetchSchemeAssignments,
  fetchSchemes,
  fetchUsersForApprover,
  removeApprover as removeApproverApi,
  setDefaultScheme,
  updateScheme,
  updateSchemeLine,
} from "@/entities/agreement/api";

import {contextualTranslationsMixin} from "@/shared/lib/mixins/contextualTranslationsMixin";
import { redirectNotFoundPage } from "@/shared/lib/helpers/navigation";

export default {
  name: "AgreementSchemesPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcButton,
    draggable,
    CheckDecagram,
    Plus,
    Pencil,
    Delete,
    Star,
    ChevronDown,
    DragVertical,
    Account,
    AccountGroup,
    Close,
    FileDocumentOutline,
    Book,
    VPage,
    VToolbar,
    VScrollArea,
    VLoader,
    VButton,
    VTextField,
    VTextArea,
    VSwitch,
    VAside,
    VDropdown,
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
    schemes: [],
    expandedScheme: null,

    // Options from backend
    entityTypeOptions: [],
    approverTypeOptions: [],

    // Users and roles for approver selection
    usersForApprover: [],
    rolesForApprover: [],

    // Scheme modal
    showSchemeModal: false,
    editingScheme: null,
    schemeForm: {
      name: "",
      description: "",
      entity_type: null,
      is_active: true,
      is_default: false,
    },

    // Line modal
    showLineModal: false,
    editingLine: null,
    currentSchemeForLine: null,
    lineForm: {
      name: "",
      require_all: false,
      deadline_enabled: false,
      deadline_days: 3,
    },

    // Approvers modal
    showApproversModal: false,
    currentSchemeForApprovers: null,
    currentLineForApprovers: null,
    approverForm: {
      type: null,
      user: null,
      role: null,
    },

    // Delete confirmation
    showDeleteConfirm: false,
    deleteConfirmMessage: "",
    deleteAction: null,

    // Assignments modal
    showAssignmentsModal: false,
    currentSchemeForAssignment: null,
    subEntityTypeOptions: [],
    entitiesForAssignment: [],
    assignmentForm: {
      subEntityType: null,
      subEntity: null,
    },
  }),
  computed: {
    breadcrumbs() {
      return this.additionalProps.breadcrumbs;
    },
    isSchemeFormValid() {
      return this.schemeForm.name?.trim() && this.schemeForm.entity_type;
    },
    isLineFormValid() {
      return this.lineForm.name?.trim();
    },
    canAddApprover() {
      if (!this.approverForm.type) return false;
      if (this.approverForm.type.id === "user" && !this.approverForm.user) return false;
      if (this.approverForm.type.id === "role" && !this.approverForm.role) return false;
      return true;
    },
    currentLineApprovers() {
      if (!this.currentLineForApprovers) return [];
      return this.currentLineForApprovers.approvers || [];
    },
    canAddAssignment() {
      return this.assignmentForm.subEntityType && this.assignmentForm.subEntity;
    },
  },
  methods: {
    n,
    async loadData() {
      try {
        const [schemesResponse, optionsResponse] = await Promise.all([
          fetchSchemes({ active_only: false }),
          fetchOptions(),
        ]);

        const basicSchemes = schemesResponse.data || [];

        if (basicSchemes?.ocs?.meta?.status === 'failure') {
          throw new Error(basicSchemes.ocs.meta.message);
        }

        const options = optionsResponse.data || {};
        this.entityTypeOptions = options.entityTypes || [];
        this.approverTypeOptions = options.approverTypes || [];
        this.subEntityTypeOptions = options.subEntityTypes || [];

        // Load full details and assignments for all schemes in parallel
        // Replace schemes only after all details are loaded
        this.schemes = await Promise.all(
            basicSchemes.map(async (scheme) => {
              const [schemeDetails, assignmentsResponse] = await Promise.all([
                fetchScheme(scheme.id),
                fetchSchemeAssignments(scheme.id),
              ]);
              const schemeData = schemeDetails.data || scheme;
              schemeData.assignments = assignmentsResponse.data || [];
              return schemeData;
            })
        );
      } catch (e) {
        console.error("Failed to load schemes:", e);
        redirectNotFoundPage(this.$router);
      }
    },

    async loadApproverOptions() {
      try {
        const [usersResponse, rolesResponse] = await Promise.all([
          fetchUsersForApprover(),
          fetchRolesForApprover(),
        ]);
        this.usersForApprover = usersResponse.data || [];
        this.rolesForApprover = rolesResponse.data || [];
      } catch (e) {
        console.error("Failed to load approver options:", e);
        redirectNotFoundPage(this.$router);
      }
    },

    getEntityTypeName(entityType) {
      const option = this.entityTypeOptions.find(o => o.id === entityType);
      let result = option?.name || entityType;
      return this.contextTranslate(result, this.context);
    },

    toggleExpand(schemeId) {
      this.expandedScheme = this.expandedScheme === schemeId ? null : schemeId;
    },

    // Scheme CRUD
    openCreateSchemeModal() {
      this.editingScheme = null;
      this.schemeForm = {
        name: "",
        description: "",
        entity_type: null,
        is_active: true,
        is_default: false,
      };
      this.showSchemeModal = true;
    },

    editScheme(scheme) {
      this.editingScheme = scheme;
      this.schemeForm = {
        name: scheme.name,
        description: scheme.description || "",
        entity_type: this.entityTypeOptions.find(o => o.id === scheme.entity_type) || null,
        is_active: Boolean(scheme.is_active),
        is_default: Boolean(scheme.is_default),
      };
      this.showSchemeModal = true;
    },

    closeSchemeModal() {
      this.showSchemeModal = false;
      this.editingScheme = null;
    },

    async saveScheme() {
      try {
        const data = {
          name: this.schemeForm.name,
          description: this.schemeForm.description,
          entity_type: this.schemeForm.entity_type?.id,
          is_active: this.schemeForm.is_active,
          is_default: this.schemeForm.is_default,
        };

        if (this.editingScheme) {
          await updateScheme(this.editingScheme.id, data);
        } else {
          await createScheme(data);
        }

        this.closeSchemeModal();
        await this.loadData();
      } catch (e) {
        console.error("Failed to save scheme:", e);
      }
    },

    async setAsDefault(scheme) {
      try {
        await setDefaultScheme(scheme.id);
        await this.loadData();
      } catch (e) {
        console.error("Failed to set default scheme:", e);
      }
    },

    confirmDeleteScheme(scheme) {
      this.deleteConfirmMessage = this.contextTranslate(
        "Are you sure you want to delete the scheme \"{name}\"?",
        this.context
      ).replace("{name}", scheme.name);
      this.deleteAction = async () => {
        await deleteScheme(scheme.id);
        await this.loadData();
      };
      this.showDeleteConfirm = true;
    },

    // Line CRUD
    openCreateLineModal(scheme) {
      this.currentSchemeForLine = scheme;
      this.editingLine = null;
      this.lineForm = {
        name: "",
        require_all: false,
        deadline_enabled: false,
        deadline_days: 3,
      };
      this.showLineModal = true;
    },

    editLine(scheme, line) {
      this.currentSchemeForLine = scheme;
      this.editingLine = line;
      this.lineForm = {
        name: line.name,
        require_all: Boolean(line.require_all),
        deadline_enabled: Boolean(line.deadline_enabled),
        deadline_days: line.deadline_days || 3,
      };
      this.showLineModal = true;
    },

    closeLineModal() {
      this.showLineModal = false;
      this.editingLine = null;
      this.currentSchemeForLine = null;
    },

    async saveLine() {
      try {
        const data = {
          name: this.lineForm.name,
          require_all: this.lineForm.require_all,
          deadline_enabled: this.lineForm.deadline_enabled,
          deadline_days: this.lineForm.deadline_enabled ? this.lineForm.deadline_days : null,
        };

        if (this.editingLine) {
          await updateSchemeLine(this.editingLine.id, data);
        } else {
          data.sort_order = (this.currentSchemeForLine.lines?.length || 0) + 1;
          await createSchemeLine(this.currentSchemeForLine.id, data);
        }

        this.closeLineModal();
        await this.loadData();
      } catch (e) {
        console.error("Failed to save line:", e);
      }
    },

    confirmDeleteLine(scheme, line) {
      this.deleteConfirmMessage = this.contextTranslate(
        "Are you sure you want to delete the step «{name}»?",
        this.context
      ).replace("{name}", line.name);
      this.deleteAction = async () => {
        await deleteSchemeLine(line.id);
        await this.loadData();
      };
      this.showDeleteConfirm = true;
    },

    async onLineReorder(scheme) {
      try {
        for (let i = 0; i < scheme.lines.length; i++) {
          await updateSchemeLine(scheme.lines[i].id, { sort_order: i + 1 });
        }
      } catch (e) {
        console.error("Failed to reorder lines:", e);
      }
    },

    // Approvers management
    async openApproversModal(scheme, line) {
      this.currentSchemeForApprovers = scheme;
      this.currentLineForApprovers = line;
      this.approverForm = {
        type: this.approverTypeOptions[0] || null,
        user: null,
        role: null,
      };
      await this.loadApproverOptions();
      this.showApproversModal = true;
    },

    closeApproversModal() {
      this.showApproversModal = false;
      this.currentSchemeForApprovers = null;
      this.currentLineForApprovers = null;
    },

    async addApprover() {
      try {
        const type = this.approverForm.type?.id;
        const userId = type === "user" ? this.approverForm.user?.id : null;
        const roleId = type === "role" ? this.approverForm.role?.id : null;

        await addApprover(this.currentLineForApprovers.id, type, userId, roleId);

        // Reload scheme to get updated approvers
        const schemeDetails = await fetchScheme(this.currentSchemeForApprovers.id);
        const updatedScheme = schemeDetails.data;

        // Update local state
        const schemeIndex = this.schemes.findIndex(s => s.id === this.currentSchemeForApprovers.id);
        if (schemeIndex !== -1) {
          this.schemes.splice(schemeIndex, 1, updatedScheme);
        }

        // Update current line reference
        this.currentLineForApprovers = updatedScheme.lines?.find(l => l.id === this.currentLineForApprovers.id);

        // Reset form
        this.approverForm.user = null;
        this.approverForm.role = null;
      } catch (e) {
        console.error("Failed to add approver:", e);
      }
    },

    async removeApprover(approver) {
      try {
        await removeApproverApi(approver.id);

        // Reload scheme
        const schemeDetails = await fetchScheme(this.currentSchemeForApprovers.id);
        const updatedScheme = schemeDetails.data;

        const schemeIndex = this.schemes.findIndex(s => s.id === this.currentSchemeForApprovers.id);
        if (schemeIndex !== -1) {
          this.schemes.splice(schemeIndex, 1, updatedScheme);
        }

        this.currentLineForApprovers = updatedScheme.lines?.find(l => l.id === this.currentLineForApprovers.id);
      } catch (e) {
        console.error("Failed to remove approver:", e);
      }
    },

    // Delete confirmation
    closeDeleteConfirm() {
      this.showDeleteConfirm = false;
      this.deleteAction = null;
    },

    async executeDelete() {
      if (this.deleteAction) {
        try {
          await this.deleteAction();
        } catch (e) {
          console.error("Failed to delete:", e);
        }
      }
      this.closeDeleteConfirm();
    },

    // Assignments management
    openAssignmentsModal(scheme) {
      this.currentSchemeForAssignment = scheme;
      this.assignmentForm = {
        subEntityType: null,
        subEntity: null,
      };
      this.entitiesForAssignment = [];
      this.showAssignmentsModal = true;
    },

    closeAssignmentsModal() {
      this.showAssignmentsModal = false;
      this.currentSchemeForAssignment = null;
      this.entitiesForAssignment = [];
    },

    async handleSubEntityTypeChange(type) {
      this.assignmentForm.subEntityType = type;
      this.assignmentForm.subEntity = null;

      if (type) {
        try {
          const response = await fetchEntitiesForAssignment(type.id);
          this.entitiesForAssignment = response.data || [];
        } catch (e) {
          console.error("Failed to load entities:", e);
          this.entitiesForAssignment = [];
          redirectNotFoundPage(this.$router);
        }
      } else {
        this.entitiesForAssignment = [];
      }
    },

    async addAssignment() {
      if (!this.canAddAssignment || !this.currentSchemeForAssignment) return;

      try {
        await createAssignment(
          this.currentSchemeForAssignment.id,
          this.currentSchemeForAssignment.entity_type,
          this.assignmentForm.subEntityType.id,
          this.assignmentForm.subEntity.id
        );

        this.closeAssignmentsModal();
        await this.loadData();
      } catch (e) {
        console.error("Failed to add assignment:", e);
      }
    },

    confirmDeleteAssignment(assignment) {
      this.deleteConfirmMessage = this.contextTranslate(
        "Are you sure you want to remove this assignment?",
        this.context
      );
      this.deleteAction = async () => {
        await deleteAssignment(assignment.id);
        await this.loadData();
      };
      this.showDeleteConfirm = true;
    },

    async init() {
      this.isLoading = true;
      await this.loadData();
      this.isLoading = false;
    },
  },
  mounted() {
    this.init();
  },
};
</script>

<style scoped>
.agreement-schemes__content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding: 24px;
  overflow-y: auto;
}

.agreement-schemes__loader {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
}

.agreement-schemes__header {
  margin-bottom: 8px;
}

.agreement-schemes__title {
  font-size: 24px;
  font-weight: 600;
  margin: 0 0 8px 0;
  color: var(--color-main-text);
}

.agreement-schemes__description {
  font-size: 14px;
  color: var(--color-text-maxcontrast);
  margin: 0;
}

.agreement-schemes__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  text-align: center;
}

.agreement-schemes__empty-icon {
  color: var(--color-text-maxcontrast);
  margin-bottom: 16px;
}

.agreement-schemes__empty-text {
  font-size: 14px;
  color: var(--color-text-maxcontrast);
  margin: 0 0 16px 0;
}

.agreement-schemes__list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.agreement-schemes__card {
  background-color: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  overflow: hidden;
}

.agreement-schemes__card--inactive {
  opacity: 0.7;
}

.agreement-schemes__card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 16px;
}

.agreement-schemes__card-info {
  flex: 1;
}

.agreement-schemes__card-title-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.agreement-schemes__card-title {
  font-size: 16px;
  font-weight: 600;
  margin: 0;
  color: var(--color-main-text);
}

.agreement-schemes__badge {
  font-size: 11px;
  font-weight: 500;
  padding: 2px 8px;
  border-radius: var(--border-radius);
}

.agreement-schemes__badge--default {
  background-color: var(--color-primary-element-light);
  color: var(--color-primary-element);
}

.agreement-schemes__badge--inactive {
  background-color: var(--color-background-dark);
  color: var(--color-text-maxcontrast);
}

.agreement-schemes__card-description {
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  margin: 0 0 8px 0;
}

.agreement-schemes__card-meta {
  display: flex;
  gap: 16px;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.agreement-schemes__card-actions {
  display: flex;
  gap: 4px;
}

.agreement-schemes__expand-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  width: 100%;
  padding: 8px;
  border: none;
  border-top: 1px solid var(--color-border);
  background-color: var(--color-background-dark);
  color: var(--color-text-maxcontrast);
  cursor: pointer;
  font-size: 13px;
  transition: background-color 0.2s;
}

.agreement-schemes__expand-btn:hover {
  background-color: var(--color-background-hover);
}

.agreement-schemes__expand-icon--rotated {
  transform: rotate(180deg);
}

.agreement-schemes__lines {
  padding: 16px;
  border-top: 1px solid var(--color-border);
  background-color: var(--color-background-dark);
}

.agreement-schemes__lines-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.agreement-schemes__lines-title {
  font-size: 14px;
  font-weight: 600;
  margin: 0;
}

.agreement-schemes__lines-empty {
  text-align: center;
  padding: 24px;
  color: var(--color-text-maxcontrast);
  font-size: 13px;
}

.agreement-schemes__line {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background-color: var(--color-main-background);
  border-radius: var(--border-radius);
  margin-bottom: 8px;
}

.agreement-schemes__line-drag {
  cursor: grab;
  color: var(--color-text-maxcontrast);
}

.agreement-schemes__line-order {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--color-primary-element);
  color: var(--color-primary-element-text);
  border-radius: 50%;
  font-size: 12px;
  font-weight: 600;
}

.agreement-schemes__line-info {
  flex: 1;
}

.agreement-schemes__line-name {
  font-weight: 500;
  color: var(--color-main-text);
}

.agreement-schemes__line-settings {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}

.agreement-schemes__line-badge {
  font-size: 11px;
  padding: 2px 6px;
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius);
  color: var(--color-text-maxcontrast);
}

.agreement-schemes__line-approvers {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.agreement-schemes__approver {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  padding: 2px 8px;
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius);
  color: var(--color-main-text);
}

.agreement-schemes__line-actions {
  display: flex;
  gap: 4px;
}

.agreement-schemes__sidebar-content {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 16px;
}

.agreement-schemes__sidebar-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: auto;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.agreement-schemes__confirm-message {
  font-size: 14px;
  color: var(--color-main-text);
  margin: 0 0 24px 0;
}

.agreement-schemes__form-group {
  margin-bottom: 16px;
}

.agreement-schemes__form-group--switch {
  display: flex;
  align-items: center;
  gap: 12px;
}

.agreement-schemes__form-row {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.agreement-schemes__label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 4px;
  color: var(--color-main-text);
}

.agreement-schemes__form-group--switch .agreement-schemes__label {
  margin-bottom: 0;
}

.agreement-schemes__approvers-section {
  margin-bottom: 24px;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--color-border);
}

.agreement-schemes__approvers-title {
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 12px 0;
}

.agreement-schemes__approvers-list {
  min-height: 100px;
}

.agreement-schemes__approvers-empty {
  text-align: center;
  padding: 24px;
  color: var(--color-text-maxcontrast);
  font-size: 13px;
}

.agreement-schemes__approver-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius);
  margin-bottom: 8px;
}

.agreement-schemes__approver-name {
  flex: 1;
  font-weight: 500;
}

.agreement-schemes__approver-type {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

/* Assignments section */
.agreement-schemes__assignments {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid var(--color-border);
}

.agreement-schemes__assignments-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.agreement-schemes__assignments-title {
  font-size: 14px;
  font-weight: 600;
  margin: 0;
}

.agreement-schemes__assignments-hint {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  margin: 0 0 12px 0;
}

.agreement-schemes__assignments-empty {
  text-align: center;
  padding: 16px;
  color: var(--color-text-maxcontrast);
  font-size: 13px;
  background-color: var(--color-main-background);
  border-radius: var(--border-radius);
}

.agreement-schemes__assignments-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.agreement-schemes__assignment-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  background-color: var(--color-main-background);
  border-radius: var(--border-radius);
}

.agreement-schemes__assignment-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.agreement-schemes__assignment-name {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-main-text);
}

.agreement-schemes__assignment-type {
  font-size: 11px;
  color: var(--color-text-maxcontrast);
}

.agreement-schemes__assignments-modal-hint {
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  margin: 0 0 16px 0;
  line-height: 1.4;
}
</style>