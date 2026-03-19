/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate(pageTitle, context)"
          forceIconText
        >
          <template #icon>
            <Plus />
          </template>
        </NcBreadcrumb>
      </NcBreadcrumbs>
      <template #actions>
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
      </template>
    </VToolbar>
    <VPageLayout>
      <VPageContent>
        <TimeTrackingEditForm
          v-model="formValues"
          :project-options="projectOptions"
          :is-edit="isEdit"
          @onSubmit="handleSubmit"
          @onSubmitAndContinue="handleSubmitAndContinue"
        />
        <div class="border-t border-(--color-border) p-4">
          <h4 class="mt-0 mb-4 mb-2 text-xl font-medium">
            {{ contextTranslate("Weekly statistics", context) }}
          </h4>
          <TimeTrackingView
            :model-data="statisticsData"
            :totals="statisticsTotals"
            :day-disabled="true"
            :task-disabled="true"
            :show-titles="['week', 'day']"
          />
        </div>
      </VPageContent>
    </VPageLayout>
    <GeneratedReportsAside
      :active="isGeneratedReportsActive"
      :loading="isGeneratedReportsLoading"
      :reports="generatedReports"
      :loaders="generatedReportsLoaders"
      :project-options="projectOptions"
      @on-close="handleCloseGeneratedReports"
      @on-decline="handleDeclineGeneratedReport"
      @on-accept="handleAcceptGeneratedReport"
    />
  </VPage>
</template>

<script>
import { mapState } from "pinia";

import { NcBreadcrumbs, NcBreadcrumb, NcButton } from "@nextcloud/vue";
import {
  format,
  isMonday,
  isSunday,
  previousMonday,
  nextSunday,
} from "date-fns";

import Plus from "vue-material-design-icons/Plus.vue";
import CalendarToday from "vue-material-design-icons/CalendarToday.vue";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
  TimeTrackingEditForm,
  TimeTrackingView,
  GeneratedReportsAside,
} from "@/widgets";

import { VToolbar, VForm, VDatePicker } from "@/shared/components";

import {
  fetchUserTimeInfo,
  createUserTimeInfo,
  updateUserTimeInfo,
} from "@/entities/timeInfo/api";
import { fetchUserStatistics } from "@/entities/statistics/api";
import { getGeneratedReports } from "@/entities/externalApps/api";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { timeTrackingFormMixin } from "@/shared/lib/mixins/timeTrackingFormMixin";

import { handleRestErrors } from "@/shared/lib/helpers/errors";
import { generateUUID } from "@/shared/lib/helpers/hash";

import { SUBMIT_DATE_FORMAT } from "@/shared/lib/constants/date";

import { useModulesStore } from "@/app/store/modules";

export default {
  name: "DictionaryPositionsPage",
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcButton,
    Plus,
    CalendarToday,
    VPage,
    VPageLayout,
    VPageContent,
    VPagePadding,
    TimeTrackingEditForm,
    TimeTrackingView,
    GeneratedReportsAside,
    VToolbar,
    VForm,
    VDatePicker,
  },
  mixins: [timeTrackingFormMixin, contextualTranslationsMixin],
  data: () => ({
    context: "user/time-tracking",
    /** timeTrackingFormMixin
     *
     *  formValues
     */
    statisticsData: [],
    statisticsTotals: {},
    isGeneratedReportsActive: false,
    isGeneratedReportsLoading: false,
    generatedReports: [],
    generatedReportsLoaders: [],
  }),
  computed: {
    slug() {
      return this.$route.params.slug;
    },
    isEdit() {
      return Boolean(this.slug) === true;
    },
    pageTitle() {
      return this.isEdit === true
        ? this.contextTranslate("Edit record")
        : this.contextTranslate("Add record");
    },
    ...mapState(useModulesStore, ["moduleExist"]),
  },
  methods: {
    redirectToStatistics(response) {
      const searchDate = format(this.formValues.date, "yyyy-MM-dd");
      const newItemSlug = response?.data?.slug;

      this.$router.push({
        name: "time-tracking-statistics",
        query: {
          searchDate,
          newItemSlug: newItemSlug || undefined,
        },
      });
    },
    async handleFetchStatistics() {
      const date = new Date();
      const monday = isMonday(date) ? date : previousMonday(date);
      const sunday = isSunday(date) ? date : nextSunday(date);

      const date_from = format(monday, SUBMIT_DATE_FORMAT);
      const date_to = format(sunday, SUBMIT_DATE_FORMAT);

      try {
        const { data, totals } = await fetchUserStatistics({
          date_from,
          date_to,
        });

        this.statisticsData = data;
        this.statisticsTotals = totals;
      } catch (e) {
        console.error(e);
      }
    },
    async handleCreate(payload, callback) {
      try {
        const response = await createUserTimeInfo(payload);

        this.saveLastFormValues(payload); // timeTrackingFormMixin

        if (callback) {
          callback(response);

          return;
        }

        this.redirectToStatistics(response);
      } catch (e) {
        handleRestErrors(e);
      }
    },
    async handleUpdate(payload) {
      try {
        await updateUserTimeInfo(this.slug, payload);

        this.redirectToStatistics();
      } catch (e) {
        handleRestErrors(e);
      }
    },
    handleSubmit({ payload }, callback) {
      if (this.isEdit === true) {
        this.handleUpdate(payload);

        return;
      }

      this.handleCreate(payload, callback);
    },
    handleShowSuccess() {
      this.$notify({
        text: this.contextTranslate("Record saved successfully"),
        type: "success",
        duration: 2 * 1000,
      });
    },
    handleSubmitAndContinue({ payload, $v }) {
      this.handleSubmit({ payload }, () => {
        this.formValues.task_link = "";
        this.formValues.description = "";
        this.formValues.comment = "";

        $v.$reset();

        this.handleShowSuccess();
        this.handleFetchStatistics();
      });
    },

    async handleOpenGeneratedReports() {
      this.isGeneratedReportsActive = true;
      this.isGeneratedReportsLoading = true;

      try {
        const { reports } = await getGeneratedReports({
          date: format(new Date(), SUBMIT_DATE_FORMAT),
        });

        this.generatedReports = reports.map((item) => ({
          ...item,
          uuid: generateUUID(),
        }));
      } catch (e) {
        handleRestErrors(e);
      } finally {
        this.isGeneratedReportsLoading = false;
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

        await this.handleFetchStatistics();

        this.handleShowSuccess();
      } catch (e) {
        handleRestErrors(e);
      } finally {
        this.handleRemoveGeneratedReportById(payload.uuid);

        this.generatedReportsLoaders = this.generatedReportsLoaders.filter(
          (uuid) => uuid !== payload.uuid,
        );
      }
    },
    async handleFetchData() {
      try {
        const { data } = await fetchUserTimeInfo(this.slug);

        this.setFormValues({ ...data }); // timeTrackingFormMixin
      } catch (e) {
        console.error(e);
      }
    },
    async init() {
      try {
        await this.handleFetchDictionaries(); // timeTrackingFormMixin
        await this.handleFetchStatistics();

        if (this.isEdit === true) {
          this.handleFetchData();

          return;
        }

        const { query } = this.$route;
        const { minutes: queryMinutes, project_id: queryProject } = query;

        this.setFormValues(query); // timeTrackingFormMixin

        if (queryMinutes && queryProject) {
          return;
        }

        // Load cached values only if there are no URL parameters
        await this.loadCachedValues(); // timeTrackingFormMixin
      } catch (e) {
        console.error(e);
      }
    },
  },
  mounted() {
    this.init();
  },
};
</script>
