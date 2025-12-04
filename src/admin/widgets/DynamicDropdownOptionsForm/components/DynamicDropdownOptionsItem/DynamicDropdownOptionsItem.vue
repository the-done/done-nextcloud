/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div class="flex items-start gap-2">
    <DragVertical
      v-if="isEdit === true"
      :size="20"
      data-handle
      class="cursor-grab"
    />
    <VTextField
      v-model="$v.formValues.label.$model"
      :error="errors.label"
      :required="true"
      :placeholder="contextTranslate('Text')"
    />
    <VButton @click="handleSubmitForm">
      {{ isEdit === true ? contextTranslate("Save") : contextTranslate("Add") }}
    </VButton>
    <VButton v-if="isEdit === true" variant="tertiary" @click="handleDelete">
      <template #icon>
        <TrashCanOutline />
      </template>
    </VButton>
  </div>
</template>

<script>
import { required } from "vuelidate/lib/validators";

import DragVertical from "vue-material-design-icons/DragVertical.vue";
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import VTextField from "@/common/shared/components/VTextField/VTextField.vue";
import VButton from "@/common/shared/components/VButton/VButton.vue";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { FIELD_IS_REQUIRED_ERROR } from "@/common/shared/lib/constants";

const REQUIRED_FIELDS = ["label"];
const INIT_FORM_VALUES = {
  label: "",
};

export default {
  name: "DynamicDropdownOptionsItem",
  emits: ["on-submit", "on-delete"],
  mixins: [contextualTranslationsMixin],
  components: {
    DragVertical,
    TrashCanOutline,
    VTextField,
    VButton,
  },
  props: {
    value: {
      type: Object,
      default: null,
    },
  },
  data() {
    return {
      formValues: {
        ...INIT_FORM_VALUES,
      },
    };
  },
  validations: {
    formValues: {
      label: {
        required,
      },
    },
  },
  computed: {
    isEdit() {
      if (this.value?.slug) {
        return true;
      }

      return false;
    },
    errors() {
      const requiredFields = REQUIRED_FIELDS.reduce((accum, key) => {
        const field = this.$v.formValues[key];

        if (field.$invalid && field.$dirty && field.required === false) {
          return {
            ...accum,
            [key]: this.contextTranslate(FIELD_IS_REQUIRED_ERROR),
          };
        }
        return accum;
      }, {});

      return {
        ...requiredFields,
      };
    },
  },
  methods: {
    handleDropForm() {
      this.formValues = { ...INIT_FORM_VALUES };

      this.$v.$reset();
    },
    setFormValues({ label }) {
      this.formValues.label = label;
    },
    handleSubmitForm() {
      this.$v.$touch();

      if (this.$v.$invalid === true) {
        return;
      }

      this.$emit("on-submit", {
        slug: this.value?.slug,
        ...this.formValues,
      });

      if (this.isEdit === true) {
        return;
      }

      this.handleDropForm();
    },
    handleDelete() {
      this.$emit("on-delete", {
        slug: this.value?.slug,
      });
    },
  },
  watch: {
    value: {
      handler: function (nextValue) {
        if (nextValue === null) {
          return;
        }

        this.setFormValues(nextValue);
      },
      immediate: true,
    },
  },
};
</script>
