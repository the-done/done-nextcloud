/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPageContent>
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('Finances', context)"
          :to="{ name: 'finances-home' }"
          forceIconText
        >
          <template #icon>
            <CashMultiple />
          </template>
        </NcBreadcrumb>
        <NcBreadcrumb
          :name="contextTranslate('Contract parameters group', context)"
          :to="{ name: 'finances-contract-parameter-group-table' }"
          forceIconText
        />
        <NcBreadcrumb :name="pageTitle" />
        <NcBreadcrumb
          v-if="isLoading === false && isEdit === true"
          :name="contextTranslate('Edit', context)"
        />
      </NcBreadcrumbs>
    </VToolbar>
    <VPageLayout class="relative">
      <VLoader v-if="isInitLoading || isLoading" absolute />
      <VPageContent v-if="isInitLoading === false">
        <VPagePadding>
          <FormCreator
            v-model="formValues"
            :descriptor="descriptor"
            :errors="errors"
            :validation="$v"
            @on-submit="handleSubmit"
          >
            <template #parameters></template>
            <template #footer>
              <div class="v-flex v-flex--justify-end">
                <NcButton native-type="submit">
                  {{ contextTranslate("Save", context) }}
                </NcButton>
              </div>
            </template>
          </FormCreator>
        </VPagePadding>
      </VPageContent>
    </VPageLayout>
  </VPageContent>
</template>

<script>
import { NcBreadcrumb, NcBreadcrumbs, NcButton } from "@nextcloud/vue";
import { required } from "vuelidate/lib/validators";

import BookCog from "vue-material-design-icons/BookCog.vue";
import CashMultiple from "vue-material-design-icons/CashMultiple.vue";

import {
  VPage,
  VPageContent,
  VPagePadding,
  VPageLayout,
  VPageAsideNavigation,
  FormCreator,
} from "@/widgets";

import { VToolbar, VLoader } from "@/shared/components";

import {
  fetchContractParameterGroupBySlug,
  createContractParameterGroup,
  updateContractParameterGroup,
  fetchGroupParameters,
  assignParameterToGroup,
  removeParameterFromGroup,
} from "@/entities/contractParameterGroups/api";
import { fetchContractParameters } from "@/entities/contractParameters/api";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { pageFormCreatorMixin } from "@/shared/lib/mixins/pageFormCreatorMixin";

import { redirectNotFoundPage } from "@/shared/lib/helpers/navigation";

import { FIELD_IS_REQUIRED_ERROR } from "@/shared/lib/constants/validation";

export default {
  name: "FinancesContractParameterGroupEditPage",
  mixins: [pageFormCreatorMixin, contextualTranslationsMixin],
  components: {
    CashMultiple,
    NcBreadcrumbs,
    BookCog,
    NcBreadcrumb,
    NcButton,
    FormCreator,
    VPage,
    VPageLayout,
    VPageAsideNavigation,
    VPageContent,
    VPagePadding,
    VToolbar,
    VLoader,
  },
  data() {
    return {
      isInitLoading: true,
      isLoading: true,
      context: "admin/finances",
      parameterOptions: [],
      formValues: {
        name: "",
        parameters: [],
      },
      initFormValues: {
        name: "",
        parameters: [],
      },
    };
  },
  computed: {
    slug() {
      return this.$route.params.slug;
    },
    isEdit() {
      return Boolean(this.slug);
    },
    pageTitle() {
      if (this.isEdit === false) {
        return this.contextTranslate(
          "Create contract parameters group",
          this.context
        );
      }

      if (this.isLoading === true) {
        return "";
      }

      return String(this.slug);
    },
    staticFieldsDescriptor() {
      return [
        {
          key: "name",
          type: "text",
          label: this.contextTranslate("Group name", this.context),
          required: true,
          validation: {
            required: {
              fn: required,
              message: this.contextTranslate(FIELD_IS_REQUIRED_ERROR),
            },
          },
        },
        {
          key: "parameters",
          type: "select",
          label: this.contextTranslate("Parameters", this.context),
          options: this.parameterOptions,
          multiple: true,
          keepOpen: true,
        },
      ];
    },
  },
  methods: {
    setFormValues(data) {
      const result = Object.keys(this.formValues).reduce((accum, key) => {
        const value = data[key];

        if (key === "parameters") {
          return {
            ...accum,
            [key]: value.reduce((accum, item) => {
              const foundItem = this.parameterOptions.find(
                (option) => option.slug === item.slug
              );

              if (foundItem !== undefined) {
                return [...accum, foundItem];
              }

              return accum;
            }, []),
          };
        }

        return {
          ...accum,
          [key]: value ?? "",
        };
      }, {});

      this.formValues = { ...result };
      this.initFormValues = { ...result };
    },
    async handleFetchParameterOptions() {
      try {
        const { data } = await fetchContractParameters();

        this.parameterOptions = data;
      } catch (e) {
        console.log(e);
      }
    },
    async handleFetchData() {
      try {
        if (this.isEdit === false) {
          return;
        }

        const promises = [
          fetchContractParameterGroupBySlug(this.slug),
          fetchGroupParameters(this.slug),
        ];

        const [{ data }, { data: parameterValues }] = await Promise.all(
          promises
        );

        this.setFormValues({ ...data, parameters: parameterValues });
      } catch (e) {
        console.log(e);

        redirectNotFoundPage(this.$router);
      } finally {
        this.isInitLoading = false;
        this.isLoading = false;
      }
    },
    async handleCreate(data) {
      try {
        const {
          data: { slug },
        } = await createContractParameterGroup(data);

        if (this.formValues.parameters.length > 0) {
          const promisses = this.formValues.parameters.map((item) =>
            assignParameterToGroup({
              group_id: slug,
              parameter_id: item.slug,
            })
          );

          await Promise.all(promisses);
        }

        this.$router.push({
          name: "finances-contract-parameter-group-table",
        });
      } catch (e) {
        throw new Error(e);
      }
    },
    async handleUpdate(data) {
      try {
        await updateContractParameterGroup({
          slug: this.slug,
          data,
        });

        const { parameters } = this.formValues;
        const { parameters: initParameters } = this.initFormValues;

        const newParameters = parameters.filter((item) => {
          const foundItem = initParameters.find(
            (initItem) => initItem.slug === item.slug
          );

          return foundItem === undefined;
        });

        const removedParameters = initParameters.filter((initItem) => {
          const foundItem = parameters.find(
            (item) => initItem.slug === item.slug
          );

          return foundItem === undefined;
        });

        const newPromises = newParameters.map((item) =>
          assignParameterToGroup({
            group_id: this.slug,
            parameter_id: item.slug,
          })
        );

        const removePromises = removedParameters.map((item) =>
          removeParameterFromGroup({
            group_id: this.slug,
            parameter_id: item.slug,
          })
        );

        const promises = [...newPromises, ...removePromises];

        await Promise.all(promises);
      } catch (e) {
        throw new Error(e);
      }
    },
    async handleSubmit() {
      if (this.$v.$invalid) {
        this.$v.$touch();

        return;
      }

      const data = Object.keys(this.formValues).reduce((accum, key) => {
        const value = this.formValues[key];

        if (key === "parameters") {
          return accum;
        }

        return {
          ...accum,
          [key]: value,
        };
      }, {});

      this.isLoading = true;

      try {
        if (this.isEdit === true) {
          await this.handleUpdate(data);

          return;
        }

        await this.handleCreate(data);
      } catch (e) {
        console.error(e);
      } finally {
        this.isLoading = false;
      }
    },
    async init() {
      await this.handleFetchParameterOptions();

      this.handleFetchData();
    },
  },
  async mounted() {
    this.init();
  },
};
</script>
