/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <span
    :class="['vacation-status-badge', `vacation-status-badge--${statusClass}`]"
    :style="customStyle"
  >
    {{ contextTranslate(statusKey, context) }}
  </span>
</template>

<script>
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import {
  VACATION_STATUS,
  getStatusKey,
  getStatusColor,
} from "@/entities/vacations/constants";

export default {
  name: "VacationStatusBadge",
  mixins: [contextualTranslationsMixin],
  props: {
    // Support both string status and legacy numeric statusId
    status: {
      type: String,
      default: null,
    },
    statusId: {
      type: [Number, String],
      default: null,
    },
    useCustomColor: {
      type: Boolean,
      default: false,
    },
  },
  data: () => ({
    context: "vacations",
  }),
  computed: {
    // Normalize status - prefer string status, fallback to statusId
    normalizedStatus() {
      return this.status || this.statusId;
    },
    statusKey() {
      return getStatusKey(this.normalizedStatus);
    },
    statusColor() {
      return getStatusColor(this.normalizedStatus);
    },
    statusClass() {
      switch (this.normalizedStatus) {
        case VACATION_STATUS.PENDING:
        case VACATION_STATUS.SUBMITTED:
          return "pending";
        case VACATION_STATUS.APPROVED:
          return "approved";
        case VACATION_STATUS.REJECTED:
          return "rejected";
        case VACATION_STATUS.RETURNED:
          return "returned";
        default:
          return "default";
      }
    },
    customStyle() {
      if (this.useCustomColor) {
        return {
          backgroundColor: `${this.statusColor}20`,
          color: this.statusColor,
          borderColor: `${this.statusColor}50`,
        };
      }
      return {};
    },
  },
};
</script>

<style scoped>
.vacation-status-badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  border: 1px solid transparent;
  white-space: nowrap;
}

.vacation-status-badge--pending {
  background-color: rgba(255, 193, 7, 0.15);
  color: var(--color-main-text, #FFC107);
  border-color: rgba(255, 193, 7, 0.3);
}

.vacation-status-badge--approved {
  background-color: rgba(76, 175, 80, 0.15);
  color: var(--color-main-text, #4CAF50);
  border-color: rgba(76, 175, 80, 0.3);
}

.vacation-status-badge--rejected {
  background-color: rgba(244, 67, 54, 0.15);
  color: var(--color-main-text, #F44336);
  border-color: rgba(244, 67, 54, 0.3);
}

.vacation-status-badge--partial {
  background-color: rgba(33, 150, 243, 0.15);
  color: var(--color-main-text, #2196F3);
  border-color: rgba(33, 150, 243, 0.3);
}

.vacation-status-badge--returned {
  background-color: rgba(33, 150, 243, 0.15);
  color: var(--color-main-text, #2196F3);
  border-color: rgba(33, 150, 243, 0.3);
}

.vacation-status-badge--default {
  background-color: var(--color-background-dark);
  color: var(--color-text-maxcontrast);
}
</style>