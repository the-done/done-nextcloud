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
          :name="contextTranslate('Contract parameters', context)"
          :to="{ name: 'finances-contract-parameter-table' }"
          forceIconText
        />
        <NcBreadcrumb :name="pageTitle" />
        <NcBreadcrumb
          v-if="isEdit === true"
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
  FormCreator,
} from "@/widgets";

import { VToolbar, VLoader } from "@/shared/components";

import {
  fetchContractParameterBySlug,
  createContractParameter,
  updateContractParameter,
} from "@/entities/contractParameters/api";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { pageFormCreatorMixin } from "@/shared/lib/mixins/pageFormCreatorMixin";

import { redirectNotFoundPage } from "@/shared/lib/helpers/navigation";

import { CONTRACT_PARAMETER_TYPE_OPTIONS } from "@/entities/contractParameters/constants";
import { FIELD_IS_REQUIRED_ERROR } from "@/shared/lib/constants/validation";

export default {
  name: "FinancesContractEditPage",
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
    VPageContent,
    VPagePadding,
    VToolbar,
    VLoader,
  },
  data() {
    return {
      isInitLoading: true,
      isLoading: false,
      context: "admin/finances",
      typeOptions: CONTRACT_PARAMETER_TYPE_OPTIONS,
      formValues: {
        name: "",
        type_id: null,
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
        return this.contextTranslate("Create contract parameter", this.context);
      }

      if (this.isInitLoading === true) {
        return "";
      }

      return String(this.slug);
    },
    staticFieldsDescriptor() {
      return [
        {
          key: "name",
          type: "text",
          label: this.contextTranslate("Name", this.context),
          required: true,
          validation: {
            required: {
              fn: required,
              message: this.contextTranslate(FIELD_IS_REQUIRED_ERROR),
            },
          },
        },
        {
          key: "type_id",
          type: "select",
          valueLabel: "label",
          label: this.contextTranslate("Type", this.context),
          options: this.typeOptions,
          required: true,
          validation: {
            required: {
              fn: required,
              message: this.contextTranslate(FIELD_IS_REQUIRED_ERROR),
            },
          },
        },
      ];
    },
  },
  methods: {
    setFormValues(data) {
      const result = Object.keys(this.formValues).reduce((accum, key) => {
        const value = data[key];

        if (key === "type_id" && value) {
          const itemValue = this.typeOptions.find(
            (item) => String(item.value) === String(value)
          );

          if (!itemValue) {
            return {
              ...accum,
              [key]: null,
            };
          }

          return {
            ...accum,
            [key]: itemValue,
          };
        }

        return {
          ...accum,
          [key]: value ?? "",
        };
      }, {});

      this.formValues = result;
    },
    async handleFetchData() {
      this.isLoading = true;

      try {
        if (this.isEdit === false) {
          return;
        }

        const { data } = await fetchContractParameterBySlug(
          this.$route.params.slug
        );

        this.setFormValues(data);
      } catch (e) {
        console.log(e);

        redirectNotFoundPage(this.$router);
      } finally {
        this.isInitLoading = false;
        this.isLoading = false;
      }
    },
    async handleSubmit() {
      if (this.$v.$invalid) {
        this.$v.$touch();

        return;
      }

      const data = Object.keys(this.formValues).reduce((accum, key) => {
        const value = this.formValues[key];

        if (key === "type_id") {
          return {
            ...accum,
            [key]: value.value,
          };
        }

        return {
          ...accum,
          [key]: value,
        };
      }, {});

      this.isLoading = true;

      try {
        if (this.isEdit === true) {
          await updateContractParameter({
            slug: this.slug,
            data,
          });

          return;
        }

        await createContractParameter(data);

        this.$router.push({ name: "finances-contract-parameter-table" });
      } catch (e) {
        console.error(e);
      } finally {
        this.isLoading = false;
      }
    },
    init() {
      this.handleFetchData();
    },
  },
  async mounted() {
    this.init();
  },
};
</script>
