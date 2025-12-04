/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div class="md:max-w-[500px] flex flex-col md:flex-row">
    <div
      v-if="isAsideExist === true"
      class="flex gap-4 justify-end md:flex-col md:justify-start md:order-2 md:ml-4"
    >
      <NcActions force-menu>
        <template #icon>
          <Cog :size="20" />
        </template>
        <NcActionButton @click="handleDeleteFieldsOrdering">
          <template #icon>
            <SortVariantRemove :size="20" />
          </template>
          {{ contextTranslate("Reset sorting", context) }}
        </NcActionButton>
      </NcActions>
    </div>
    <VForm class="md:order-1" @submit="handleSubmit">
      <component
        :is="listComponent"
        :value="descriptor"
        :animation="150"
        handle="[data-handle]"
        class="flex flex-col gap-2"
        @input="handleSort"
      >
        <template v-for="field in descriptor">
          <div
            v-if="canView(field.key) === true && field.hidden !== true"
            :key="field.key"
            class="flex gap-2"
          >
            <DragVertical
              v-if="sortable"
              :size="20"
              class="mt-6 cursor-grab"
              data-handle
            />
            <div v-if="field.type === 'select'" class="w-full">
              <VDropdown
                :value="value[field.key]"
                :label="field.label"
                :read-disabled="canRead(field.key) === false"
                :disabled="
                  field.disabled === true || canWrite(field.key) === false
                "
                :options="field.options"
                :value-label="field.valueLabel"
                :user-select="field.userSelect"
                :required="field.required && canWrite(field.key) === true"
                :multiple="field.multiple"
                :caption="field.caption"
                :error="errors[field.key] || checkFieldInvalid(field.key)"
                @input="(value) => handleInputField({ key: field.key, value })"
              />
            </div>
            <div v-else-if="field.type === 'date'" class="w-full">
              <VDatePicker
                :value="value[field.key]"
                :label="field.label"
                :read-disabled="canRead(field.key) === false"
                :disabled="
                  field.disabled === true || canWrite(field.key) === false
                "
                :required="field.required && canWrite(field.key) === true"
                :caption="field.caption"
                :error="errors[field.key] || checkFieldInvalid(field.key)"
                @input="(value) => handleInputField({ key: field.key, value })"
              />
            </div>
            <div v-else-if="field.type === 'datetime'" class="w-full">
              <VDatePicker
                :value="value[field.key]"
                :label="field.label"
                :read-disabled="canRead(field.key) === false"
                :disabled="
                  field.disabled === true || canWrite(field.key) === false
                "
                :required="field.required && canWrite(field.key) === true"
                :error="errors[field.key] || checkFieldInvalid(field.key)"
                :caption="field.caption"
                type="datetime"
                format="DD.MM.YYYY hh:mm:ss"
                @input="(value) => handleInputField({ key: field.key, value })"
              />
            </div>
            <div v-else-if="field.type === 'radio'" class="w-full">
              <VRadioSet
                :value="value[field.key]"
                :label="field.label"
                :read-disabled="canRead(field.key) === false"
                :disabled="
                  field.disabled === true || canWrite(field.key) === false
                "
                :options="field.options"
                :required="field.required && canWrite(field.key) === true"
                :error="errors[field.key] || checkFieldInvalid(field.key)"
                :caption="field.caption"
                @input="(value) => handleInputField({ key: field.key, value })"
              />
            </div>
            <div v-else-if="field.type === 'textarea'" class="w-full">
              <VTextArea
                :value="value[field.key]"
                :label="field.label"
                :read-disabled="canRead(field.key) === false"
                :disabled="
                  field.disabled === true || canWrite(field.key) === false
                "
                :required="field.required && canWrite(field.key) === true"
                :error="errors[field.key] || checkFieldInvalid(field.key)"
                :caption="field.caption"
                @input="(value) => handleInputField({ key: field.key, value })"
              />
            </div>
            <div v-else class="w-full">
              <VTextField
                :value="value[field.key]"
                :label="field.label"
                :read-disabled="canRead(field.key) === false"
                :disabled="
                  field.disabled === true || canWrite(field.key) === false
                "
                :required="field.required && canWrite(field.key) === true"
                :error="errors[field.key] || checkFieldInvalid(field.key)"
                :caption="field.caption"
                @input="(value) => handleInputField({ key: field.key, value })"
              />
            </div>
          </div>
        </template>
      </component>
      <slot name="footer" />
    </VForm>
  </div>
</template>

<script>
import { mapState } from "pinia";
import Draggable from "vuedraggable";
import { NcActions, NcActionButton } from "@nextcloud/vue";

import DragVertical from "vue-material-design-icons/DragVertical.vue";
import Cog from "vue-material-design-icons/Cog.vue";
import SortVariantRemove from "vue-material-design-icons/SortVariantRemove.vue";

import VForm from "@/common/shared/components/VForm/VForm.vue";
import VDatePicker from "@/common/shared/components/VDatePicker/VDatePicker.vue";
import VRadioSet from "@/common/shared/components/VRadioSet/VRadioSet.vue";
import VTextField from "@/common/shared/components/VTextField/VTextField.vue";
import VTextArea from "@/common/shared/components/VTextArea/VTextArea.vue";
import VDropdown from "@/common/shared/components/VDropdown/VDropdown.vue";

import { SimpleDiv } from "./components/SimpleDiv";

import { usePermissionStore } from "@/admin/app/store/permission";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "FormCreator",
  mixins: [contextualTranslationsMixin],
  components: {
    Draggable,
    NcActions,
    NcActionButton,
    DragVertical,
    Cog,
    SortVariantRemove,
    VForm,
    VDatePicker,
    VRadioSet,
    VTextField,
    VTextArea,
    VDropdown,
    SimpleDiv,
  },
  emits: ["input", "on-submit", "on-sort", "on-delete-fields-ordering"],
  props: {
    value: {
      type: Object,
      default: () => ({}),
    },
    descriptor: {
      type: Array,
      default: () => [],
    },
    permissionsFormName: {
      type: String,
      default: "",
    },
    errors: {
      type: Object,
      default: () => ({}),
    },
    validation: {
      type: Object,
      default: () => ({}),
    },
    validationFormKey: {
      type: String,
      default: "formValues",
    },
    sortable: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    ...mapState(usePermissionStore, [
      "canViewField",
      "canReadField",
      "canWriteField",
    ]),
    requiredFields() {
      return this.descriptor.filter((item) => item.required === true);
    },
    listComponent() {
      return this.sortable ? Draggable : SimpleDiv;
    },
    isAsideExist() {
      return this.sortable === true;
    },
  },
  methods: {
    checkFieldInvalid(key) {
      if (
        !this.validation ||
        !this.validation[this.validationFormKey] ||
        !this.validation[this.validationFormKey][key]
      ) {
        return false;
      }

      const value = this.validation[this.validationFormKey][key];

      return value.$invalid === true && value.$dirty === true;
    },
    canView(field) {
      if (!this.permissionsFormName) {
        return true;
      }

      return this.canViewField({ form: this.permissionsFormName, field });
    },
    canRead(field) {
      if (!this.permissionsFormName) {
        return true;
      }

      return this.canReadField({ form: this.permissionsFormName, field });
    },
    canWrite(field) {
      if (!this.permissionsFormName) {
        return true;
      }

      return this.canWriteField({ form: this.permissionsFormName, field });
    },
    handleInputField({ key, value }) {
      this.$emit("input", {
        ...this.value,
        [key]: value,
      });

      this.$emit("on-field-input", { key, value });

      if (
        !this.validation ||
        !this.validation[this.validationFormKey] ||
        !this.validation[this.validationFormKey][key]
      ) {
        return false;
      }

      this.validation[this.validationFormKey][key].$touch();
    },
    handleSubmit() {
      this.$emit("on-submit");
    },
    handleSort(payload) {
      this.$emit("on-sort", payload);
    },
    handleDeleteFieldsOrdering() {
      this.$emit("on-delete-fields-ordering");
    },
  },
};
</script>
