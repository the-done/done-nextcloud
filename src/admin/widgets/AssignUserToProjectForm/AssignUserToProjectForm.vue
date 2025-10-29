/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <section v-if="loading === false">
    <form
      v-if="isActive === true"
      class="flex flex-wrap gap-2 items-start"
      @submit.prevent="handleSubmit"
    >
      <div class="w-[260px]">
        <VDropdown
          v-model="$v.formValues.user.$model"
          :options="userOptions"
          :user-select="true"
          :required="true"
          :error="errors.user"
          :ariaLabel="contextTranslate('Employee', context)"
          :placeholder="contextTranslate('Employee', context)"
        />
      </div>
      <div class="w-[260px]">
        <VDropdown
          v-model="$v.formValues.role.$model"
          :options="computedRoleOptions"
          :required="true"
          :error="errors.role"
          :ariaLabel="contextTranslate('Role in project', context)"
          :placeholder="contextTranslate('Position', context)"
          @input="handleChangeRoleValue"
        />
      </div>
      <div class="flex gap-2">
        <NcButton @click="handleCancel">
          {{ contextTranslate("Cancel", context) }}
        </NcButton>
        <NcButton :disabled="loading" native-type="submit" type="primary">
          {{ contextTranslate("Add", context) }}
        </NcButton>
      </div>
    </form>
    <NcButton
      v-else-if="
        userOptions &&
        userOptions.length > 0 &&
        roleOptions &&
        roleOptions.length > 0
      "
      @click="toggleActive"
    >
      {{ contextTranslate("Add employee to project", context) }}
    </NcButton>
    <div class="flex flex-col gap-2" v-else>
      <NcNoteCard
        v-if="userOptions && userOptions.length === 0"
        type="warning"
        :style="{ margin: '0' }"
      >
        <template #icon>
          <Account :size="20" />
        </template>
        {{ contextTranslate("No available employees.", context) }}
        <RouterLink
          :to="{ name: 'staff-new' }"
          class="v-link v-link--underline"
        >
          {{ contextTranslate("Add employee", context) }}
        </RouterLink>
      </NcNoteCard>
      <NcNoteCard
        v-if="roleOptions && roleOptions.length === 0"
        type="warning"
        :style="{ margin: '0' }"
      >
        <template #icon>
          <BookOpenVariant :size="20" />
        </template>
        {{ contextTranslate("Project roles dictionary is empty.", context) }}
        <RouterLink
          :to="{ name: 'dictionary-roles-new' }"
          class="v-link v-link--underline"
        >
          {{ contextTranslate("Add role", context) }}
        </RouterLink>
      </NcNoteCard>
    </div>

    <NcModal
      v-if="isCreateRoleModalActive"
      name="create-role-modal"
      @close="handleCloseCreateRoleModal"
    >
      <div class="p-4">
        <div class="text-xl font-semibold mb-4">
          {{ t("done", "Add role") }}
        </div>
        <DictionaryEditForm
          v-model="createRoleFormValues"
          @on-submit="handleSubmitCreateRoleForm"
        />
      </div>
    </NcModal>
  </section>
</template>

<script>
import { t } from "@nextcloud/l10n";
import { required } from "vuelidate/lib/validators";

import { NcButton, NcNoteCard, NcModal } from "@nextcloud/vue";

import Account from "vue-material-design-icons/Account.vue";
import BookOpenVariant from "vue-material-design-icons/BookOpenVariant.vue";

import DictionaryEditForm from "@/admin/widgets/DictionaryEditForm/DictionaryEditForm.vue";

import VDropdown from "@/common/shared/components/VDropdown/VDropdown.vue";

import {
  getNextSortInDict,
  createDictionaryItem,
} from "@/admin/entities/dictionaries/api";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { FIELD_IS_REQUIRED_ERROR } from "@/common/shared/lib/constants";

const REQUIRED_FIELDS = ["user", "role"];
const DICTIONARY_NAME = "rolesDictionary";

export default {
  name: "AssignUserToProjectForm",
  mixins: [contextualTranslationsMixin],
  props: {
    userOptions: {
      type: Array,
      default: () => [],
    },
    roleOptions: {
      type: Array,
      default: () => [],
    },
    loading: {
      type: Boolean,
      default: true,
    },
  },
  emits: ["onSubmit", "onSubmitCreateRoleSuccess"],
  components: {
    NcButton,
    NcNoteCard,
    NcModal,
    Account,
    BookOpenVariant,
    VDropdown,
    DictionaryEditForm,
  },
  data() {
    return {
      context: "admin/projects",
      isActive: false,
      isCreateRoleModalActive: false,
      formValues: {
        user: null,
        role: null,
      },
      createRoleFormValues: {
        name: "",
        sort: "",
      },
    };
  },
  validations: {
    formValues: {
      user: {
        required,
      },
      role: {
        required,
      },
    },
  },
  computed: {
    computedRoleOptions() {
      return [
        {
          id: "create_role",
          name: `+ ${t("done", "Add role")}`,
        },
        ...this.roleOptions,
      ];
    },
    errors() {
      const requiredFields = REQUIRED_FIELDS.reduce((accum, key) => {
        const field = this.$v.formValues[key];

        if (field.$invalid && field.$dirty && field.required === false) {
          return {
            ...accum,
            [key]: t("done", FIELD_IS_REQUIRED_ERROR),
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
    async handleOpenCreateRoleModal() {
      try {
        const nextNumber = await getNextSortInDict(DICTIONARY_NAME);

        this.createRoleFormValues.sort = nextNumber;

        this.isCreateRoleModalActive = true;
      } catch (e) {
        console.log(e);
      }
    },
    handleCloseCreateRoleModal() {
      this.isCreateRoleModalActive = false;
    },
    handleChangeRoleValue(value) {
      if (value === null || value.id !== "create_role") {
        return;
      }

      this.formValues.role = null;

      this.handleOpenCreateRoleModal();
    },
    async handleSubmitCreateRoleForm() {
      try {
        this.handleCloseCreateRoleModal();

        const {
          data: { slug },
        } = await createDictionaryItem(DICTIONARY_NAME, {
          ...this.createRoleFormValues,
        });

        this.$emit("onSubmitCreateRoleSuccess", () => {
          const newRoleItem = this.roleOptions.find(
            (item) => item.slug === slug
          );

          if (!newRoleItem) {
            return;
          }

          this.formValues.role = newRoleItem;
        });
      } catch (e) {
        console.log(e);
      }
    },
    handleDropFrom() {
      this.formValues.user = null;
      this.formValues.role = null;

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
