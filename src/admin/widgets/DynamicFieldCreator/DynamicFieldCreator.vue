<template>
  <form
    @submit.prevent="handleSubmit"
    class="flex flex-wrap items-start gap-2 p-4"
  >
    <div class="flex flex-col gap-2">
      <div class="flex flex-col">
        <div class="flex flex-wrap gap-2">
          <div class="w-[320px]">
            <VTextField
              v-model="$v.formValues.title.$model"
              :error="errors.title"
              :required="true"
              :placeholder="contextTranslate('Title', context)"
              :caption="modelData?.comment?.comment"
            />
          </div>
          <div class="w-[320px]">
            <VDropdown
              v-model="$v.formValues.fieldType.$model"
              :error="errors.fieldType"
              :options="fieldTypeOptions"
              :aria-label="contextTranslate('Field type', context)"
              :placeholder="contextTranslate('Field type', context)"
              @input="handleChangeType"
            />
          </div>
        </div>
      </div>
      <div v-if="isEdit === true" class="flex flex-wrap gap-2">
        <VButton
          :disabled="loading"
          variant="tertiary"
          @click="handleOpenCommentForm"
        >
          <template #icon>
            <CommentOutline />
          </template>
        </VButton>
        <VButton
          v-if="isDropdownField === true"
          :disabled="loading"
          variant="tertiary"
          @click="handleOpenSelectOptions"
        >
          <template #icon>
            <ListBoxOutline />
          </template>
        </VButton>
        <NcCheckboxRadioSwitch
          v-if="isDropdownField === true"
          v-model="formValues.multiple"
          :disabled="loading"
        >
          {{ contextTranslate("Multiselect", context) }}
        </NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch
          v-model="formValues.required"
          :disabled="loading"
        >
          {{ contextTranslate("Required", context) }}
        </NcCheckboxRadioSwitch>
      </div>
    </div>
    <div class="v-flex-0-0-auto v-flex v-flex--gap-1 v-flex--justify-end">
      <VButton
        :style="{ width: '120px' }"
        :loading="loading"
        :disabled="$v.$invalid"
        native-type="submit"
      >
        {{
          isEdit === true
            ? contextTranslate("Save", context)
            : contextTranslate("Add", context)
        }}
      </VButton>
      <VButton
        v-if="isEdit === true"
        :loading="loading"
        variant="tertiary"
        @click="handleDelete"
      >
        <template #icon>
          <TrashCanOutline />
        </template>
      </VButton>
    </div>
  </form>
</template>

<script>
import { NcCheckboxRadioSwitch } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";
import { required } from "vuelidate/lib/validators";

import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";
import ListBoxOutline from "vue-material-design-icons/ListBoxOutline.vue";
import CommentOutline from "vue-material-design-icons/CommentOutline.vue";

import VTextField from "@/common/shared/components/VTextField/VTextField.vue";
import VDropdown from "@/common/shared/components/VDropdown/VDropdown.vue";
import VButton from "@/common/shared/components/VButton/VButton.vue";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import {
  DYNAMIC_FIELD_TYPE_LABELS,
  DYNAMIC_FIELDS_BY_ID,
} from "@/admin/entities/dynamicFields/constants";
import { FIELD_IS_REQUIRED_ERROR } from "@/common/shared/lib/constants";

const REQUIRED_FIELDS = ["title", "fieldType"];
const INIT_FORM_VALUES = {
  title: "",
  fieldType: null,
  required: false,
  multiple: false,
};

export default {
  name: "DynamicFieldCreator",
  components: {
    NcCheckboxRadioSwitch,
    TrashCanOutline,
    ListBoxOutline,
    CommentOutline,
    VTextField,
    VDropdown,
    VButton,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    modelData: {
      type: Object,
      default: null,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    draggable: {
      type: Boolean,
      default: false,
    },
  },
  emits: [
    "on-submit",
    "on-delete",
    "on-open-dropdown-options-form",
    "on-open-comment-form",
  ],
  data() {
    return {
      formValues: { ...INIT_FORM_VALUES },
    };
  },
  validations: {
    formValues: {
      title: {
        required,
      },
      fieldType: {
        required,
      },
    },
  },
  computed: {
    isEdit() {
      if (this.modelData?.id) {
        return true;
      }

      return false;
    },
    isDropdownField() {
      return this.formValues.fieldType?.id === DYNAMIC_FIELDS_BY_ID["select"];
    },
    fieldTypeOptions() {
      return Object.keys(DYNAMIC_FIELD_TYPE_LABELS).reduce((accum, key) => {
        return [
          ...accum,
          {
            id: key,
            name: t("done", DYNAMIC_FIELD_TYPE_LABELS[key]),
          },
        ];
      }, []);
    },
    errors() {
      const requiredFields = REQUIRED_FIELDS.reduce((accum, key) => {
        const field = this.$v.formValues[key];

        if (field.$invalid && field.$dirty && field.required === false) {
          return {
            ...accum,
            [key]: this.contextTranslate(FIELD_IS_REQUIRED_ERROR, this.context),
          };
        }
        return accum;
      }, {});

      return {
        ...requiredFields,
      };
    },
  },
  methods: {
    t,
    handleOpenSelectOptions() {
      this.$emit("on-open-dropdown-options-form", {
        title: this.modelData.title,
        slug: this.modelData.slug,
        ...this.formValues,
      });
    },
    handleOpenCommentForm() {
      this.$emit("on-open-comment-form", this.modelData);
    },
    handleChangeType({ id }) {
      const numericId = Number(id);

      if (numericId !== DYNAMIC_FIELDS_BY_ID["select"]) {
        this.formValues.multiple = false;
      }
    },
    setFormValues({ title, field_type, required, multiple }) {
      this.formValues.title = title;
      this.formValues.required = required;
      this.formValues.multiple = multiple;

      const fieldType = this.fieldTypeOptions.find(
        (item) => String(item.id) === String(field_type)
      );

      if (fieldType !== undefined) {
        this.formValues.fieldType = fieldType;
      }
    },
    handleDropForm() {
      this.formValues = { ...INIT_FORM_VALUES };

      this.$v.$reset();
    },
    handleDelete() {
      this.$emit("on-delete", { id: this.modelData?.id });
    },
    handleSubmit() {
      this.$v.$touch();

      if (this.$v.$invalid === true) {
        return;
      }

      this.$emit("on-submit", {
        formValues: this.formValues,
        id: this.modelData?.id,
      });

      if (this.isEdit === false) {
        this.handleDropForm();
      }
    },
  },
  watch: {
    modelData: {
      handler: function (nextValue) {
        if (nextValue === null) {
          return;
        }

        this.setFormValues(nextValue);
      },
      immediate: true,
    },
  },
};
</script>
