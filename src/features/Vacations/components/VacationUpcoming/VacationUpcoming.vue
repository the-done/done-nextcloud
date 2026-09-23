/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div v-if="vacations.length > 0" class="vacation-upcoming">
    <h3 class="vacation-upcoming__title">
      {{ contextTranslate("Upcoming paid leave", context) }}
    </h3>

    <div class="vacation-upcoming__list">
      <div
        v-for="(vacation, index) in vacations"
        :key="vacation.id"
        class="vacation-upcoming__item"
      >
        <template v-if="index === 0">
          <div v-if="vacation.is_active" class="vacation-upcoming__row">
            <span class="vacation-upcoming__badge vacation-upcoming__badge--active">
              {{ contextTranslate("In progress", context) }}
            </span>
            <span class="vacation-upcoming__text">
              {{ contextTranslate("until", context) }} {{ formatDate(vacation.date_end) }}
            </span>
          </div>
          <div v-else class="vacation-upcoming__row">
            <span class="vacation-upcoming__label">
              {{ contextTranslate("Nearest vacation", context) }}:
            </span>
            <span class="vacation-upcoming__text">
              {{ formatDate(vacation.date_start) }} — {{ formatDate(vacation.date_end) }}
            </span>
          </div>
        </template>
        <template v-else>
          <div class="vacation-upcoming__row">
            <span class="vacation-upcoming__text vacation-upcoming__text--secondary">
              {{ formatDate(vacation.date_start) }} — {{ formatDate(vacation.date_end) }}
            </span>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { formatDateDisplay } from "@/entities/vacations/constants";

export default {
  name: "VacationUpcoming",
  mixins: [contextualTranslationsMixin],
  props: {
    vacations: {
      type: Array,
      default: () => [],
    },
  },
  data: () => ({
    context: "vacations",
  }),
  methods: {
    formatDate(dateString) {
      return formatDateDisplay(dateString);
    },
  },
};
</script>

<style scoped>
.vacation-upcoming {
  background-color: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  padding: 16px;
}

.vacation-upcoming__title {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 16px 0;
  color: var(--color-main-text);
}

.vacation-upcoming__list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.vacation-upcoming__item {
  display: flex;
  flex-direction: column;
}

.vacation-upcoming__row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.vacation-upcoming__badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  white-space: nowrap;
}

.vacation-upcoming__badge--active {
  background-color: var(--color-success-light, rgba(76, 175, 80, 0.15));
  color: var(--color-border-success, #4CAF50);
}

.vacation-upcoming__label {
  font-size: 14px;
  color: var(--color-text-maxcontrast);
  white-space: nowrap;
}

.vacation-upcoming__text {
  font-size: 14px;
  color: var(--color-main-text);
}

.vacation-upcoming__text--secondary {
  color: var(--color-text-maxcontrast);
}
</style>