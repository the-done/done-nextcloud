/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VAside :value="open" :width="480" @input="handleClose">
    <template #title>
      {{ contextTranslate("Change history", context) }}
    </template>

    <div class="vacation-history">
      <VLoader v-if="loading" class="vacation-history__loader" />

      <div v-else-if="history.length > 0" class="vacation-history__timeline">
        <div
          v-for="(record, index) in history"
          :key="record.id"
          class="vacation-history__item"
        >
          <!-- Timeline connector -->
          <div
            v-if="index !== history.length - 1"
            class="vacation-history__connector"
          />

          <!-- Timeline dot -->
          <div class="vacation-history__dot" />

          <!-- Content -->
          <div class="vacation-history__content">
            <!-- Event label -->
            <div class="vacation-history__event-label">
              {{ record.event_label }}
            </div>

            <!-- Status change -->
            <div v-if="record.status_after_name" class="vacation-history__detail">
              <span class="vacation-history__detail-label">
                {{ contextTranslate("Status", context) }}:
              </span>
              <template v-if="record.status_before_name">
                <span class="vacation-history__status-before">
                  {{ record.status_before_name }}
                </span>
                <ArrowRight class="vacation-history__arrow" :size="16" />
              </template>
              <span class="vacation-history__status-after">
                {{ record.status_after_name }}
              </span>
            </div>

            <!-- Line (stage) change -->
            <div v-if="record.line_after_name" class="vacation-history__detail">
              <span class="vacation-history__detail-label">
                {{ contextTranslate("Stage", context) }}:
              </span>
              <template v-if="record.line_before_name">
                <span class="vacation-history__status-before">
                  {{ record.line_before_name }}
                </span>
                <ArrowRight class="vacation-history__arrow" :size="16" />
              </template>
              <span class="vacation-history__stage-name">
                {{ record.line_after_name }}
              </span>
            </div>

            <!-- Comment -->
            <p v-if="record.comment && record.event_type !== 'status_changed'" class="vacation-history__comment">
              <span class="vacation-history__detail-label">
                {{ contextTranslate("Comment", context) }}:
              </span>
              {{ record.comment }}
            </p>

            <!-- Meta info -->
            <div class="vacation-history__meta">
              <span>{{ record.created_at_formatted }}</span>
              <span class="vacation-history__separator">•</span>
              <span>{{ record.changed_by_name }}</span>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="vacation-history__empty">
        {{ contextTranslate("Change history is empty", context) }}
      </div>
    </div>
  </VAside>
</template>

<script>
import ArrowRight from "vue-material-design-icons/ArrowRight.vue";

import { VAside, VLoader } from "@/shared/components";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { fetchVacationHistory } from "@/entities/vacations/api";
import { getStatusKey, formatDateDisplay } from "@/entities/vacations/constants";
import { fetchOptions } from "@/entities/agreement/api";

export default {
  name: "VacationHistoryDialog",
  components: {
    VAside,
    ArrowRight,
    VLoader,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    open: {
      type: Boolean,
      default: false,
    },
    vacationId: {
      type: String,
      default: null,
    },
  },
  emits: ["close"],
  data: () => ({
    context: "vacations",
    loading: false,
    history: [],
    eventTypeOptions: {},
    actionTypeOptions: [],
  }),
  watch: {
    open: {
      immediate: true,
      handler(newVal) {
        if (newVal && this.vacationId) {
          this.loadHistory();
        }
      },
    },
    vacationId: {
      handler(newVal) {
        if (this.open && newVal) {
          this.loadHistory();
        }
      },
    },
  },
  methods: {
    handleClose() {
      this.$emit("close");
    },
    async loadHistory() {
      if (!this.vacationId) return;

      try {
        this.loading = true;
        const [response, optionsResponse] = await Promise.all([
          fetchVacationHistory(this.vacationId),
          fetchOptions(),
        ]);

        const options = optionsResponse.data || {};
        this.eventTypeOptions = options.eventTypes || {};
        this.actionTypeOptions = options.actionTypes || [];

        this.history = (response.data || []).map((record) => this.formatRecord(record));
      } catch (error) {
        console.error("Failed to load vacation history:", error);
        this.history = [];
      } finally {
        this.loading = false;
      }
    },
    formatRecord(record) {
      return {
        ...record,
        status_before_name: record.status_before
          ? this.contextTranslate(getStatusKey(record.status_before), this.context)
          : null,
        status_after_name: record.status_after
          ? this.contextTranslate(getStatusKey(record.status_after), this.context)
          : null,
        event_label: this.getEventLabel(record),
        created_at_formatted: record.created_at
          ? formatDateDisplay(record.created_at)
          : "",
      };
    },
    getEventTypeName(eventType) {
      return this.eventTypeOptions[eventType] || eventType;
    },
    getActionTypeName(actionType) {
      const option = this.actionTypeOptions.find(o => o.id === actionType);
      return option?.name || actionType;
    },
    getEventLabel(record) {
      const eventLabel = this.getEventTypeName(record.event_type);

      if (record.event_type === "action_performed" && record.action) {
        return `${eventLabel}: ${this.getActionTypeName(record.action)}`;
      }

      return eventLabel;
    },
  },
};
</script>

<style scoped>
.vacation-history {
  min-height: 150px;
  padding: 16px;
}

.vacation-history__loader {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 150px;
}

.vacation-history__timeline {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.vacation-history__item {
  position: relative;
  display: flex;
  gap: 16px;
  padding: 12px;
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius);
}

.vacation-history__connector {
  position: absolute;
  left: 17px;
  top: 100%;
  width: 2px;
  height: 12px;
  background-color: var(--color-border);
}

.vacation-history__dot {
  flex-shrink: 0;
  width: 10px;
  height: 10px;
  margin-top: 4px;
  border-radius: 50%;
  background-color: var(--color-primary-element);
}

.vacation-history__content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.vacation-history__detail {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  font-size: 14px;
}

.vacation-history__detail-label {
  color: var(--color-text-maxcontrast);
  font-weight: 500;
}

.vacation-history__status-before {
  font-size: 14px;
  color: var(--color-text-maxcontrast);
}

.vacation-history__arrow {
  color: var(--color-text-lighter);
}

.vacation-history__status-after {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-main-text);
}

.vacation-history__comment {
  font-size: 14px;
  color: var(--color-main-text);
  margin: 0;
}

.vacation-history__meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: var(--color-text-lighter);
}

.vacation-history__separator {
  color: var(--color-text-lighter);
}

.vacation-history__event-label {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-main-text);
}

.vacation-history__stage-name {
  color: var(--color-main-text);
}

.vacation-history__empty {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 150px;
  color: var(--color-text-maxcontrast);
}
</style>