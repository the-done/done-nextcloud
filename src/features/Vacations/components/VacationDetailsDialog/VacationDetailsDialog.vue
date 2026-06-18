/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VAside :value="open" :width="480" @input="handleClose">
    <template #title>
      {{ contextTranslate("Request details", context) }}
    </template>

    <div v-if="vacation" class="vacation-details">
      <div class="vacation-details__grid">
        <!-- Vacation Type -->
        <div class="vacation-details__field">
          <span class="vacation-details__label">
            {{ contextTranslate("Absence type", context) }}
          </span>
          <div class="vacation-details__value">
            <VacationTypeBadge
              :name="vacation.type_name"
              :color="vacation.type_color"
            />
          </div>
        </div>

        <!-- Status -->
        <div class="vacation-details__field">
          <span class="vacation-details__label">
            {{ contextTranslate("Status", context) }}
          </span>
          <div class="vacation-details__value">
            <VacationStatusBadge :status-id="vacation.status_id" />
          </div>
        </div>

        <!-- Date Start -->
        <div class="vacation-details__field">
          <span class="vacation-details__label">
            {{ contextTranslate("Start date", context) }}
          </span>
          <span class="vacation-details__value">
            {{ formatDate(vacation.date_start) }}
          </span>
        </div>

        <!-- Date End -->
        <div class="vacation-details__field">
          <span class="vacation-details__label">
            {{ contextTranslate("End date", context) }}
          </span>
          <span class="vacation-details__value">
            {{ formatDate(vacation.date_end) }}
          </span>
        </div>

        <!-- Days Count -->
        <div class="vacation-details__field">
          <span class="vacation-details__label">
            {{ contextTranslate("Days count", context) }}
          </span>
          <span class="vacation-details__value">
            {{ vacation.days_count }}
          </span>
        </div>

        <!-- Project -->
        <div class="vacation-details__field">
          <span class="vacation-details__label">
            {{ contextTranslate("Project", context) }}
          </span>
          <span class="vacation-details__value">
            {{ projectName }}
          </span>
        </div>
      </div>

      <!-- Comment -->
      <div v-if="vacation.comment" class="vacation-details__comment">
        <span class="vacation-details__label">
          {{ contextTranslate("Comment", context) }}
        </span>
        <div class="vacation-details__comment-text">
          {{ vacation.comment }}
        </div>
      </div>
    </div>
  </VAside>
</template>

<script>
import { VAside } from "@/shared/components";

import { VacationStatusBadge } from "../VacationStatusBadge";
import { VacationTypeBadge } from "../VacationTypeBadge";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { formatDateDisplay } from "@/entities/vacations/constants";

export default {
  name: "VacationDetailsDialog",
  components: {
    VAside,
    VacationStatusBadge,
    VacationTypeBadge,
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
    projects: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["close"],
  data: () => ({
    context: "vacations",
  }),
  computed: {
    projectName() {
      if (!this.vacation?.project_id) {
        return "-";
      }
      const project = this.projects.find(p => p.id === this.vacation.project_id);
      return project?.name || this.vacation.project_id;
    },
  },
  methods: {
    handleClose() {
      this.$emit("close");
    },
    formatDate(dateString) {
      return formatDateDisplay(dateString);
    },
  },
};
</script>

<style scoped>
.vacation-details {
  padding: 16px;
}

.vacation-details__grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.vacation-details__field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.vacation-details__label {
  font-size: 13px;
  color: var(--color-text-maxcontrast);
}

.vacation-details__value {
  font-size: 14px;
  color: var(--color-main-text);
}

.vacation-details__comment {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.vacation-details__comment-text {
  margin-top: 4px;
  padding: 12px;
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius);
  color: var(--color-main-text);
}

.vacation-details__footer {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.vacation-details__id {
  font-size: 12px;
  color: var(--color-text-lighter);
}

@media (max-width: 768px) {
  .vacation-details__grid {
    grid-template-columns: 1fr;
  }
}
</style>