/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('Vacations', context)"
          :to="{ name: 'vacations-requests' }"
          forceIconText
        >
          <template #icon>
            <PalmTree />
          </template>
        </NcBreadcrumb>
        <NcBreadcrumb :name="contextTranslate('Request', context)" />
      </NcBreadcrumbs>
    </VToolbar>

    <VScrollArea class="vacation-card__content">
      <div v-if="isLoading" class="vacation-card__loader">
        <VLoader />
      </div>

      <div v-else-if="notFound" class="vacation-card__empty">
        <FileDocumentOutline :size="48" class="vacation-card__empty-icon" />
        <p class="vacation-card__empty-text">
          {{ contextTranslate("Request not found", context) }}
        </p>
        <NcButton type="primary" @click="$router.push({ name: 'vacations-requests' })">
          {{ contextTranslate("Back to requests", context) }}
        </NcButton>
      </div>

      <div v-else-if="vacation" class="vacation-card">
        <div class="vacation-card__header">
          <h1 class="vacation-card__title">
            {{ vacation.user_name }}
          </h1>
          <VacationStatusBadge :status-id="vacation.status_id" />
        </div>

        <div class="vacation-card__grid">
          <div class="vacation-card__field">
            <span class="vacation-card__label">{{ contextTranslate("Absence type", context) }}</span>
            <div class="vacation-card__value">
              <VacationTypeBadge :name="vacation.type_name" :color="vacation.type_color" />
            </div>
          </div>

          <div class="vacation-card__field">
            <span class="vacation-card__label">{{ contextTranslate("Days count", context) }}</span>
            <span class="vacation-card__value">{{ vacation.days_count }}</span>
          </div>

          <div class="vacation-card__field">
            <span class="vacation-card__label">{{ contextTranslate("Start date", context) }}</span>
            <span class="vacation-card__value">{{ formatDate(vacation.date_start) }}</span>
          </div>

          <div class="vacation-card__field">
            <span class="vacation-card__label">{{ contextTranslate("End date", context) }}</span>
            <span class="vacation-card__value">{{ formatDate(vacation.date_end) }}</span>
          </div>

          <div class="vacation-card__field">
            <span class="vacation-card__label">{{ contextTranslate("Project", context) }}</span>
            <span class="vacation-card__value">{{ vacation.project_name || "—" }}</span>
          </div>
        </div>

        <div v-if="vacation.comment" class="vacation-card__comment">
          <span class="vacation-card__label">{{ contextTranslate("Comment", context) }}</span>
          <div class="vacation-card__comment-text">{{ vacation.comment }}</div>
        </div>
      </div>
    </VScrollArea>
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb, NcButton } from "@nextcloud/vue";

import PalmTree from "vue-material-design-icons/PalmTree.vue";
import FileDocumentOutline from "vue-material-design-icons/FileDocumentOutline.vue";

import { VPage } from "@/widgets";
import { VToolbar, VScrollArea, VLoader } from "@/shared/components";

import { VacationStatusBadge } from "@/features/Vacations/components/VacationStatusBadge";
import { VacationTypeBadge } from "@/features/Vacations/components/VacationTypeBadge";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { formatDateDisplay } from "@/entities/vacations/constants";
import { fetchVacation } from "@/entities/vacations/api";

export default {
  name: "VacationCardPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcButton,
    PalmTree,
    FileDocumentOutline,
    VPage,
    VToolbar,
    VScrollArea,
    VLoader,
    VacationStatusBadge,
    VacationTypeBadge,
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
    notFound: false,
    vacation: null,
  }),
  watch: {
    "$route.params.slug": {
      immediate: true,
      handler() {
        this.loadVacation();
      },
    },
  },
  methods: {
    formatDate(dateString) {
      return formatDateDisplay(dateString);
    },
    async loadVacation() {
      const slug = this.$route.params.slug;
      if (!slug) {
        this.notFound = true;
        return;
      }

      this.isLoading = true;
      this.notFound = false;

      try {
        const { data } = await fetchVacation(slug);
        if (data && data.id) {
          this.vacation = data;
        } else {
          this.vacation = null;
          this.notFound = true;
        }
      } catch (e) {
        console.error("Failed to load vacation:", e);
        this.vacation = null;
        this.notFound = true;
      } finally {
        this.isLoading = false;
      }
    },
  },
};
</script>

<style scoped>
.vacation-card__content {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 24px;
  overflow-y: auto;
}

.vacation-card__loader,
.vacation-card__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  min-height: 240px;
}

.vacation-card__empty-icon {
  color: var(--color-text-maxcontrast);
}

.vacation-card__empty-text {
  margin: 0;
  font-size: 15px;
  color: var(--color-text-maxcontrast);
}

.vacation-card {
  max-width: 640px;
  background-color: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large, var(--border-radius));
  padding: 24px;
}

.vacation-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
}

.vacation-card__title {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: var(--color-main-text);
}

.vacation-card__grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px 24px;
}

.vacation-card__field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.vacation-card__label {
  font-size: 13px;
  color: var(--color-text-maxcontrast);
}

.vacation-card__value {
  font-size: 14px;
  color: var(--color-main-text);
}

.vacation-card__comment {
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--color-border);
}

.vacation-card__comment-text {
  margin-top: 6px;
  padding: 12px;
  background-color: var(--color-background-dark);
  border-radius: var(--border-radius);
  color: var(--color-main-text);
}

.vacation-card__footer {
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.vacation-card__id {
  font-size: 12px;
  color: var(--color-text-lighter);
}

@media (max-width: 768px) {
  .vacation-card__grid {
    grid-template-columns: 1fr;
  }
}
</style>