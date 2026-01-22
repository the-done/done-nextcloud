/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VForm @submit="handleSubmit">
    <VTextField
      v-model="$v.value.name.$model"
      :error="errors.name"
      :required="true"
      :label="contextTranslate('Title', context)"
    />
    <VTextField
      v-model="$v.value.sort.$model"
      :error="errors.sort"
      :required="true"
      :label="contextTranslate('Sorting', context)"
    />
    <NcButton native-type="submit" class="mt-4">
      {{ contextTranslate("Save", context) }}
    </NcButton>
  </VForm>
</template>

<script>
import { NcButton } from "@nextcloud/vue";
import { required, minValue } from "vuelidate/lib/validators";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { VForm, VTextField } from "@/shared/components";

import { FIELD_IS_REQUIRED_ERROR } from "@/shared/lib/constants/validation";

const REQUIRED_FIELDS = ["name", "sort"];

export default {
  name: "DictionaryEditForm",
  mixins: [contextualTranslationsMixin],
  emits: ["on-submit"],
  components: {
    NcButton,
    VForm,
    VTextField,
  },
  props: {
    value: {
      type: Object,
      default: {
        name: "",
        sort: "",
      },
    },
  },
  data: () => ({
    context: "admin/users",
  }),
  validations: {
    value: {
      name: {
        required,
      },
      sort: {
        required,
        minValue: minValue(0),
      },
    },
  },
  computed: {
    errors() {
      const requiredFields = REQUIRED_FIELDS.reduce((accum, key) => {
        const field = this.$v.value[key];

        if (field.$invalid && field.$dirty && field.required === false) {
          return {
            ...accum,
            [key]: this.contextTranslate(FIELD_IS_REQUIRED_ERROR, this.context),
          };
        }
        return accum;
      }, {});

      return requiredFields;
    },
  },
  methods: {
    async handleSubmit() {
      this.$v.$touch();

      if (this.$v.$invalid === true) {
        return;
      }

      this.$emit("on-submit");
    },
  },
};
</script>
