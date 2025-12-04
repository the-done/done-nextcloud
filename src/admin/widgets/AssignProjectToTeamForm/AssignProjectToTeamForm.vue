/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <section v-if="loading === false">
    <form
      v-if="isActive === true"
      class="v-simple-inline-form"
      @submit.prevent="handleSubmit"
    >
      <VDropdown
        v-model="$v.formValues.project.$model"
        :options="projectOptions"
        :required="true"
        :error="errors.project"
        :ariaLabel="contextTranslate('Project', context)"
        :placeholder="contextTranslate( 'Project', context)"
        class="v-simple-inline-form__input"
      />
      <NcButton class="v-simple-inline-form__button" @click="handleCancel">
        {{ contextTranslate( 'Cancel', context) }}
      </NcButton>
      <NcButton
        native-type="submit"
        type="primary"
        class="v-simple-inline-form__button"
      >
        {{ contextTranslate( 'Add', context) }}
      </NcButton>
    </form>
    <NcButton
      v-else-if="projectOptions && projectOptions.length > 0"
      @click="toggleActive"
    >
      {{ contextTranslate( 'Add team to project', context) }}
    </NcButton>
    <NcNoteCard v-else type="warning" :style="{ margin: '0' }">
      <template #icon>
        <BookCog :size="20" />
      </template>
      {{ contextTranslate( 'No available projects.', context) }}
      <RouterLink
        :to="{ name: 'project-new' }"
        class="v-link v-link--underline"
      >
        {{ contextTranslate( 'Add project', context) }}
      </RouterLink>
    </NcNoteCard>
  </section>
</template>

<script>
import { NcButton, NcNoteCard } from "@nextcloud/vue";
import { required } from "vuelidate/lib/validators";

import BookCog from "vue-material-design-icons/BookCog.vue";

import VDropdown from "@/common/shared/components/VDropdown/VDropdown.vue";

import { FIELD_IS_REQUIRED_ERROR } from "@/common/shared/lib/constants";
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

export default {
  name: "AssignProjectToTeamForm",
  mixins: [contextualTranslationsMixin],
  props: {
    projectOptions: {
      type: Array,
      default: () => [],
    },
    loading: {
      type: Boolean,
      default: true,
    },
  },
  emits: ["onSubmit"],
  components: {
    NcButton,
    NcNoteCard,
    BookCog,
    VDropdown,
  },
  data() {
    return {
      context: 'admin/users',
      isActive: false,
      formValues: {
        project: null,
      },
    };
  },
  validations: {
    formValues: {
      project: {
        required,
      },
    },
  },
  computed: {
    errors() {
      const requiredFields = ["project"].reduce((accum, key) => {
        const field = this.$v.formValues[key];

        if (field.$invalid && field.$dirty && field.required === false) {
          return {
            ...accum,
            [key]: t('done', FIELD_IS_REQUIRED_ERROR),
          };
        }
        return accum;
      }, {});

      return requiredFields;
    },
  },
  methods: {
    t,
    toggleActive() {
      this.isActive = !this.isActive;
    },
    handleDropFrom() {
      this.formValues.project = null;

      this.$v.$reset();
    },
    handleCancel() {
      this.handleDropFrom();

      this.isActive = false;
    },
    handleSubmit() {
      this.$v.$touch();

      if (this.$v.$invalid === true) {
        return;
      }

      this.$emit("onSubmit", { ...this.formValues });

      this.handleCancel();
    },
  },
};
</script>
