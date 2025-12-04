/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPageContent class="relative">
    <VLoader v-if="isInitLoading || isLoading" absolute />
    <VPagePadding v-if="isInitLoading === false">
      <div
        v-if="hasWarnings === true"
        class="v-flex v-flex--column v-flex--gap-1 mb-4"
      >
        <NcNoteCard
          v-if="nexcloudUserOptions && nexcloudUserOptions.length === 0"
          type="warning"
          :style="{ margin: '0' }"
        >
          <template #icon>
            <AccountMultiple :size="20" />
          </template>
          {{ contextTranslate("No available Nextcloud users.", context) }}
        </NcNoteCard>
        <NcNoteCard
          v-if="positionOptions && positionOptions.length === 0"
          type="warning"
          :style="{ margin: '0' }"
        >
          <template #icon>
            <BookOpenVariant :size="20" />
          </template>
          {{ contextTranslate("Positions dictionary is empty.", context) }}
          <RouterLink
            :to="{ name: 'dictionary-positions-new' }"
            class="v-link v-link--underline"
          >
            {{ contextTranslate("Add position", context) }}
          </RouterLink>
        </NcNoteCard>
        <NcNoteCard
          v-if="contractTypeOptions && contractTypeOptions.length === 0"
          type="warning"
          :style="{ margin: '0' }"
        >
          <template #icon>
            <BookOpenVariant :size="20" />
          </template>
          {{ contextTranslate("Contract types dictionary is empty.", context) }}
          <RouterLink
            :to="{ name: 'dictionary-contract-types-new' }"
            class="v-link v-link--underline"
          >
            {{ contextTranslate("Add contract type", context) }}
          </RouterLink>
        </NcNoteCard>
      </div>
      <FormCreator
        v-model="formValues"
        :descriptor="descriptor"
        :errors="errors"
        :validation="$v"
        :permissions-form-name="permissionsFormName"
        :sortable="isSortable"
        @on-submit="handleSubmit"
        @on-sort="handleSortFields"
        @on-delete-fields-ordering="handleDeleteFieldsOrdering"
      >
        <template #footer>
          <div class="v-flex v-flex--justify-end v-flex--gap-1">
            <NcButton native-type="submit">
              {{ contextTranslate("Save", context) }}
            </NcButton>
            <NcButton v-if="isEdit === false" @click="handleSubmitAndContinue">
              {{ contextTranslate("Save and continue", context) }}
            </NcButton>
          </div>
        </template>
      </FormCreator>
    </VPagePadding>
  </VPageContent>
</template>

<script>
import { NcButton, NcNoteCard } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";

import AccountMultiple from "vue-material-design-icons/AccountMultiple.vue";
import BookOpenVariant from "vue-material-design-icons/BookOpenVariant.vue";

import {
  createUser,
  updateUser,
  fetchUserBySlug,
} from "@/common/entities/users/api";

import { fetchFreeNextcloudUsers } from "@/admin/entities/nextcloud/api";
import { saveDynamicFieldsDataMultiple } from "@/admin/entities/dynamicFields/api";

import { pageFormCreatorMixin } from "@/common/shared/mixins/pageFormCreatorMixin";

import { VPageContent, VPagePadding } from "@/common/widgets/VPage";
import { FormCreator } from "@/common/widgets/FormCreator";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import VLoader from "@/common/shared/components/VLoader/VLoader.vue";

import {
  fetchPositionsDictionary,
  fetchContractTypesDictionary,
} from "@/admin/entities/dictionaries/api";

import { handleRestErrors } from "@/common/shared/lib/helpers";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { MAP_ENTITY_SOURCE } from "@/common/shared/lib/constants";

export default {
  name: "UsersEditFormPage",
  mixins: [pageFormCreatorMixin, contextualTranslationsMixin],
  components: {
    NcButton,
    NcNoteCard,
    VPageContent,
    VPagePadding,
    FormCreator,
    VToolbar,
    VLoader,
    AccountMultiple,
    BookOpenVariant,
  },
  data: () => ({
    context: "admin/users",
    permissionsFormName: "user_card",
    nexcloudUserOptions: [],
    positionOptions: [],
    contractTypeOptions: [],
    entityModel: {},
    isLoading: false,
    isDictionaryLoading: true,
    isSortable: true,
    source: MAP_ENTITY_SOURCE["user"],
    formValues: {
      user_id: null, // Nextcloud user UUID *
      user_display_name: "", // Nickname *
      lastname: "", // Employee lastname *
      name: "", // Employee name *
      middle_name: "", // Employee middle name *
      position_id: null, // Position *
      contract_type_id: null, // Contract type *
    },
    /**
     *  dynamicFieldsDescriptor: [],
     *
     *  pageFormCreatorMixin
     */
  }),
  computed: {
    /**
     *  descriptor: [],
     *
     *  pageFormCreatorMixin
     */
    staticFieldsDescriptor() {
      return [
        {
          key: "user_id",
          type: "select",
          label: this.contextTranslate("Nextcloud user", this.context),
          disabled: this.isEdit === true,
          options: this.nexcloudUserOptions,
          userSelect: true,
          required: true,
          transformDataForRest: this.getFieldSlugValue, // pageFormCreatorMixin
        },
        {
          key: "user_display_name",
          type: "text",
          label: this.contextTranslate("Nickname", this.context),
          required: false,
        },
        {
          key: "lastname",
          type: "text",
          label: this.contextTranslate("Lastname", this.context),
          required: false,
        },
        {
          key: "name",
          type: "text",
          label: this.contextTranslate("Name", this.context),
          required: true,
        },
        {
          key: "middle_name",
          type: "text",
          label: this.contextTranslate("Middle name", this.context),
          required: false,
        },
        {
          key: "position_id",
          type: "select",
          label: this.contextTranslate("Position", this.context),
          options: this.positionOptions,
          required: false,
          transformDataForRest: this.getFieldSlugValue, // pageFormCreatorMixin
        },
        {
          key: "contract_type_id",
          type: "select",
          label: this.contextTranslate("Contract type", this.context),
          options: this.contractTypeOptions,
          required: false,
          transformDataForRest: this.getFieldSlugValue, // pageFormCreatorMixin
        },
      ];
    },
    slug() {
      return this.$route.params.slug;
    },
    isEdit() {
      return Boolean(this.slug) === true;
    },
    hasWarnings() {
      if (this.isDictionaryLoading === true || this.isEdit === true) {
        return false;
      }

      return (
        (this.nexcloudUserOptions && this.nexcloudUserOptions.length === 0) ||
        (this.positionOptions && this.positionOptions.length === 0) ||
        (this.contractTypeOptions && this.contractTypeOptions.length === 0)
      );
    },
  },
  methods: {
    t,
    isEmptyValue(value) {
      return value === null || value === undefined || value === "";
    },
    async handleCreate(callback) {
      this.isLoading = true;

      try {
        const { staticFields, dynamicFields } = this.transformDataForRest(); // pageFormCreatorMixin

        const response = await createUser(staticFields);

        const {
          data: { id },
        } = response;

        if (dynamicFields?.length) {
          await saveDynamicFieldsDataMultiple({
            record_id: id,
            data: dynamicFields,
          });
        }

        if (callback) {
          callback(response);

          return;
        }

        this.$router.push({ name: "staff-table" });
      } catch (e) {
        handleRestErrors(e);

        this.isLoading = false;
      }
    },
    async handleUpdate() {
      this.isLoading = true;

      try {
        const { staticFields, dynamicFields } = this.transformDataForRest(); // pageFormCreatorMixin

        await updateUser({
          slug: this.slug,
          slug_type: this.entityModel.slug_type,
          data: staticFields,
        });

        if (dynamicFields?.length) {
          await saveDynamicFieldsDataMultiple({
            record_id: this.entityModel.id,
            data: dynamicFields,
          });
        }

        this.$router.push({ name: "staff-table" });
      } catch (e) {
        handleRestErrors(e);

        this.isLoading = false;
      }
    },
    handleSubmit(callback) {
      this.$v.$touch();

      if (this.$v.$invalid === true) {
        return;
      }

      if (this.isEdit === true) {
        this.handleUpdate();

        return;
      }

      this.handleCreate(callback);
    },
    handleSubmitAndContinue() {
      this.handleSubmit((response) => {
        const slug = response?.data?.slug;

        if (slug) {
          this.$router.push({ name: "staff-roles", params: { slug } });

          return;
        }

        this.$router.push({ name: "staff-table" });
      });
    },
    setStaticFormValues(payload) {
      const result = this.descriptor.reduce((accum, item) => {
        const { key } = item;
        const value = payload[key];
        const keyExist = key in this.formValues;

        if (keyExist === false) {
          return accum;
        }

        if (item.type === "date" || item.type === "datetime") {
          return {
            ...accum,
            [key]: value ? new Date(value) : null,
          };
        }

        if (key === "position_id") {
          const objectValue = this.positionOptions.find(
            (item) => item.slug === value
          );

          if (objectValue) {
            return {
              ...accum,
              [key]: objectValue,
            };
          }

          return {
            ...accum,
            [key]: null,
          };
        }

        if (key === "contract_type_id") {
          const objectValue = this.contractTypeOptions.find(
            (item) => item.slug === value
          );

          if (objectValue) {
            return {
              ...accum,
              [key]: objectValue,
            };
          }

          return {
            ...accum,
            [key]: null,
          };
        }

        if (key === "gender" && value !== null) {
          return {
            ...accum,
            [key]: String(value),
          };
        }

        return {
          ...accum,
          [key]: value,
        };
      }, {});

      this.formValues = result;
    },
    async handleFetchData() {
      try {
        const { data } = await fetchUserBySlug({ slug: this.slug });

        this.entityModel = data;

        await this.initFormWithDynamicFields({
          itemId: data.id,
          data,
        }); // formDynamicFieldsMixin
      } catch (e) {
        console.log(e);
      }
    },
    async fetchDictionaries() {
      this.isDictionaryLoading = true;

      try {
        const promises = [
          fetchFreeNextcloudUsers(),
          fetchPositionsDictionary(),
          fetchContractTypesDictionary(),
        ];

        const [
          { data: nexcloudUserOptions },
          { data: positionOptions },
          { data: contractTypeOptions },
        ] = await Promise.all(promises);

        this.nexcloudUserOptions = nexcloudUserOptions.map((item) => {
          const { name, uuid } = item;

          return {
            ...item,
            displayName: name,
            subname: uuid,
          };
        });
        this.positionOptions = positionOptions;
        this.contractTypeOptions = contractTypeOptions;
      } catch (e) {
        console.log(e);
      } finally {
        this.isDictionaryLoading = false;
      }
    },
    async init() {
      await this.fetchDictionaries();

      if (this.isEdit === false) {
        await this.initFormWithDynamicFields(); // formDynamicFieldsMixin

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

<style scoped>
.nextcloud-uuid {
  color: var(--color-text-lighter);
  margin-bottom: 8px;
}
</style>
