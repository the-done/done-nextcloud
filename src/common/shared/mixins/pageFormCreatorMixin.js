/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { mapState } from "pinia";
import { required, integer, decimal } from "vuelidate/lib/validators";
import { format } from "date-fns";
import { t } from "@nextcloud/l10n";

import {
  fetchDynamicFieldsForSource,
  fetchDynamicFieldValuesByRecordId,
  fetchDropdownOptions,
} from "@/admin/entities/dynamicFields/api";
import { getFieldCommentsBySource } from "@/admin/entities/fieldComments/api";
import {
  saveFieldsOrdering,
  getFieldsOrdering,
  deleteFieldsOrdering,
} from "@/admin/entities/common/api";

import { usePermissionStore } from "@/admin/app/store/permission";

import { transformSortFieldsForRest } from "@/admin/shared/lib/helpers";
import { handleRestErrors } from "@/common/shared/lib/helpers";

import {
  DYNAMIC_FIELD_TYPES,
  DYNAMIC_FIELDS_BY_ID,
} from "@/admin/entities/dynamicFields/constants";
import { FIELD_IS_REQUIRED_ERROR } from "@/common/shared/lib/constants";

export const pageFormCreatorMixin = {
  validations() {
    return {
      formValues: {
        ...this.requiredFieldsValidation,
        ...this.fieldsValidation,
      },
    };
  },
  data() {
    return {
      formValues: {},
      dynamicFieldsRawData: [],
      dynamicFieldRawValues: [],
      tempSortDescriptor: [],
      fieldsSortOrdering: [],
      dropdownOptionsCache: {},
    };
  },
  computed: {
    ...mapState(usePermissionStore, [
      "canViewField",
      "canReadField",
      "canWriteField",
    ]),
    dynamicFieldsDescriptor() {
      return this.dynamicFieldsRawData.map(
        ({
          id,
          field_type,
          title,
          slug,
          required: fieldIsRequired,
          multiple,
          caption,
        }) => {
          const type = DYNAMIC_FIELD_TYPES[field_type];
          const data = {
            key: id,
            type,
            label: title,
            isDynamic: true,
            slug,
            required: Boolean(fieldIsRequired),
            multiple,
            caption,
          };

          const fieldRequiredValidation = {
            required: fieldIsRequired
              ? {
                  fn: required,
                  message: t("done", FIELD_IS_REQUIRED_ERROR),
                }
              : null,
          };

          if (type === "integer") {
            return {
              ...data,
              validation: {
                ...fieldRequiredValidation,
                integer: {
                  fn: integer,
                  message: t("done", "Must be an integer"),
                },
              },
            };
          }

          if (type === "decimal") {
            return {
              ...data,
              validation: {
                ...fieldRequiredValidation,
                decimal: {
                  fn: decimal,
                  message: t("done", "Must be a number"),
                },
              },
            };
          }

          if (type === "select") {
            const options = this.dropdownOptionsCache[slug] || [];

            return {
              ...data,
              options: options,
              valueLabel: "option_label",
              validation: fieldRequiredValidation,
            };
          }

          return data;
        }
      );
    },
    descriptor() {
      if (this.tempSortDescriptor && this.tempSortDescriptor.length > 0) {
        return this.tempSortDescriptor;
      }

      const descriptor = [
        ...this.staticFieldsDescriptor,
        ...this.dynamicFieldsDescriptor,
      ];

      if (!this.isSortable || !this.fieldsSortOrdering?.length) {
        return descriptor;
      }

      const sortKeys = this.fieldsSortOrdering.map((item) => item.field);

      const sortedFields = sortKeys.reduce((accum, key) => {
        const field = descriptor.find((item) => item.key === key);

        if (field) {
          return [...accum, field];
        }

        return accum;
      }, []);

      const unsortedFields = descriptor.reduce((accum, item) => {
        const isSorted = sortKeys.includes(item.key);

        if (isSorted === true) {
          return accum;
        }

        return [...accum, item];
      }, []);

      return [...sortedFields, ...unsortedFields];
    },
    requiredFields() {
      if (this.descriptor) {
        return this.descriptor.filter(
          (item) => item.required === true && item.validation === undefined
        );
      }

      return this.descriptor.filter(
        (item) => item.required === true && item.validation === undefined
      );
    },
    fieldsNeedValidation() {
      if (this.descriptor) {
        return this.descriptor.filter((item) => item.validation !== undefined);
      }

      return this.descriptor.filter((item) => item.validation !== undefined);
    },
    requiredFieldsValidation() {
      return this.requiredFields.reduce((accum, field) => {
        const { key } = field;

        return {
          ...accum,
          [key]: {
            required(value) {
              return this.validateRequiredWithPermissions({
                field: key,
                value,
              });
            },
          },
        };
      }, {});
    },
    fieldsValidation() {
      return this.fieldsNeedValidation.reduce((accum, field) => {
        const { key, validation } = field;

        return {
          ...accum,
          [key]: Object.keys(validation).reduce((valAccum, valKey) => {
            const value = validation[valKey];

            if (!value) {
              return valAccum;
            }

            return {
              ...valAccum,
              [valKey]: validation[valKey].fn,
            };
          }, {}),
        };
      }, {});
    },
    requiredFieldsErrors() {
      return this.requiredFields.reduce((accum, field) => {
        const { key } = field;
        const $v = this.$v.formValues[key];

        if (
          $v.$invalid === true &&
          $v.$dirty === true &&
          $v.required === false
        ) {
          return {
            ...accum,
            [key]: t("done", FIELD_IS_REQUIRED_ERROR),
          };
        }

        return accum;
      }, {});
    },
    fieldsErrors() {
      return this.fieldsNeedValidation.reduce((accum, field) => {
        const { key, validation } = field;
        const validationKeys = Object.keys(validation);
        const $v = this.$v.formValues[key];

        const result = validationKeys.reduce((accum, valKey) => {
          if (
            $v.$invalid === true &&
            $v.$dirty === true &&
            $v[valKey] === false
          ) {
            return {
              ...accum,
              [key]: validation[valKey].message,
            };
          }

          return accum;
        }, {});

        return {
          ...accum,
          ...result,
        };
      }, {});
    },
    errors() {
      return {
        ...this.requiredFieldsErrors,
        ...this.fieldsErrors,
      };
    },
  },
  methods: {
    t,
    getFieldSlugValue({ value }) {
      if (typeof value === "string") {
        return value;
      }

      return value?.slug || value?.uuid || null;
    },
    getFieldIdValue({ value }) {
      return value?.id || null;
    },
    getFormValueForRest({ item }) {
      const { key } = item;
      const value = this.formValues[key];

      if (item.transformDataForRest && value) {
        return item.transformDataForRest({
          value,
          formValues: this.formValues,
        });
      }

      if (item.type === "date" && value) {
        return format(value, "dd.MM.yyyy");
      }

      if (item.type === "datetime" && value) {
        return format(value, "dd.MM.yyyy hh:mm:ss");
      }

      if (item.type === "select") {
        if (!value || value === null) {
          return null;
        }

        return {
          multiple: Array.isArray(value),
          data: value,
        };
      }

      return value;
    },
    transformStaticDescriptorForRest() {
      return this.staticFieldsDescriptor.reduce((accum, item) => {
        const { key } = item;

        if (this.canWrite(key) === false) {
          return accum;
        }

        const value = this.getFormValueForRest({ item });

        return {
          ...accum,
          [key]: value,
        };
      }, {});
    },
    transformDynamicDescriptorForRest() {
      return this.dynamicFieldsDescriptor.reduce((accum, item) => {
        const { key } = item;

        const dynamicFieldRawValue = this.dynamicFieldRawValues.find(
          (item) => item.dyn_field_id === key
        );

        if (this.canWrite(key) === false) {
          return accum;
        }

        const value = this.getFormValueForRest({
          item,
        });

        return [
          ...accum,
          {
            dyn_field_id: key,
            slug: dynamicFieldRawValue?.slug || null,
            value,
          },
        ];
      }, []);
    },
    transformDataForRest() {
      const staticFields = this.transformStaticDescriptorForRest();
      const dynamicFields = this.transformDynamicDescriptorForRest();

      return {
        staticFields,
        dynamicFields,
      };
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
    validateRequiredWithPermissions({ field, value }) {
      if (this.canWrite(field) === false) {
        return true;
      }

      return required(value);
    },
    async handleSortFields(payload) {
      if (!this.isSortable) {
        return;
      }

      try {
        this.tempSortDescriptor = payload;

        const fields = transformSortFieldsForRest(payload);

        await saveFieldsOrdering({ source: this.source, fields });
      } catch (e) {
        console.log(e);
      }
    },
    async handleDeleteFieldsOrdering() {
      if (!this.isSortable) {
        return;
      }

      try {
        await deleteFieldsOrdering({ source: this.source });

        this.tempSortDescriptor = [];
        this.fieldsSortOrdering = [];
      } catch (e) {
        console.log(e);
      }
    },
    async fetchFieldsOrdering() {
      try {
        const { data } = await getFieldsOrdering({
          source: this.source,
        });

        this.fieldsSortOrdering = data;
      } catch (e) {
        handleRestErrors(e);
      }
    },
    async loadDropdownOptions(dynamicFieldsData) {
      const dropdownFieldTypeId = Number(DYNAMIC_FIELDS_BY_ID["select"]);

      const dropdownFields = dynamicFieldsData.filter(
        (field) => field.field_type === dropdownFieldTypeId
      );

      for (const field of dropdownFields) {
        try {
          const { data: options } = await fetchDropdownOptions(field.slug);

          this.dropdownOptionsCache = {
            ...this.dropdownOptionsCache,
            [field.slug]: options,
          };
        } catch (e) {
          console.warn(
            `Failed to load options for dropdown field ${field.slug}:`,
            e
          );

          this.dropdownOptionsCache = {
            ...this.dropdownOptionsCache,
            [field.slug]: [],
          };
        }
      }
    },
    setDynamicFieldFormValues(payload) {
      const cachedData = payload.reduce((accum, item) => {
        return {
          ...accum,
          [item.dyn_field_id]: item.value,
        };
      }, {});

      const result = this.dynamicFieldsDescriptor.reduce((accum, item) => {
        const { key, type, options } = item;
        const value = cachedData[key];

        if (type === "date" || type === "datetime") {
          return {
            ...accum,
            [key]: value ? new Date(value) : null,
          };
        }

        if (type === "select") {
          if (!value) {
            return {
              ...accum,
              [key]: null,
            };
          }

          if (Array.isArray(value)) {
            return {
              ...accum,
              [key]: value.reduce((accum, val) => {
                const option = options.find(
                  (option) => option.slug === val.option_slug
                );

                if (option) {
                  return [...accum, option];
                }

                return accum;
              }, []),
            };
          }

          const option = options.find(
            (option) => option.slug === value.option_slug
          );

          if (option) {
            return {
              ...accum,
              [key]: option,
            };
          }

          return {
            ...accum,
            [key]: null,
          };
        }

        return {
          ...accum,
          [key]: value,
        };
      }, {});

      this.formValues = {
        ...this.formValues,
        ...result,
      };
    },
    async initFormWithDynamicFields({ itemId, data: staticFieldValues }) {
      try {
        if (!this.source) {
          throw new Error(t("done", "Source not found"));
        }

        this.setStaticFormValues({
          ...staticFieldValues,
        });

        if (!itemId) {
          return;
        }

        const { data: dynamicFieldsData } = await fetchDynamicFieldsForSource(
          this.source
        );

        const { data: fieldComments } = await getFieldCommentsBySource(
          this.source
        );

        this.dynamicFieldsRawData = dynamicFieldsData.map((item) => ({
          ...item,
          caption: fieldComments?.[item.slug]?.comment || "",
        }));

        await this.loadDropdownOptions(dynamicFieldsData);

        const { data: dynamicFieldValues } =
          await fetchDynamicFieldValuesByRecordId({
            record_id: itemId,
          });

        this.dynamicFieldRawValues = dynamicFieldValues;

        this.setDynamicFieldFormValues(dynamicFieldValues);

        if (this.isSortable === true) {
          this.fetchFieldsOrdering();
        }
      } catch (e) {
        handleRestErrors(e);
      }
    },
  },
};
