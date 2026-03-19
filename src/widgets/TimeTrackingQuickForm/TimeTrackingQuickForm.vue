/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VAside :value="active" :width="768" class="z-1500" @input="handleClose">
    <TimeTrackingEditForm
      v-model="formValues"
      :project-options="projectOptions"
      @onSubmit="handleSubmit"
      @onSubmitAndContinue="handleSubmitAndContinue"
    />
  </VAside>
</template>

<script>
import { TimeTrackingEditForm } from "@/widgets/TimeTrackingEditForm";
import { VAside } from "@/shared/components";

import { createUserTimeInfo } from "@/entities/timeInfo/api";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { timeTrackingFormMixin } from "@/shared/lib/mixins/timeTrackingFormMixin";

import { handleRestErrors } from "@/shared/lib/helpers/errors";

export default {
  name: "TimeTrackingQuickForm",
  components: {
    TimeTrackingEditForm,
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
  methods: {
    handleClose() {
      this.$emit("on-close");
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
