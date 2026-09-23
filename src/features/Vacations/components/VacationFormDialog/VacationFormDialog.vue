/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VAside :value="open" :width="620" @input="handleClose">
    <template #title>
      {{ dialogTitle }}
    </template>

    <div class="vacation-form">
      <div class="vacation-form__grid">
        <!-- Date Start -->
        <VDatePicker
            :value="formData.date_start"
            :label="contextTranslate('Vacation start date', context)"
            :placeholder="contextTranslate('Select date', context)"
            :error="errors.date_start"
            format="YYYY-MM-DD"
            required
            @input="updateField('date_start', $event)"
        />

        <!-- Date End -->
        <VDatePicker
            :value="formData.date_end"
            :label="contextTranslate('Vacation end date', context)"
            :placeholder="contextTranslate('Select date', context)"
            :error="errors.date_end"
            format="YYYY-MM-DD"
            required
            @input="updateField('date_end', $event)"
        />

        <!-- Half day: the last day counts as half (e.g. a half-day time off) -->
        <div class="vacation-form__field--full vacation-form__half-day">
          <VSwitch
            :value="formData.half_day"
            @input="updateField('half_day', $event)"
          />
          <span class="vacation-form__half-day-label">
            {{ contextTranslate("Half day (last day counts as half)", context) }}
          </span>
          <span v-if="computedDays !== null" class="vacation-form__half-day-total">
            {{ contextTranslate("Total", context) }}: {{ computedDays }}
            {{ contextTranslate("days", context) }}
          </span>
        </div>

        <!-- Employee -->
        <VDropdown v-if="isApprover"
           :value="selectedEmployee"
           :options="employeeOptions"
           :label="contextTranslate('Employee', context)"
           :placeholder="contextTranslate('Select employee', context)"
           :error="errors.user_id"
           :disabled="isEditMode"
           value-label="name"
           user-select
           required
           @input="handleEmployeeChange"
        />

        <!-- Vacation Type -->
        <VDropdown
          :value="selectedType"
          :options="typeOptions"
          :label="contextTranslate('Absence type', context)"
          :placeholder="contextTranslate('Select type', context)"
          :error="errors.type_id"
          value-label="name"
          required
          @input="handleTypeChange"
        />

        <!-- Project -->
        <VDropdown
            :value="selectedProject"
            :options="projectOptions"
            :label="contextTranslate('Project', context)"
            :placeholder="contextTranslate('Select project (optional)', context)"
            value-label="name"
            @input="handleProjectChange"
        />

        <!-- Comment -->
        <div class="vacation-form__field--full">
          <VTextArea
            :value="formData.comment"
            :label="contextTranslate('Comment', context)"
            :placeholder="contextTranslate('Enter comment...', context)"
            @input="updateField('comment', $event)"
          />
        </div>
      </div>

      <div class="vacation-form__actions">
        <NcButton variant="tertiary" @click="handleClose">
          {{ contextTranslate("Cancel", context) }}
        </NcButton>
        <NcButton
          variant="primary"
          :disabled="submitting"
          @click="handleSubmit"
        >
          <template v-if="submitting">
            <VLoader :size="20" />
          </template>
          <template v-else>
            {{ isEditMode ? contextTranslate("Save", context) : contextTranslate("Create", context) }}
          </template>
        </NcButton>
      </div>
    </div>
  </VAside>
</template>

<script>
import { NcButton } from "@nextcloud/vue";

import { VAside, VDropdown, VDatePicker, VTextArea, VLoader, VSwitch } from "@/shared/components";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { createVacation, updateVacation } from "@/entities/vacations/api";
import {format} from "date-fns";

export default {
  name: "VacationFormDialog",
  components: {
    VAside,
    NcButton,
    VDropdown,
    VDatePicker,
    VTextArea,
    VLoader,
    VSwitch,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    open: {
      type: Boolean,
      default: false,
    },
    vacation: {
      type: Object,
      default: null,
    },
    vacationTypes: {
      type: Array,
      default: () => [],
    },
    isApprover: {
      type: Boolean,
      default: false,
    },
    currentUserId: {
      type: String,
      default: null,
    },
    projects: {
      type: Array,
      default: () => [],
    },
    employees: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["close", "submit", "success"],
  data: () => ({
    context: "vacations",
    submitting: false,
    formData: {
      user_id: null,
      type_id: null,
      date_start: null,
      date_end: null,
      half_day: false,
      project_id: null,
      comment: "",
    },
    errors: {},
  }),
  computed: {
    isEditMode() {
      return !!this.vacation;
    },
    dialogTitle() {
      return this.isEditMode
        ? this.contextTranslate("Edit request", this.context)
        : this.contextTranslate("Add request", this.context);
    },
    typeOptions() {
      return this.vacationTypes.map(type => ({
        id: type.id,
        name: type.name,
        color: type.color,
      }));
    },
    projectOptions() {
      return [
        { id: null, name: this.contextTranslate("No project", this.context) },
        ...this.projects.map(project => ({
          id: project.id,
          name: project.name,
        })),
      ];
    },
    employeeOptions() {
      return this.employees.map(emp => ({
        id: emp.id,
        name: emp.name || '',
      }));
    },
    selectedEmployee() {
      return this.employeeOptions.find(opt => opt.id === this.formData.user_id) || null;
    },
    selectedType() {
      return this.typeOptions.find(opt => opt.id === this.formData.type_id) || null;
    },
    selectedProject() {
      return this.projectOptions.find(opt => opt.id === this.formData.project_id) || null;
    },
    // Live preview of the absence length: inclusive calendar days minus 0.5
    // when the half-day flag is on. Null until both dates are valid.
    computedDays() {
      if (!this.formData.date_start || !this.formData.date_end) {
        return null;
      }
      const start = new Date(this.formData.date_start);
      const end = new Date(this.formData.date_end);
      start.setHours(0, 0, 0, 0);
      end.setHours(0, 0, 0, 0);
      if (start > end) {
        return null;
      }
      const calendarDays = Math.round((end - start) / 86400000) + 1;
      const total = this.formData.half_day ? calendarDays - 0.5 : calendarDays;
      return total;
    },
  },
  watch: {
    open: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          this.initForm();
        }
      },
    },
    vacation: {
      immediate: true,
      handler() {
        if (this.open) {
          this.initForm();
        }
      },
    },
  },
  methods: {
    initForm() {
      this.errors = {};

      if (this.vacation) {
        this.formData = {
          user_id: this.vacation.user_id || null,
          type_id: this.vacation.type_id || null,
          date_start: this.vacation.date_start ? new Date(this.vacation.date_start) : null,
          date_end: this.vacation.date_end ? new Date(this.vacation.date_end) : null,
          half_day: Boolean(this.vacation.half_day),
          project_id: this.vacation.project_id || null,
          comment: this.vacation.comment || "",
        };
      } else {
        this.formData = {
          user_id: null,
          type_id: null,
          date_start: null,
          date_end: null,
          half_day: false,
          project_id: null,
          comment: "",
        };

        if (!this.isApprover) {
          this.formData.user_id = this.currentUserId;
        }
      }
    },
    updateField(key, value) {
      this.formData[key] = value;
      if (this.errors[key]) {
        delete this.errors[key];
      }
    },
    handleEmployeeChange(option) {
      this.updateField("user_id", option?.id || null);
    },
    handleTypeChange(option) {
      this.updateField("type_id", option?.id || null);
    },
    handleProjectChange(option) {
      this.updateField("project_id", option?.id || null);
    },
    handleClose() {
      this.$emit("close");
    },
    validate() {
      this.errors = {};

      if (!this.formData.user_id) {
        this.errors.user_id = this.contextTranslate("Employee is required", this.context);
      }

      if (!this.formData.type_id) {
        this.errors.type_id = this.contextTranslate("Absence type is required", this.context);
      }

      if (!this.formData.date_start) {
        this.errors.date_start = this.contextTranslate("Start date is required", this.context);
      }

      if (!this.formData.date_end) {
        this.errors.date_end = this.contextTranslate("End date is required", this.context);
      }

      if (this.formData.date_start && this.formData.date_end) {
        const start = new Date(this.formData.date_start);
        const end = new Date(this.formData.date_end);
        if (start > end) {
          this.errors.date_end = this.contextTranslate("End date must be after start date", this.context);
        }
      }

      return Object.keys(this.errors).length === 0;
    },
    async handleSubmit() {
      if (!this.validate()) {
        return;
      }

      try {
        this.submitting = true;

        const payload = {
          user_id: this.formData.user_id,
          type_id: this.formData.type_id,
          date_start: format(new Date(this.formData.date_start), "yyyy-MM-dd"),
          date_end: format(new Date(this.formData.date_end), "yyyy-MM-dd"),
          half_day: this.formData.half_day ? 1 : 0,
          project_id: this.formData.project_id || null,
          comment: this.formData.comment || "",
        };

        if (this.isEditMode) {
          await updateVacation(this.vacation.id, payload);
        } else {
          await createVacation(payload);
        }

        this.$emit("success");
        this.$emit("submit", payload);
        this.handleClose();
      } catch (error) {
        console.error("Failed to save vacation:", error);
      } finally {
        this.submitting = false;
      }
    },
  },
};
</script>

<style scoped>
.vacation-form {
  padding: 16px;
  overflow: hidden;
}

.vacation-form__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.vacation-form__field--full {
  grid-column: span 2;
}

.vacation-form__half-day {
  display: flex;
  align-items: center;
  line-height: normal;
}

.vacation-form__half-day-label {
  font-size: 14px;
  color: var(--color-main-text);
}

.vacation-form__half-day-total {
  margin-left: auto;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
}

.vacation-form__actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

@media (max-width: 768px) {
  .vacation-form__grid {
    grid-template-columns: 1fr;
  }

  .vacation-form__field--full {
    grid-column: span 1;
  }
}
</style>
