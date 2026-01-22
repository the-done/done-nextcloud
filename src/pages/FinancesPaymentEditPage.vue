/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage>
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
          :name="contextTranslate('Payments', context)"
          :to="{ name: 'finances-payment-table' }"
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
            :permissions-form-name="permissionsFormName"
            @on-field-input="handleFieldInput"
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
  </VPage>
</template>

<script>
import { NcBreadcrumb, NcBreadcrumbs, NcButton } from "@nextcloud/vue";
import { mapState } from "pinia";
import { required, integer, minValue } from "vuelidate/lib/validators";

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

import { usePermissionStore } from "@/app/store/permission";

import {
  fetchPaymentTypes,
  createPayment,
  updatePayment,
  fetchPaymentBySlug,
  fetchSimpleUsers,
  fetchCustomers,
} from "@/entities/finances/api";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { pageFormCreatorMixin } from "@/shared/lib/mixins/pageFormCreatorMixin";

import { redirectNotFoundPage } from "@/shared/lib/helpers/navigation";

import {
  FIELD_IS_REQUIRED_ERROR,
  FIELD_WRONG_VALUE_ERROR,
} from "@/shared/lib/constants/validation";

export default {
  name: "FinancesPaymentEditPage",
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
      isLoading: false,
      context: "admin/finances",
      permissionsFormName: "payment_card",
      paymentTypes: [],
      users: [],
      customers: [],
      formValues: {
        date: null,
        amount: "",
        type_id: null,
        description: "",
        payee: "",
        payer: "",
        INN: "",
        employee_id: null,
        customer_id: null,
        comment: "",
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
        return this.contextTranslate("Create payment", this.context);
      }

      if (this.isLoading === true) {
        return "";
      }

      return String(this.slug);
    },
    staticFieldsDescriptor() {
      return [
        {
          key: "type_id",
          type: "select",
          label: this.contextTranslate("Payment type", this.context),
          options: this.paymentTypes,
          valueLabel: "name",
          required: true,
        },
        {
          key: "date",
          type: "date",
          label: this.contextTranslate("Payment date", this.context),
          required: true,
        },
        {
          key: "amount",
          type: "text",
          label: this.contextTranslate("Payment amount", this.context),
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
          key: "INN",
          type: "text",
          label: this.contextTranslate("INN", this.context),
          required: true,
        },
        {
          key: "payee",
          type: "text",
          label: this.contextTranslate("Payee", this.context),
          required: this.formValues.type_id?.id === "2",
          hidden: this.formValues.type_id?.id !== "2",
        },
        {
          key: "payer",
          type: "text",
          label: this.contextTranslate("Payer", this.context),
          required: this.formValues.type_id?.id === "1",
          hidden: this.formValues.type_id?.id !== "1",
        },
        {
          key: "description",
          type: "textarea",
          label: this.contextTranslate("Description of payment", this.context),
          required: true,
        },
        {
          key: "employee_id",
          type: "select",
          label: this.contextTranslate("Employee", this.context),
          options: this.users,
          valueLabel: "name",
          userSelect: true,
          hidden: this.formValues.type_id?.id !== "2",
        },
        {
          key: "customer_id",
          type: "select",
          label: this.contextTranslate("Customer", this.context),
          options: this.customers,
          valueLabel: "name",
          hidden: this.formValues.type_id?.id !== "1",
        },
        {
          key: "comment",
          type: "textarea",
          label: this.contextTranslate("Comment", this.context),
          required: false,
        },
      ];
    },
  },

  methods: {
    setFormValues(data) {
      const result = Object.keys(this.formValues).reduce((accum, key) => {
        const value = data[key];

        if (key === "date" && value) {
          return {
            ...accum,
            [key]: new Date(value),
          };
        }

        if (key === "type_id" && value) {
          const itemValue = this.paymentTypes.find(
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

        if (key === "customer_id" && value) {
          const itemValue = this.customers.find(
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

    async loadPayment() {
      try {
        const { data } = await fetchPaymentBySlug({
          slug: this.slug,
          slug_type: "id",
        });

        this.setFormValues(data);
      } catch (e) {
        console.log(e);

        redirectNotFoundPage(this.$router);
      }
    },

    async loadPaymentTypes() {
      try {
        const { data } = await fetchPaymentTypes();

        this.paymentTypes = data.map((type) => ({
          ...type,
          label: type.name,
          value: type.id,
        }));
      } catch (e) {
        console.log(e);
      }
    },

    async loadUsers() {
      try {
        const response = await fetchSimpleUsers();

        if (response && response.data && Array.isArray(response.data)) {
          this.users = response.data.map((user) => ({
            ...user,
            value: user.id,
          }));
        }
      } catch (e) {
        console.log(e);
      }
    },

    async loadCustomers() {
      try {
        const response = await fetchCustomers();

        this.customers = response.data.map((customer) => ({
          ...customer,
          value: customer.id,
        }));
      } catch (e) {
        console.log(e);
      }
    },

    async handleFetchData() {
      this.isLoading = true;

      try {
        await this.loadPaymentTypes();
        await this.loadUsers();
        await this.loadCustomers();

        if (this.isEdit) {
          await this.loadPayment();
        }
      } catch (e) {
        console.log(e);
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

      // Prepare data for sending, extracting id values
      const dataToSend = { ...this.formValues };

      dataToSend.type_id = dataToSend.type_id.id;

      // Extract id for customer_id if it is an object
      if (
        dataToSend.customer_id &&
        typeof dataToSend.customer_id === "object" &&
        dataToSend.customer_id.id
      ) {
        dataToSend.customer_id = dataToSend.customer_id.id;
      }

      // Extract id for employee_id if it is an object
      if (
        dataToSend.employee_id &&
        typeof dataToSend.employee_id === "object" &&
        dataToSend.employee_id.id
      ) {
        dataToSend.employee_id = dataToSend.employee_id.id;
      }

      this.isLoading = true;

      try {
        if (this.isEdit) {
          await updatePayment({
            slug: this.slug,
            slug_type: "id",
            data: dataToSend,
          });
        } else {
          await createPayment(dataToSend);
        }

        this.$router.push({ name: "finances-payment-table" });
      } catch (e) {
        console.error(e);
      } finally {
        this.isLoading = false;
      }
    },

    handleFieldInput({ key, value }) {
      if (key !== "type_id") {
        return;
      }

      if (!value || typeof value === "undefined") {
        this.formValues.payee = "";
        this.formValues.payer = "";
        this.formValues.employee_id = "";
        this.formValues.customer_id = "";

        this.$v.$reset();

        return;
      }

      let paymentTypeId = value?.id;

      if (paymentTypeId === "2") {
        this.formValues.payer = "";
        this.formValues.customer_id = "";
      }

      if (paymentTypeId === "1") {
        this.formValues.payee = "";
        this.formValues.employee_id = "";
      }

      this.$v.$reset();
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
