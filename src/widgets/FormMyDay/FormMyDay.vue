/** * SPDX-FileCopyrightText: 2026 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <div class="contents" data-component-id="FormMyDay">
    <NcButton
      v-if="moduleExist('integrationwithextapps') === true"
      :aria-label="contextTranslate('Form my day', context)"
      @click="handleOpenGeneratedReports"
    >
      <template #icon>
        <CalendarToday />
      </template>
      {{ contextTranslate("Form my day", context) }}
    </NcButton>
    <GeneratedReportsAside
      :active="isGeneratedReportsActive"
      :loading="isGeneratedReportsLoading"
      :reports="generatedReports"
      :loaders="generatedReportsLoaders"
      :project-options="projectOptions"
      :date="openedForDate"
      @on-close="handleCloseGeneratedReports"
      @on-decline="handleDeclineGeneratedReport"
      @on-accept="handleAcceptGeneratedReport"
    />
  </div>
</template>

<script>
import { mapState } from "pinia";

import { NcButton } from "@nextcloud/vue";

import CalendarToday from "vue-material-design-icons/CalendarToday.vue";

import { GeneratedReportsAside } from "@/widgets/GeneratedReportsAside";

import { createUserTimeInfo } from "@/entities/timeInfo/api";
import { getGeneratedReports } from "@/entities/externalApps/api";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { handleRestErrors } from "@/shared/lib/helpers/errors";
import { generateUUID } from "@/shared/lib/helpers/hash";

import { useModulesStore } from "@/app/store/modules";

import { requestDate } from "./requestDate";

export default {
  name: "FormMyDay",
  components: {
    NcButton,
    CalendarToday,
    GeneratedReportsAside,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    date: {
      type: [Date, String],
      default: null,
    },
    projectOptions: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["on-accepted"],
  data: () => ({
    context: "user/time-tracking",
    isGeneratedReportsActive: false,
    isGeneratedReportsLoading: false,
    generatedReports: [],
    generatedReportsLoaders: [],
    openedForDate: "",
    generatedReportsRequestId: 0,
  }),
  computed: {
    dayToForm() {
      return requestDate(this.date);
    },
    ...mapState(useModulesStore, ["moduleExist"]),
  },
  methods: {
    handleShowSuccess() {
      this.$notify({
        text: this.contextTranslate("Record saved successfully"),
        type: "success",
        duration: 2 * 1000,
      });
    },
    async handleOpenGeneratedReports() {
      // Only the latest open may fill the panel: a slower response for a previous day is dropped.
      const requestId = ++this.generatedReportsRequestId;

      this.openedForDate = this.dayToForm;
      // Drop the previous day's list so an accept still in flight for it cannot empty and close this panel.
      this.generatedReports = [];
      this.isGeneratedReportsActive = true;
      this.isGeneratedReportsLoading = true;

      try {
        const { reports } = await getGeneratedReports({
          date: this.openedForDate,
        });

        if (requestId !== this.generatedReportsRequestId) {
          return;
        }

        this.generatedReports = reports.map((item) => ({
          ...item,
          uuid: generateUUID(),
        }));
      } catch (e) {
        if (requestId === this.generatedReportsRequestId) {
          handleRestErrors(e);
        }
      } finally {
        if (requestId === this.generatedReportsRequestId) {
          this.isGeneratedReportsLoading = false;
        }
      }
    },
    handleCloseGeneratedReports() {
      this.isGeneratedReportsActive = false;
    },
    handleRemoveGeneratedReportById(uuid) {
      const result = this.generatedReports.filter((item) => item.uuid !== uuid);

      this.generatedReports = result;

      if (result.length === 0) {
        this.isGeneratedReportsActive = false;
      }
    },
    handleDeclineGeneratedReport(uuid) {
      this.handleRemoveGeneratedReportById(uuid);
    },
    async handleAcceptGeneratedReport(payload) {
      try {
        this.generatedReportsLoaders.push(payload.uuid);

        await createUserTimeInfo(payload);

        this.handleShowSuccess();

        this.$emit("on-accepted", payload);
      } catch (e) {
        handleRestErrors(e);
      } finally {
        this.handleRemoveGeneratedReportById(payload.uuid);

        this.generatedReportsLoaders = this.generatedReportsLoaders.filter(
          (uuid) => uuid !== payload.uuid,
        );
      }
    },
  },
};
</script>
