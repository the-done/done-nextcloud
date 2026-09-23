/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPageContent class="relative">
    <VLoader v-if="isInitLoading || isLoading" absolute />
    <VPagePadding v-if="isInitLoading === false">
      <FormCreator
        v-model="formValues"
        :descriptor="descriptor"
        :errors="errors"
        :validation="$v"
        @on-submit="handleSubmit"
      >
        <template #footer>
          <div class="flex justify-end">
            <NcButton native-type="submit">
              {{ contextTranslate("Save", context) }}
            </NcButton>
          </div>
        </template>
      </FormCreator>
    </VPagePadding>
  </VPageContent>
</template>

<script>
import { NcButton } from "@nextcloud/vue";
import { mapState } from "pinia";
import { required, integer, minValue } from "vuelidate/lib/validators";

import { VPageContent, VPagePadding, FormCreator } from "@/widgets";

import { VLoader } from "@/shared/components";

import { usePermissionStore } from "@/app/store/permission";

import { fetchSimpleUsers } from "@/entities/finances/api";
import {
  fetchContractBySlug,
  createContract,
  updateContract,
} from "@/entities/contracts/api";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { pageFormCreatorMixin } from "@/shared/lib/mixins/pageFormCreatorMixin";

import { redirectNotFoundPage } from "@/shared/lib/helpers/navigation";

import {
  FIELD_IS_REQUIRED_ERROR,
  FIELD_WRONG_VALUE_ERROR,
} from "@/shared/lib/constants/validation";

export default {
  name: "FinancesContractEditPage",
  mixins: [pageFormCreatorMixin, contextualTranslationsMixin],
  components: {
    NcButton,
    FormCreator,
    VPageContent,
    VPagePadding,
    VLoader,
  },
  data() {
    return {
      isInitLoading: true,
      isLoading: false,
      context: "admin/finances",
      paymentTypes: [],
      users: [],
      customers: [],
      formValues: {
        employee_id: null,
        start_date: null,
        end_date: null,
        is_hourly: false,
        number_of_hours: "",
        hourly_rate: "0",
        period_rate: "0",
        project_rate: "0",
      },
    };
  },
  computed: {
    ...mapState(usePermissionStore, [
      "canViewField",
      "canReadField",
      "canWriteField",
    ]),
    slug() {
      return this.$route.params.slug;
    },
    isEdit() {
      return Boolean(this.slug);
    },
    pageTitle() {
      if (this.isEdit === false) {
        return this.contextTranslate("Create contract", this.context);
      }

      if (this.isLoading === true) {
        return "";
      }

      return String(this.slug);
    },
    staticFieldsDescriptor() {
      return [
        {
          key: "employee_id",
          type: "select",
          label: this.contextTranslate("Employee", this.context),
          options: this.users,
          valueLabel: "name",
          userSelect: true,
          required: true,
          validation: {
            required: {
              fn: required,
              message: this.contextTranslate(FIELD_IS_REQUIRED_ERROR),
            },
          },
        },
        {
          key: "start_date",
          type: "date",
          label: this.contextTranslate("Contract start date", this.context),
          required: true,
        },
        {
          // Optional: employees can work on indefinite-term contracts, so the
          // contract end date must not be required.
          key: "end_date",
          type: "date",
          label: this.contextTranslate("Contract end date", this.context),
        },
        {
          key: "is_hourly",
          type: "checkbox",
          label: this.contextTranslate("Hourly pay", this.context),
        },
        {
          key: "number_of_hours",
          type: "text",
          label: this.contextTranslate("Number of hours", this.context),
          required: true,
          validation: {
            required: {
              fn: required,
              message: this.contextTranslate(FIELD_IS_REQUIRED_ERROR),
            },
            integer: {
              fn: integer,
              message: this.contextTranslate(FIELD_WRONG_VALUE_ERROR),
            },
            minValue: {
              fn: minValue(1),
              message: this.contextTranslate(FIELD_WRONG_VALUE_ERROR),
            },
          },
        },
        {
          key: "hourly_rate",
          type: "text",
          label: this.contextTranslate("Hourly rate", this.context),
          validation: {
            integer: {
              fn: integer,
              message: this.contextTranslate(FIELD_WRONG_VALUE_ERROR),
            },
          },
        },
        {
          key: "period_rate",
          type: "text",
          label: this.contextTranslate("Period rate", this.context),
          validation: {
            integer: {
              fn: integer,
              message: this.contextTranslate(FIELD_WRONG_VALUE_ERROR),
            },
          },
        },
        {
          key: "project_rate",
          type: "text",
          label: this.contextTranslate("Project rate", this.context),
          validation: {
            integer: {
              fn: integer,
              message: this.contextTranslate(FIELD_WRONG_VALUE_ERROR),
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

        if (["start_date", "end_date"].includes(key) === true && value) {
          return {
            ...accum,
            [key]: new Date(value),
          };
        }

        if (key === "is_hourly") {
          return {
            ...accum,
            [key]: Boolean(value),
          };
        }

        if (key === "employee_id" && value) {
          const itemValue = this.users.find(
            (item) => String(item.id) === String(value)
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
    async handleFetchEntityData() {
      try {
        const {
          data: { model_data },
        } = await fetchContractBySlug({
          slug: this.slug,
          slug_type: "id",
        });

        this.setFormValues(model_data);
      } catch (e) {
        console.error(e);

        redirectNotFoundPage(this.$router);
      }
    },
    async handleFetchUsers() {
      try {
        const response = await fetchSimpleUsers();

        if (response && response.data && Array.isArray(response.data)) {
          this.users = response.data.map((user) => ({
            ...user,
            value: user.id,
          }));
        }
      } catch (e) {
        console.error(e);
      }
    },
    async handleFetchData() {
      this.isLoading = true;

      try {
        await this.handleFetchUsers();

        if (this.isEdit === false) {
          return;
        }

        this.handleFetchEntityData();
      } catch (e) {
        console.error(e);
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

        if (key === "employee_id") {
          return {
            ...accum,
            [key]: value.id,
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
          await updateContract({
            slug: this.slug,
            data,
          });

          return;
        }

        await createContract(data);

        this.$router.push({ name: "finances-contract-table" });
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
