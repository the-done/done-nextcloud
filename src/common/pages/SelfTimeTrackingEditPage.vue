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
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb } from "@nextcloud/vue";
import {
  format,
  isMonday,
  isSunday,
  previousMonday,
  nextSunday,
} from "date-fns";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import Plus from "vue-material-design-icons/Plus.vue";

import {
  fetchUserTimeInfo,
  createUserTimeInfo,
  updateUserTimeInfo,
} from "@/common/entities/timeInfo/api";

import { fetchUserStatistics } from "@/common/entities/statistics/api";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
} from "@/common/widgets/VPage";
import { TimeTrackingEditForm } from "@/common/widgets/TimeTrackingEditForm";
import { TimeTrackingView } from "@/common/widgets/TimeTrackingView";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import VForm from "@/common/shared/components/VForm/VForm.vue";
import VDatePicker from "@/common/shared/components/VDatePicker/VDatePicker.vue";

import { timeTrackingFormMixin } from "@/common/shared/mixins/timeTrackingFormMixin";

import { handleRestErrors } from "@/common/shared/lib/helpers";
import { SUBMIT_DATE_FORMAT } from "@/common/shared/lib/constants";
//TODO: Why we need this?
import { useModulesStore } from "@/admin/app/store/modules";
import {mapState} from "pinia";

export default {
  name: "DictionaryPositionsPage",
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    Plus,
    VPage,
    VPageLayout,
    VPageContent,
    VPagePadding,
    TimeTrackingEditForm,
    TimeTrackingView,
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
     *  projectOptions
     */
    statisticsData: [],
    statisticsTotals: {},
  }),
  computed: {
    ...mapState(useModulesStore, ["moduleExist"]),
    slug() {
      return this.$route.params.slug;
    },
    isEdit() {
      return Boolean(this.slug) === true;
    },
    pageTitle() {
      return this.isEdit === true
        ? t("done", "Edit record")
        : t("done", "Add record");
    },
  },
  methods: {
    t,
    redirectToStatistics() {
      const searchDate = format(this.formValues.date, "yyyy-MM-dd");

      this.$router.push({
        name: "time-tracking-statistics",
        query: { searchDate },
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
        console.log(e);
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

        this.redirectToStatistics();
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
    handleSubmitAndContinue({ payload, $v }) {
      this.handleSubmit({ payload }, () => {
        this.formValues.task_link = "";
        this.formValues.description = "";
        this.formValues.comment = "";

        $v.$reset();

        this.$notify({
          text: t("done", "Record saved successfully"),
          type: "success",
          duration: 2 * 1000,
        });

        this.handleFetchStatistics();
      });
    },
    async handleFetchData() {
      try {
        const { data } = await fetchUserTimeInfo(this.slug);

        this.setFormValues({ ...data }); // timeTrackingFormMixin
      } catch (e) {
        console.log(e);
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
        console.log(e);
      }
    },
  },
  mounted() {
    this.init();
  },
};
</script>
