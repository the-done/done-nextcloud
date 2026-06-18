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
            <Cog v-else />
          </template>
        </NcBreadcrumb>
      </NcBreadcrumbs>
    </VToolbar>

    <!-- Main content -->
    <VScrollArea class="vacation-settings__content">
      <!-- Loading state -->
      <div v-if="isLoading" class="vacation-settings__loader">
        <VLoader />
      </div>

      <!-- Access denied -->
      <div v-else-if="accessDenied" class="vacation-settings__access-denied">
        <ShieldLock class="vacation-settings__access-denied-icon" :size="64" />
        <h2 class="vacation-settings__access-denied-title">
          {{ contextTranslate("Access denied", context) }}
        </h2>
        <p class="vacation-settings__access-denied-description">
          {{ contextTranslate("You do not have permission to access these settings. Only HR approvers can manage vacation settings.", context) }}
        </p>
        <NcButton type="primary" @click="$router.push({ name: 'vacations-home' })">
          {{ contextTranslate("Back to vacations", context) }}
        </NcButton>
      </div>

      <template v-else>
        <!-- Page title -->
        <div class="vacation-settings__header">
          <h1 class="vacation-settings__title">
            {{ contextTranslate("Vacations module settings", context) }}
          </h1>
          <p class="vacation-settings__description">
            {{ contextTranslate("Manage vacation and absence settings", context) }}
          </p>
        </div>

        <!-- Report settings -->
        <div class="vacation-settings__section">
          <div class="vacation-settings__section-header">
            <FileChart class="vacation-settings__section-icon" :size="20" />
            <h2 class="vacation-settings__section-title">
              {{ contextTranslate("Vacations report", context) }}
            </h2>
          </div>

          <div class="vacation-settings__section-content">
            <div class="vacation-settings__type-card">
              <div class="vacation-settings__type-field">
                <span class="vacation-settings__type-label">
                  {{ contextTranslate("«Used / limit» separator", context) }}
                </span>
                <VTextField
                  :value="reportSeparator"
                  class="vacation-settings__separator-input"
                  :placeholder="'/'"
                  @input="reportSeparator = $event"
                />
              </div>
              <p class="vacation-settings__field-hint">
                {{ contextTranslate("Shown between used and total days, e.g. «5 / 38». Default is «/».", context) }}
              </p>
            </div>

            <div class="vacation-settings__type-card">
              <div class="vacation-settings__type-field">
                <span class="vacation-settings__type-label">
                  {{ contextTranslate("Mandatory leave minimum length (days)", context) }}
                </span>
                <VTextField
                  :value="mandatoryMinDays"
                  class="vacation-settings__separator-input"
                  type="number"
                  min="1"
                  :placeholder="'14'"
                  @input="mandatoryMinDays = $event"
                />
              </div>
              <p class="vacation-settings__field-hint">
                {{ contextTranslate("A paid leave of at least this many days counts as a mandatory leave. Default is 14.", context) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Vacation Types Settings -->
        <div class="vacation-settings__section">
          <div class="vacation-settings__section-header">
            <CalendarMonth class="vacation-settings__section-icon" :size="20" />
            <h2 class="vacation-settings__section-title">
              {{ contextTranslate("Absence types", context) }}
            </h2>
          </div>
          <p class="vacation-settings__section-description">
            {{ contextTranslate("Configure limits and colors for each type", context) }}
          </p>

          <div class="vacation-settings__section-content">
            <div
              v-for="setting in typeSettings"
              :key="setting.id"
              class="vacation-settings__type-card"
            >
              <div class="vacation-settings__type-header">
                <span
                  class="vacation-settings__type-dot"
                  :style="{ backgroundColor: setting.color }"
                />
                <span class="vacation-settings__type-name">{{ setting.name }}</span>
              </div>

              <div class="vacation-settings__type-fields">
                <!-- Color -->
                <div class="vacation-settings__type-field">
                  <span class="vacation-settings__type-label">
                    {{ contextTranslate("Color", context) }}
                  </span>
                  <div class="vacation-settings__color-input">
                    <input
                      type="color"
                      :value="setting.color"
                      class="vacation-settings__color-picker"
                      @input="updateTypeSetting(setting.id, 'color', $event.target.value)"
                    />
                    <VTextField
                      :value="setting.color"
                      class="vacation-settings__color-text"
                      @input="updateTypeSetting(setting.id, 'color', $event)"
                    />
                  </div>
                </div>

                <!-- Default days -->
                <div class="vacation-settings__type-field">
                  <span class="vacation-settings__type-label">
                    {{ contextTranslate("Default days (0 = no limit)", context) }}
                  </span>
                  <VTextField
                    :value="setting.defaultDays"
                    type="number"
                    min="0"
                    @input="updateTypeSetting(setting.id, 'defaultDays', parseInt($event) || 0)"
                  />
                </div>

                <!-- Display warning -->
                <div class="vacation-settings__type-field vacation-settings__type-field--switch">
                  <span class="vacation-settings__type-label">
                    {{ contextTranslate("Show warning", context) }}
                  </span>
                  <VSwitch
                    :value="setting.displayWarning"
                    @input="updateTypeSetting(setting.id, 'displayWarning', $event)"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Coefficients -->
        <div class="vacation-settings__section">
          <div class="vacation-settings__section-header">
            <ChartBellCurve class="vacation-settings__section-icon" :size="20" />
            <h2 class="vacation-settings__section-title">
              {{ contextTranslate("Coefficients", context) }}
            </h2>
          </div>
          <p class="vacation-settings__section-description">
            {{ contextTranslate("Configure per-employee accrual multipliers for vacation types. Applied monthly, prorated across the year.", context) }}
          </p>

          <div class="vacation-settings__section-content">
            <table v-if="coefficients.length > 0" class="vacation-settings__coef-table">
              <thead>
                <tr>
                  <th>{{ contextTranslate("Employee", context) }}</th>
                  <th>{{ contextTranslate("Vacation type", context) }}</th>
                  <th>{{ contextTranslate("Coefficient", context) }}</th>
                  <th>{{ contextTranslate("Effective from", context) }}</th>
                  <th>{{ contextTranslate("Effective to", context) }}</th>
                  <th>{{ contextTranslate("Comment", context) }}</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in coefficients" :key="row.id">
                  <td>{{ row.user_name }}</td>
                  <td>
                    <span class="vacation-settings__coef-type">
                      <span class="vacation-settings__type-dot" :style="{ backgroundColor: row.type_color }" />
                      {{ row.type_name }}
                    </span>
                  </td>
                  <td>{{ Number(row.value).toFixed(2) }}</td>
                  <td>{{ formatDate(row.effective_from) }}</td>
                  <td>{{ row.effective_to ? formatDate(row.effective_to) : "—" }}</td>
                  <td class="vacation-settings__coef-comment">{{ row.comment || "" }}</td>
                  <td class="vacation-settings__coef-actions">
                    <NcButton type="tertiary" :aria-label="contextTranslate('Delete', context)" @click="removeCoefficient(row.id)">
                      <template #icon>
                        <Close :size="20" />
                      </template>
                    </NcButton>
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-else class="vacation-settings__coef-empty">
              {{ contextTranslate("No coefficients configured yet.", context) }}
            </p>

            <div v-if="coefficientFormOpen" class="vacation-settings__coef-form">
              <div class="vacation-settings__coef-form-grid">
                <VDropdown
                  :value="selectedCoefEmployee"
                  :options="employeeOptions"
                  :label="contextTranslate('Employee', context)"
                  :placeholder="contextTranslate('Select employee', context)"
                  value-label="name"
                  user-select
                  required
                  @input="handleCoefEmployeeChange"
                />
                <VDropdown
                  :value="selectedCoefType"
                  :options="typeOptions"
                  :label="contextTranslate('Vacation type', context)"
                  :placeholder="contextTranslate('Select type', context)"
                  value-label="name"
                  required
                  @input="handleCoefTypeChange"
                />
                <VTextField
                  :value="newCoefficient.value"
                  :label="contextTranslate('Coefficient (e.g., 1.2)', context)"
                  type="number"
                  step="0.01"
                  min="0"
                  @input="newCoefficient.value = $event"
                />
                <VDatePicker
                  :value="newCoefficient.effective_from"
                  :label="contextTranslate('Effective from', context)"
                  :placeholder="contextTranslate('Select date', context)"
                  format="YYYY-MM-DD"
                  required
                  @input="newCoefficient.effective_from = $event"
                />
                <VDatePicker
                  :value="newCoefficient.effective_to"
                  :label="contextTranslate('Effective to (optional)', context)"
                  :placeholder="contextTranslate('Select date', context)"
                  format="YYYY-MM-DD"
                  @input="newCoefficient.effective_to = $event"
                />
                <VTextField
                  :value="newCoefficient.comment"
                  :label="contextTranslate('Comment', context)"
                  @input="newCoefficient.comment = $event"
                />
              </div>
              <div class="vacation-settings__coef-form-actions">
                <NcButton type="tertiary" @click="closeCoefficientForm">
                  {{ contextTranslate("Cancel", context) }}
                </NcButton>
                <NcButton
                  type="primary"
                  :disabled="!canSubmitCoefficient"
                  @click="submitCoefficient"
                >
                  {{ contextTranslate("Add coefficient", context) }}
                </NcButton>
              </div>
            </div>

            <div v-else class="vacation-settings__coef-toolbar">
              <NcButton type="secondary" @click="openCoefficientForm">
                <template #icon>
                  <Plus :size="20" />
                </template>
                {{ contextTranslate("Add coefficient", context) }}
              </NcButton>
            </div>
          </div>
        </div>

        <!-- Save button -->
        <div class="vacation-settings__actions">
          <VButton
            variant="primary"
            :loading="isSaving"
            @click="handleSave"
          >
            {{ contextTranslate("Save settings", context) }}
          </VButton>
        </div>
      </template>
    </VScrollArea>
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb, NcButton, NcSelect } from "@nextcloud/vue";

import Cog from "vue-material-design-icons/Cog.vue";
import CalendarMonth from "vue-material-design-icons/CalendarMonth.vue";
import AccountGroup from "vue-material-design-icons/AccountGroup.vue";
import AccountSupervisor from "vue-material-design-icons/AccountSupervisor.vue";
import InformationOutline from "vue-material-design-icons/InformationOutline.vue";
import ShieldLock from "vue-material-design-icons/ShieldLock.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import Close from "vue-material-design-icons/Close.vue";
import ChartBellCurve from "vue-material-design-icons/ChartBellCurve.vue";
import FileChart from "vue-material-design-icons/FileChart.vue";

import { format } from "date-fns";

import { VPage } from "@/widgets";
import {
  VToolbar,
  VScrollArea,
  VLoader,
  VSwitch,
  VTextField,
  VButton,
  VDropdown,
  VDatePicker,
} from "@/shared/components";

import {
  fetchVacationTypes,
  fetchVacationTypeSettings,
  setVacationTypeSetting,
  fetchVacationCoefficients,
  createVacationCoefficient,
  deleteVacationCoefficient,
  fetchVacationReportSeparator,
  setVacationReportSeparator,
  fetchMandatoryLeaveMinDays,
  setMandatoryLeaveMinDays,
} from "@/entities/vacations/api";
import { fetchEmployeesOptionsForVacations } from "@/entities/vacations/api";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "VacationsSettingsPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcButton,
    NcSelect,
    Cog,
    CalendarMonth,
    AccountGroup,
    AccountSupervisor,
    InformationOutline,
    ShieldLock,
    Plus,
    Close,
    ChartBellCurve,
    FileChart,
    VPage,
    VToolbar,
    VScrollArea,
    VLoader,
    VSwitch,
    VTextField,
    VButton,
    VDropdown,
    VDatePicker,
  },
  props: {
    additionalProps: {
      type: Object,
      default: () => ({}),
    },
  },
  data: () => ({
    context: "vacations",
    isLoading: false,
    isSaving: false,
    accessDenied: false,
    typeSettings: [],
    reportSeparator: "/",
    mandatoryMinDays: 14,
    coefficients: [],
    employees: [],
    vacationTypes: [],
    coefficientFormOpen: false,
    newCoefficient: {
      user_id: null,
      type_id: null,
      value: "1.00",
      effective_from: "",
      effective_to: "",
      comment: "",
    },
  }),
  computed: {
    breadcrumbs() {
      return this.additionalProps.breadcrumbs;
    },
    employeeOptions() {
      return this.employees.map(e => ({ id: e.id, name: e.name || e.id }));
    },
    typeOptions() {
      return this.vacationTypes.map(t => ({ id: t.id, name: t.name }));
    },
    selectedCoefEmployee() {
      return this.employeeOptions.find(e => e.id === this.newCoefficient.user_id) || null;
    },
    selectedCoefType() {
      return this.typeOptions.find(t => t.id === this.newCoefficient.type_id) || null;
    },
    canSubmitCoefficient() {
      const c = this.newCoefficient;
      return Boolean(c.user_id && c.type_id && c.effective_from && Number(c.value) > 0);
    },
  },
  methods: {
    async loadData() {
      try {
        const [typesResponse, settingsResponse, employeesResponse, coefficientsResponse, separatorResponse, mandatoryResponse] = await Promise.all([
          fetchVacationTypes(),
          fetchVacationTypeSettings(),
          fetchEmployeesOptionsForVacations(),
          fetchVacationCoefficients(),
          fetchVacationReportSeparator(),
          fetchMandatoryLeaveMinDays(),
        ]);

        const types = typesResponse.data || [];
        const settings = settingsResponse.data || [];

        this.vacationTypes = types;
        this.employees = employeesResponse.data || [];
        this.coefficients = coefficientsResponse.data || [];
        this.reportSeparator = separatorResponse.data?.separator ?? "/";
        this.mandatoryMinDays = mandatoryResponse.data?.min_days ?? 14;

        // Merge types with settings
        this.typeSettings = types.map(type => {
          const typeSetting = settings.find(s => s.type_id === type.id);
          return {
            id: type.id,
            name: type.name,
            color: type.color || "#4CAF50",
            defaultDays: typeSetting?.value || 0,
            displayWarning: typeSetting?.display_warning ?? true,
          };
        });
      } catch (e) {
        console.error("Failed to load settings:", e);
      }
    },

    async loadCoefficients() {
      try {
        const { data } = await fetchVacationCoefficients();
        this.coefficients = data || [];
      } catch (e) {
        console.error("Failed to load coefficients:", e);
        this.coefficients = [];
      }
    },

    formatDate(value) {
      if (!value) return "";
      try {
        const date = new Date(value);
        return format(date, "yyyy-MM-dd");
      } catch (e) {
        return String(value);
      }
    },

    openCoefficientForm() {
      this.coefficientFormOpen = true;
    },

    closeCoefficientForm() {
      this.coefficientFormOpen = false;
      this.newCoefficient = {
        user_id: null,
        type_id: null,
        value: "1.00",
        effective_from: "",
        effective_to: "",
        comment: "",
      };
    },

    handleCoefEmployeeChange(option) {
      this.newCoefficient.user_id = option?.id || null;
    },

    handleCoefTypeChange(option) {
      this.newCoefficient.type_id = option?.id || null;
    },

    async submitCoefficient() {
      if (!this.canSubmitCoefficient) return;

      try {
        const c = this.newCoefficient;
        await createVacationCoefficient({
          user_id: c.user_id,
          type_id: c.type_id,
          value: parseFloat(c.value),
          effective_from: this.formatDate(c.effective_from),
          effective_to: c.effective_to ? this.formatDate(c.effective_to) : null,
          comment: c.comment || null,
        });
        this.closeCoefficientForm();
        await this.loadCoefficients();
      } catch (e) {
        console.error("Failed to create coefficient:", e);
      }
    },

    async removeCoefficient(id) {
      try {
        await deleteVacationCoefficient(id);
        await this.loadCoefficients();
      } catch (e) {
        console.error("Failed to delete coefficient:", e);
      }
    },

    updateTypeSetting(typeId, key, value) {
      const index = this.typeSettings.findIndex(t => t.id === typeId);
      if (index !== -1) {
        this.typeSettings = [
          ...this.typeSettings.slice(0, index),
          { ...this.typeSettings[index], [key]: value },
          ...this.typeSettings.slice(index + 1),
        ];
      }
    },

    async handleSave() {
      try {
        this.isSaving = true;

        // Save type settings
        for (const setting of this.typeSettings) {
          await setVacationTypeSetting({
            type_id: setting.id,
            value: setting.defaultDays,
            display_warning: setting.displayWarning,
          });
        }

        // Save report separator
        await setVacationReportSeparator(this.reportSeparator ?? "/");

        // Save mandatory-leave minimum length
        await setMandatoryLeaveMinDays(parseInt(this.mandatoryMinDays, 10) || 14);
      } catch (e) {
        console.error("Failed to save settings:", e);
      } finally {
        this.isSaving = false;
      }
    },

    async checkAccess() {
      try {
        const response = true;
        if (!response) {
          this.accessDenied = true;
          return false;
        }
        return true;
      } catch (e) {
        console.error("Failed to check access:", e);
        this.accessDenied = true;
        return false;
      }
    },

    async init() {
      this.isLoading = true;

      const hasAccess = await this.checkAccess();
      if (!hasAccess) {
        this.isLoading = false;
        return;
      }

      await Promise.all([
        this.loadData(),
      ]);

      this.isLoading = false;
    },
  },
  mounted() {
    this.init();
  },
};
</script>

<style scoped>
.vacation-settings__content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding: 24px;
  overflow-y: auto;
}

.vacation-settings__loader {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
}

.vacation-settings__header {
  margin-bottom: 8px;
}

.vacation-settings__title {
  font-size: 24px;
  font-weight: 600;
  margin: 0 0 8px 0;
  color: var(--color-main-text);
}

.vacation-settings__description {
  font-size: 14px;
  color: var(--color-text-maxcontrast);
  margin: 0;
}

.vacation-settings__section {
  background-color: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  padding: 20px;
}

.vacation-settings__section-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.vacation-settings__section-icon {
  color: var(--color-primary-element);
}

.vacation-settings__section-title {
  font-size: 16px;
  font-weight: 600;
  margin: 0;
  color: var(--color-main-text);
}

.vacation-settings__section-description {
  font-size: 14px;
  color: var(--color-text-maxcontrast);
  margin: 0 0 16px 0;
}

.vacation-settings__section-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.vacation-settings__setting {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius);
}

.vacation-settings__setting-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.vacation-settings__setting-label {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-main-text);
}

.vacation-settings__setting-description {
  font-size: 13px;
  color: var(--color-text-maxcontrast);
}

.vacation-settings__type-card {
  padding: 16px;
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius);
}

.vacation-settings__separator-input {
  max-width: 120px;
}

.vacation-settings__field-hint {
  margin-top: 8px;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.vacation-settings__type-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.vacation-settings__type-dot {
  width: 20px;
  height: 20px;
  border-radius: 50%;
}

.vacation-settings__type-name {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-main-text);
}

.vacation-settings__type-fields {
  display: grid;
  grid-template-columns: 240px 140px auto;
  align-items: end;
  gap: 16px 20px;
}

@media (max-width: 768px) {
  .vacation-settings__type-fields {
    grid-template-columns: 1fr;
    align-items: stretch;
  }
}

.vacation-settings__type-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.vacation-settings__type-field--switch {
  flex-direction: row;
  align-items: center;
  gap: 12px;
  justify-self: start;
  padding-bottom: 6px;
}

.vacation-settings__type-label {
  font-size: 13px;
  color: var(--color-text-maxcontrast);
}

.vacation-settings__color-input {
  display: flex;
  gap: 8px;
}

.vacation-settings__color-picker {
  width: 40px;
  height: 36px;
  padding: 2px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  cursor: pointer;
}

.vacation-settings__color-text {
  flex: 1;
}


.vacation-settings__actions {
  display: flex;
  justify-content: flex-end;
}

.vacation-settings__info-text p {
  margin: 0;
  font-size: 14px;
  color: var(--color-main-text);
}

.vacation-settings__access-denied {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  text-align: center;
  padding: 48px 24px;
}

.vacation-settings__access-denied-icon {
  color: var(--color-error, #F44336);
  margin-bottom: 16px;
}

.vacation-settings__access-denied-title {
  margin: 0 0 8px 0;
  font-size: 20px;
  font-weight: 600;
  color: var(--color-main-text);
}

.vacation-settings__access-denied-description {
  margin: 0 0 24px 0;
  font-size: 14px;
  color: var(--color-text-maxcontrast);
  max-width: 400px;
}

.vacation-settings__coef-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.vacation-settings__coef-table thead th {
  text-align: left;
  padding: 8px 12px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  border-bottom: 1px solid var(--color-border);
}

.vacation-settings__coef-table tbody td {
  padding: 10px 12px;
  border-bottom: 1px solid var(--color-border);
  vertical-align: middle;
}

.vacation-settings__coef-table tbody tr:hover {
  background-color: var(--color-background-hover);
}

.vacation-settings__coef-type {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.vacation-settings__coef-comment {
  color: var(--color-text-maxcontrast);
  max-width: 240px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.vacation-settings__coef-actions {
  width: 1%;
  white-space: nowrap;
}

.vacation-settings__coef-empty {
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  margin: 0;
}

.vacation-settings__coef-toolbar {
  display: flex;
  justify-content: flex-start;
  margin-top: 12px;
}

.vacation-settings__coef-form {
  margin-top: 12px;
  padding: 16px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background-color: var(--color-background-dark);
}

.vacation-settings__coef-form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px 16px;
}

@media (max-width: 768px) {
  .vacation-settings__coef-form-grid {
    grid-template-columns: 1fr;
  }
}

.vacation-settings__coef-form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 16px;
}
</style>