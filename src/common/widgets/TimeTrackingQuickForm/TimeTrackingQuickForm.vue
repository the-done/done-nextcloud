/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VAside :value="active" @input="handleClose">
    <TimeTrackingEditForm
      v-model="formValues"
      :project-options="projectOptions"
      @onSubmit="handleSubmit"
      @onSubmitAndContinue="handleSubmitAndContinue"
    />
  </VAside>
</template>

<script>
import { createUserTimeInfo } from "@/common/entities/timeInfo/api";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { TimeTrackingEditForm } from "@/common/widgets/TimeTrackingEditForm";

import { VAside } from "@/common/shared/components/VAside";

import { timeTrackingFormMixin } from "@/common/shared/mixins/timeTrackingFormMixin";

import { handleRestErrors } from "@/common/shared/lib/helpers";

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
    t,
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
        console.log(e);

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

        console.log(e);
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
