/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div class="vacation-balance">
    <div class="vacation-balance__list">
      <div
        v-for="balance in balancesList"
        :key="balance.type_id"
        class="vacation-balance__item"
      >
        <div class="vacation-balance__header">
          <div class="vacation-balance__type">
            <span
              class="vacation-balance__dot"
              :style="{ backgroundColor: balance.type_color }"
            />
            <span class="vacation-balance__type-name">{{ balance.type_name }}</span>
            <span
              v-if="balance.is_custom_limit"
              class="vacation-balance__custom-label"
            >
              ({{ contextTranslate("custom", context) }})
            </span>
          </div>
          <span class="vacation-balance__count">
            {{ balance.used }} / {{ balance.limit }} {{ contextTranslate("days", context) }}
          </span>
        </div>

        <div class="vacation-balance__progress">
          <div
            class="vacation-balance__progress-bar"
            :style="getProgressStyle(balance)"
          />
        </div>

        <div class="vacation-balance__details">
          <span class="vacation-balance__detail">
            {{ contextTranslate("Used", context) }}: {{ balance.used }}
          </span>
          <span class="vacation-balance__detail">
            {{ contextTranslate("Planned", context) }}: {{ balance.planned }}
          </span>
          <span
            :class="[
              'vacation-balance__detail',
              isWarning(balance) && 'vacation-balance__detail--warning'
            ]"
          >
            {{ contextTranslate("Remaining", context) }}: {{ balance.remaining }}
          </span>
        </div>
      </div>
    </div>

    <div v-if="balancesList.length === 0" class="vacation-balance__empty">
      {{ contextTranslate("No balance data available", context) }}
    </div>
  </div>
</template>

<script>
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "VacationBalance",
  mixins: [contextualTranslationsMixin],
  props: {
    balances: {
      type: Object,
      default: () => ({}),
    },
  },
  data: () => ({
    context: "vacations",
  }),
  computed: {
    balancesList() {
      return Object.values(this.balances);
    },
  },
  methods: {
    getPercentage(balance) {
      if (!balance.limit || balance.limit === 0) return 0;
      return Math.min((balance.used / balance.limit) * 100, 100);
    },
    isWarning(balance) {
      return balance.display_warning && this.getPercentage(balance) > 80;
    },
    getProgressStyle(balance) {
      const percentage = this.getPercentage(balance);
      const color = this.isWarning(balance) ? "#FF9800" : balance.type_color;

      return {
        width: `${percentage}%`,
        backgroundColor: color,
      };
    },
  },
};
</script>

<style scoped>
.vacation-balance {
  background-color: var(--color-main-background);
  padding: 16px;
}

.vacation-balance__title {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 16px 0;
  color: var(--color-main-text);
}

.vacation-balance__list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.vacation-balance__item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.vacation-balance__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.vacation-balance__type {
  display: flex;
  align-items: center;
  gap: 8px;
}

.vacation-balance__dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0;
}

.vacation-balance__type-name {
  font-size: 14px;
  color: var(--color-main-text);
}

.vacation-balance__custom-label {
  font-size: 12px;
  color: var(--color-primary-element);
}

.vacation-balance__count {
  font-size: 14px;
  color: var(--color-text-maxcontrast);
}

.vacation-balance__progress {
  height: 8px;
  background-color: var(--color-background-dark);
  border-radius: 4px;
  overflow: hidden;
}

.vacation-balance__progress-bar {
  height: 100%;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.vacation-balance__details {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.vacation-balance__detail--warning {
  color: var(--color-warning, #FF9800);
}

.vacation-balance__empty {
  text-align: center;
  color: var(--color-text-maxcontrast);
  padding: 16px;
}
</style>