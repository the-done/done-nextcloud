/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <div class="size-full overflow-hidden">
    <VPageContent class="relative">
      <VLoader v-if="isInitLoading || isLoading" absolute />
      <div
        :class="[
          'flex justify-end px-4 py-2 gap-4',
          tableData?.length === 0 && 'border-b border-(--color-border)',
        ]"
      >
        <div>
          <NcButton
            :aria-label="contextTranslate('Create')"
            @click="handleClickCreate"
          >
            <template #icon>
              <Plus />
            </template>
            {{ contextTranslate("Create") }}
          </NcButton>
        </div>
        <div class="w-[232px] max-w-full">
          <VDropdown
            :aria-label="contextTranslate('Select parameters group')"
            :placeholder="contextTranslate('Select parameters group')"
            :options="parameterGroupOptions"
            value-label="name"
            @input="handleSelectGroup"
          />
        </div>
      </div>
      <VTable
        v-if="isInitLoading === false"
        v-model="tableData"
        :columns="columns"
        :loading="isLoading"
      >
        <template #controls="{ row }">
          <NcActions :inline="1">
            <NcActionButton
              :aria-label="contextTranslate('Edit', context)"
              @click="() => handleClickEdit(row)"
            >
              <template #icon>
                <Pencil />
              </template>
            </NcActionButton>
            <NcActionButton :force-name="true" @click="() => handleDelete(row)">
              <template #icon>
                <TrashCanOutline />
                {{ contextTranslate("Delete", context) }}
              </template>
            </NcActionButton>
          </NcActions>
        </template>
        <template #value="{ row, value }">
          <span class="text-sm">
            {{ getTextValueForTable({ row, value }) }}
          </span>
        </template>
      </VTable>
    </VPageContent>
    <VAside v-model="isFormActive" :width="isFormula === true ? 768 : 425">
      <div class="relative h-full">
        <VLoader v-if="isFormLoading" absolute />
        <VForm class="p-4" @submit="handleSubmit">
          <VDropdown
            v-model="$v.formValues.contract_parameter_id.$model"
            :label="contextTranslate('Contract parameter')"
            :options="parameterOptions"
            :required="true"
            :disabled="isFormEditMode === true"
            :error="errors.contract_parameter_id"
            value-label="name"
            @input="handleChangeContractParameterId"
          />
          <ContractParameterFormulaEditor
            v-if="isFormula === true"
            v-model="formValues.value"
            :parameter-options="tableData"
          />
          <VTextField
            v-else
            v-model="$v.formValues.value.$model"
            :label="contextTranslate('Value', context)"
            :required="true"
            :error="errors.value"
            :caption="valueCaption"
          />
          <div class="flex justify-end">
            <NcButton native-type="submit">
              {{ contextTranslate("Save", context) }}
            </NcButton>
          </div>
        </VForm>
      </div>
    </VAside>
  </div>
</template>

<script>
import { NcButton, NcActions, NcActionButton } from "@nextcloud/vue";
import { required } from "vuelidate/lib/validators";
import { t } from "@nextcloud/l10n";

import Plus from "vue-material-design-icons/Plus.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import { VPageContent } from "@/widgets";
import { VTable, ContractParameterFormulaEditor } from "@/features";
import {
  VLoader,
  VAside,
  VForm,
  VTextField,
  VDropdown,
} from "@/shared/components";

import {
  fetchContractParameterValues,
  saveContractParameterValue,
  deleteContractParameterValue,
} from "@/entities/contractParameterValues/api";
import { fetchContractParameters } from "@/entities/contractParameters/api";
import {
  fetchContractParameterGroups,
  fetchGroupParameters,
} from "@/entities/contractParameterGroups/api";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { transformFormulaToString } from "@/shared/lib/helpers/contractFormula";
import { handleRestErrors } from "@/shared/lib/helpers/errors";
import { redirectNotFoundPage } from "@/shared/lib/helpers/navigation";

import {
  CONTRACT_PARAMETER_TYPES,
  MAP_CONTRACT_PARAMETER_TYPES,
} from "@/entities/contractParameters/constants";
import { FIELD_IS_REQUIRED_ERROR } from "@/shared/lib/constants/validation";

const INIT_FORM_VALUES = {
  contract_parameter_id: null,
  value: "",
};

export default {
  name: "FinancesContractEditParameterPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcButton,
    NcActions,
    NcActionButton,
    Plus,
    Pencil,
    TrashCanOutline,
    ContractParameterFormulaEditor,
    VPageContent,
    VTable,
    VLoader,
    VAside,
    VForm,
    VTextField,
    VDropdown,
  },
  data() {
    return {
      context: "admin/finances",
      isInitLoading: true,
      isLoading: false,
      isFormActive: false,
      isFormLoading: false,
      isFormEditMode: false,
      formValues: { ...INIT_FORM_VALUES },
      parameterOptions: [],
      parameterGroupOptions: [],
      tableData: [],
      columns: [
        {
          label: "",
          key: "controls",
        },
        {
          label: t("done", "Title"),
          key: "contract_parameter_name",
        },
        {
          label: t("done", "Value"),
          key: "value",
        },
        {
          label: t("done", "Calculated value"),
          key: "calculated_value",
        },
      ],
    };
  },
  validations: {
    formValues: {
      contract_parameter_id: { required },
      value: { required },
    },
  },
  computed: {
    slug() {
      return this.$route.params.slug;
    },
    valueCaption() {
      if (!this.formValues.contract_parameter_id) {
        return "";
      }

      const type =
        MAP_CONTRACT_PARAMETER_TYPES[
          this.formValues.contract_parameter_id.type_id
        ];

      if (!type) {
        return "";
      }

      return type.label;
    },
    isFormula() {
      if (!this.formValues.contract_parameter_id) {
        return false;
      }

      return (
        this.formValues.contract_parameter_id.type_id ===
        CONTRACT_PARAMETER_TYPES["formula"]
      );
    },
    errors() {
      return {
        contract_parameter_id:
          this.$v.formValues.contract_parameter_id.$invalid &&
          this.$v.formValues.contract_parameter_id.$dirty
            ? this.contextTranslate(FIELD_IS_REQUIRED_ERROR, this.context)
            : "",
        value:
          this.$v.formValues.value.$invalid && this.$v.formValues.value.$dirty
            ? this.contextTranslate(FIELD_IS_REQUIRED_ERROR, this.context)
            : "",
      };
    },
  },
  methods: {
    getTextValueForTable({ row, value }) {
      if (
        row.contract_parameter_type_id === CONTRACT_PARAMETER_TYPES["formula"]
      ) {
        try {
          const parsed = JSON.parse(value);

          return transformFormulaToString(parsed, this.tableData);
        } catch (e) {
          return value;
        }
      }

      return value;
    },
    handleOpenForm() {
      this.isFormActive = true;
    },
    handleCloseForm() {
      this.isFormActive = false;
    },
    handleDropForm() {
      this.formValues = { ...INIT_FORM_VALUES };

      this.$v.$reset();
    },
    handleClickCreate() {
      this.isFormEditMode = false;

      this.handleDropForm();
      this.handleOpenForm();
    },
    handleClickEdit({ contract_parameter_id, value }) {
      this.isFormEditMode = true;

      const option = this.parameterOptions.find((item) => {
        return item.slug === contract_parameter_id;
      });

      if (option !== undefined) {
        this.formValues.contract_parameter_id = option;

        if (option.type_id === CONTRACT_PARAMETER_TYPES["formula"]) {
          if (typeof value === "string") {
            try {
              this.formValues.value = JSON.parse(value);
            } catch (e) {
              this.formValues.value = [];

              this.$notify({
                text: e,
                type: "error",
                duration: 2 * 1000,
              });
            }
          } else {
            this.formValues.value = [];
          }
        } else {
          this.formValues.value = value;
        }
      } else {
        this.formValues.contract_parameter_id = null;
        this.formValues.value = "";
      }

      this.handleOpenForm();
    },
    handleChangeContractParameterId(value) {
      if (value?.type_id === CONTRACT_PARAMETER_TYPES["formula"]) {
        this.formValues.value = [];

        return;
      }

      this.formValues.value = "";
    },
    async handleSelectGroup({ slug }) {
      this.isLoading = true;

      try {
        const { data } = await fetchGroupParameters(slug);

        const mappedParameters = this.tableData.map(
          (item) => item.contract_parameter_id,
        );
        const newParameters = data.filter(
          (item) => mappedParameters.includes(item.slug) === false,
        );

        const promises = newParameters.map((item) =>
          saveContractParameterValue({
            contract_id: this.slug,
            contract_parameter_id: item.slug,
          }),
        );

        await Promise.all(promises);

        this.handleFetchData();
      } catch (e) {
        handleRestErrors(e);
      } finally {
        this.isLoading = false;
      }
    },
    async handleFetchDictionaries() {
      this.isFormLoading = true;

      try {
        const promises = [
          fetchContractParameters(),
          fetchContractParameterGroups(),
        ];
        const [{ data: parameterOptions }, { data: parameterGroupOptions }] =
          await Promise.all(promises);

        this.parameterOptions = parameterOptions;
        this.parameterGroupOptions = parameterGroupOptions;
      } catch (e) {
        handleRestErrors(e);
      } finally {
        this.isFormLoading = false;
      }
    },
    async handleFetchData() {
      this.isLoading = true;

      try {
        const {
          data: { values },
        } = await fetchContractParameterValues(this.slug);

        this.tableData = values;
      } catch (e) {
        console.error(e);

        redirectNotFoundPage(this.$router);
      } finally {
        this.isLoading = false;
        this.isInitLoading = false;
      }
    },
    async handleDelete({ slug }) {
      if (
        !confirm(
          this.contextTranslate(
            "Are you sure you want to delete the record?",
            this.context,
          ),
        )
      ) {
        return;
      }

      try {
        await deleteContractParameterValue(slug);

        this.handleFetchData();
      } catch (e) {
        handleRestErrors(e);
      }
    },
    async handleSubmit() {
      if (this.$v.$invalid) {
        this.$v.$touch();

        return;
      }

      try {
        const { contract_parameter_id, value } = this.formValues;

        await saveContractParameterValue({
          contract_id: this.slug,
          contract_parameter_id: contract_parameter_id.slug,
          value:
            contract_parameter_id.type_id ===
            CONTRACT_PARAMETER_TYPES["formula"]
              ? JSON.stringify(value)
              : value,
        });

        this.handleFetchData();
        this.handleCloseForm();
        this.handleDropForm();
      } catch (e) {
        handleRestErrors(e);
      }
    },
  },
  mounted() {
    this.handleFetchData();
    this.handleFetchDictionaries();
  },
};
</script>
