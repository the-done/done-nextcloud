/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div class="vacation-filters">
    <div class="vacation-filters__header">
      <FilterVariant class="vacation-filters__icon" :size="20" />
      <h3 class="vacation-filters__title">
        {{ contextTranslate("Filters", context) }}
      </h3>
    </div>

    <div class="vacation-filters__grid">
      <!-- Date From -->
      <VDatePicker
        :value="filters.date_from"
        :label="contextTranslate('Date from', context)"
        :placeholder="contextTranslate('Select date', context)"
        format="YYYY-MM-DD"
        @input="updateFilter('date_from', $event)"
      />

      <!-- Date To -->
      <VDatePicker
        :value="filters.date_to"
        :label="contextTranslate('Date to', context)"
        :placeholder="contextTranslate('Select date', context)"
        format="YYYY-MM-DD"
        @input="updateFilter('date_to', $event)"
      />

      <!-- Vacation Type -->
      <VDropdown
        :value="selectedTypes"
        :options="typeOptions"
        :label="contextTranslate('Type', context)"
        :placeholder="contextTranslate('All types', context)"
        value-label="name"
        multiple
        @input="handleTypeChange"
      />

      <!-- Status -->
      <VDropdown
        :value="selectedStatus"
        :options="statusOptions"
        :label="contextTranslate('Status', context)"
        :placeholder="contextTranslate('All statuses', context)"
        value-label="name"
        @input="handleStatusChange"
      />

      <!-- Project -->
      <VDropdown
        :value="selectedProjects"
        :options="projectOptions"
        :label="contextTranslate('Project', context)"
        :placeholder="contextTranslate('All projects', context)"
        value-label="name"
        multiple
        @input="handleProjectChange"
      />

      <!-- Employee -->
      <VDropdown
        :value="selectedEmployees"
        :options="employeeOptions"
        :label="contextTranslate('Employee', context)"
        :placeholder="contextTranslate('All employees', context)"
        value-label="name"
        user-select
        multiple
        @input="handleEmployeeChange"
      />
    </div>

    <div class="vacation-filters__actions">
      <VButton variant="tertiary" @click="resetFilters">
        {{ contextTranslate("Reset filters", context) }}
      </VButton>
    </div>
  </div>
</template>

<script>
import FilterVariant from "vue-material-design-icons/FilterVariant.vue";

import { VDatePicker, VDropdown, VButton } from "@/shared/components";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import {
  VACATION_STATUS,
  VACATION_STATUS_KEYS,
} from "@/entities/vacations/constants";

export default {
  name: "VacationFilters",
  components: {
    FilterVariant,
    VDatePicker,
    VDropdown,
    VButton,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    filters: {
      type: Object,
      default: () => ({
        date_from: null,
        date_to: null,
        vacation_type_ids: [],
        vacation_status: null,
        project_ids: [],
        employee_ids: [],
      }),
    },
    vacationTypes: {
      type: Array,
      default: () => [],
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
  emits: ["update:filters", "filter-change"],
  data: () => ({
    context: "vacations",
  }),
  computed: {
    typeOptions() {
      return this.vacationTypes.map(type => ({
        id: type.id,
        name: type.name,
      }));
    },
    statusOptions() {
      return [
        { id: null, name: this.contextTranslate("All statuses", this.context) },
        {
          id: VACATION_STATUS.SUBMITTED,
          name: this.contextTranslate(VACATION_STATUS_KEYS[VACATION_STATUS.SUBMITTED], this.context),
        },
        {
          id: VACATION_STATUS.APPROVED,
          name: this.contextTranslate(VACATION_STATUS_KEYS[VACATION_STATUS.APPROVED], this.context),
        },
        {
          id: VACATION_STATUS.REJECTED,
          name: this.contextTranslate(VACATION_STATUS_KEYS[VACATION_STATUS.REJECTED], this.context),
        },
      ];
    },
    projectOptions() {
      return this.projects.map(project => ({
        id: project.id,
        name: project.name,
      }));
    },
    employeeOptions() {
      return this.employees.map(emp => ({
        id: emp.id,
        name: emp.full_name || `${emp.lastname} ${emp.name}`,
      }));
    },
    selectedTypes() {
      const typeIds = this.filters.vacation_type_ids || [];
      return this.typeOptions.filter(opt => typeIds.includes(opt.id));
    },
    selectedStatus() {
      const statusId = this.filters.vacation_status;
      return this.statusOptions.find(opt => opt.id === statusId) || null;
    },
    selectedProjects() {
      const projectIds = this.filters.project_ids || [];
      return this.projectOptions.filter(opt => projectIds.includes(opt.id));
    },
    selectedEmployees() {
      const employeeIds = this.filters.employee_ids || [];
      return this.employeeOptions.filter(opt => employeeIds.includes(opt.id));
    },
  },
  methods: {
    updateFilter(key, value) {
      const newFilters = { ...this.filters, [key]: value };
      this.$emit("update:filters", newFilters);
      this.$emit("filter-change", newFilters);
      this.updateUrlParams(newFilters);
    },
    handleTypeChange(options) {
      const value = Array.isArray(options)
        ? options.map(opt => opt.id)
        : [];
      this.updateFilter("vacation_type_ids", value);
    },
    handleStatusChange(option) {
      this.updateFilter("vacation_status", option?.id || null);
    },
    handleProjectChange(options) {
      const value = Array.isArray(options)
        ? options.map(opt => opt.id)
        : [];
      this.updateFilter("project_ids", value);
    },
    handleEmployeeChange(options) {
      const value = Array.isArray(options)
        ? options.map(opt => opt.id)
        : [];
      this.updateFilter("employee_ids", value);
    },
    resetFilters() {
      const currentYear = new Date().getFullYear();
      const defaultFilters = {
        date_from: `${currentYear}-01-01`,
        date_to: `${currentYear}-12-31`,
        vacation_type_ids: [],
        vacation_status: null,
        project_ids: [],
        employee_ids: [],
      };
      this.$emit("update:filters", defaultFilters);
      this.$emit("filter-change", defaultFilters);
      this.updateUrlParams(defaultFilters);
    },
    updateUrlParams(filters) {
      const query = {};

      if (filters.date_from) query.date_from = filters.date_from;
      if (filters.date_to) query.date_to = filters.date_to;
      if (filters.vacation_type_ids?.length) {
        query.type = filters.vacation_type_ids.join(",");
      }
      if (filters.vacation_status) query.status = filters.vacation_status;
      if (filters.project_ids?.length) {
        query.project = filters.project_ids.join(",");
      }
      if (filters.employee_ids?.length) {
        query.employee = filters.employee_ids.join(",");
      }

      this.$router.replace({ query }).catch(() => {});
    },
    initFromUrl() {
      const query = this.$route.query;
      const currentYear = new Date().getFullYear();

      const filters = {
        date_from: query.date_from || `${currentYear}-01-01`,
        date_to: query.date_to || `${currentYear}-12-31`,
        // Keep IDs as strings to match API response format
        vacation_type_ids: query.type
          ? query.type.split(",").filter(id => id.trim())
          : [],
        vacation_status: query.status ? parseInt(query.status, 10) : null,
        project_ids: query.project
          ? query.project.split(",").filter(id => id.trim())
          : [],
        employee_ids: query.employee
          ? query.employee.split(",").filter(id => id.trim())
          : [],
      };

      this.$emit("update:filters", filters);
    },
  },
  mounted() {},
};
</script>

<style scoped>
.vacation-filters {
  background-color: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  padding: 16px;
}

.vacation-filters__header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
}

.vacation-filters__icon {
  color: var(--color-primary-element);
}

.vacation-filters__title {
  font-size: 16px;
  font-weight: 600;
  margin: 0;
  color: var(--color-main-text);
}

.vacation-filters__grid {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: 16px;
}

@media (min-width: 768px) {
  .vacation-filters__grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .vacation-filters__grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.vacation-filters__actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}
</style>