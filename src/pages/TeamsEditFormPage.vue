/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPageContent>
    <VPagePadding>
      <VForm class="md:max-w-[425px]" @submit="handleSubmit">
        <VTextField
          v-model="formValues.name"
          :error="errors.name"
          :required="true"
          :label="contextTranslate('Title')"
        />
        <VTextField
          v-model="formValues.comment"
          :label="contextTranslate('Comment')"
        />
        <div class="flex justify-end">
          <NcButton native-type="submit">{{
            contextTranslate("Save")
          }}</NcButton>
        </div>
      </VForm>
    </VPagePadding>
  </VPageContent>
</template>

<script>
import { required } from "vuelidate/lib/validators";
import { NcButton } from "@nextcloud/vue";

import { VPageContent, VPagePadding } from "@/widgets";
import { VForm, VTextField } from "@/shared/components";

import { createTeam, updateTeam, fetchTeamBySlug } from "@/entities/teams/api";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { handleRestErrors } from "@/shared/lib/helpers/errors";

import { FIELD_IS_REQUIRED_ERROR } from "@/shared/lib/constants/validation";

export default {
  name: "TeamsEditFormPage",
  components: {
    NcButton,
    VPageContent,
    VPagePadding,
    VForm,
    VTextField,
  },
  mixins: [contextualTranslationsMixin],
  data: () => ({
    formValues: {
      name: "", // Title *
      comment: "", // Comment
    },
  }),
  validations: {
    formValues: {
      name: {
        required,
      },
    },
  },
  computed: {
    slug() {
      return this.$route.params.slug;
    },
    isEdit() {
      return Boolean(this.slug);
    },
    errors() {
      return {
        name:
          this.$v.formValues.name.$invalid && this.$v.formValues.name.$dirty
            ? this.contextTranslate(FIELD_IS_REQUIRED_ERROR)
            : "",
      };
    },
  },
  methods: {
    async handleCreate() {
      try {
        const { slug } = await createTeam(this.formValues);

        this.$router.push({ name: "team-staff", params: { slug } });
      } catch (e) {
        handleRestErrors(e);
      }
    },
    async handleUpdate() {
      try {
        await updateTeam(this.slug, this.formValues);

        this.$router.push({ name: "team-table" });
      } catch (e) {
        handleRestErrors(e);
      }
    },
    handleSubmit() {
      this.$v.$touch();

      if (this.$v.$invalid === true) {
        return;
      }

      if (this.isEdit === true) {
        this.handleUpdate();

        return;
      }

      this.handleCreate();
    },
    setFormValues(payload) {
      const availableKeys = Object.keys(this.formValues);

      const result = Object.keys(payload).reduce((accum, key) => {
        const value = payload[key];

        if (availableKeys.includes(key) === true) {
          return {
            ...accum,
            [key]: value,
          };
        }

        return accum;
      }, {});

      this.formValues = result;
    },
    async handleFetchData() {
      try {
        const { data } = await fetchTeamBySlug(this.slug);

        this.setFormValues(data);
      } catch (e) {
        console.error(e);
      }
    },
    async init() {
      if (this.isEdit === false) {
        return;
      }

      this.handleFetchData();
    },
  },
  mounted() {
    this.init();
  },
};
</script>
