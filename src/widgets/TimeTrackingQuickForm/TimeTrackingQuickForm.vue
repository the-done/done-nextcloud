/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VAside :value="active" :width="768" class="z-1500" @input="handleClose">
    <div
      v-if="moduleExist('integrationwithextapps') === true"
      class="flex justify-end p-4 pb-0"
    >
      <FormMyDay
        :date="formValues.date"
        :project-options="projectOptions"
        @on-accepted="handleFormMyDayAccepted"
      />
    </div>
    <TimeTrackingEditForm
      v-model="formValues"
      :project-options="projectOptions"
      @onSubmit="handleSubmit"
      @onSubmitAndContinue="handleSubmitAndContinue"
    />
  </VAside>
</template>

<script>
import { mapState } from "pinia";

import { TimeTrackingEditForm } from "@/widgets/TimeTrackingEditForm";
import { FormMyDay } from "@/widgets/FormMyDay";
import { VAside } from "@/shared/components";

import { createUserTimeInfo } from "@/entities/timeInfo/api";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { timeTrackingFormMixin } from "@/shared/lib/mixins/timeTrackingFormMixin";

import { handleRestErrors } from "@/shared/lib/helpers/errors";

import { useModulesStore } from "@/app/store/modules";

export default {
  name: "TimeTrackingQuickForm",
  components: {
    TimeTrackingEditForm,
    FormMyDay,
    VAside,
  },
  emits: ["on-close", "on-submit-success", "on-submit-and-continue-success"],
  props: {
    active: {
      type: Boolean,
      default: false,
    },
    date: {
      type: String,
      default: "",
    },
  },
  mixins: [timeTrackingFormMixin, contextualTranslationsMixin],
  data: () => ({
    context: "user/time-tracking",
    /** timeTrackingFormMixin
     *
     *  formValues
     *  projectOptions
     */
  }),
  computed: {
    ...mapState(useModulesStore, ["moduleExist"]),
  },
  methods: {
    handleClose() {
      this.$emit("on-close");
    },
    handleFormMyDayAccepted() {
      // The record is already created and announced by FormMyDay; let the page refresh and keep the form open.
      this.$emit("on-submit-and-continue-success");
    },
    handleClearForm() {
      this.formValues.task_link = "";
      this.formValues.description = "";
      this.formValues.comment = "";
    },
    async handleCreate({ payload, $v, onSuccess, onError }) {
      try {
        const response = await createUserTimeInfo(payload);

        this.saveLastFormValues(payload); // timeTrackingFormMixin
        this.handleClearForm();

        $v.$reset();

        if (onSuccess) {
          onSuccess(response);
        }
      } catch (e) {
        console.error(e);

        handleRestErrors(e);

        if (onError) {
          onError(e);
        }
      }
    },
    handleSubmit({ payload, $v }) {
      this.handleCreate({
        payload,
        $v,
        onSuccess: (response) => {
          this.handleClose();

          this.$emit("on-submit-success", { payload, response });
        },
      });
    },
    handleSubmitAndContinue({ payload, $v }) {
      this.handleCreate({
        payload,
        $v,
        onSuccess: (response) => {
          this.$notify({
            text: this.contextTranslate(
              "Record saved successfully",
              this.context
            ),
            type: "success",
            duration: 2 * 1000,
          });

          this.$emit("on-submit-and-continue-success", response);
        },
      });
    },
    async init() {
      try {
        await this.handleFetchDictionaries(); // timeTrackingFormMixin
        await this.loadCachedValues(); // timeTrackingFormMixin
      } catch (e) {
        handleRestErrors(e);

        console.error(e);
      }
    },
  },
  mounted() {
    this.init();
  },
  watch: {
    date(nextValue) {
      this.setFormValues({
        date: nextValue,
      }); // timeTrackingFormMixin
    },
  },
};
</script>
