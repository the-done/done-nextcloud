<template>
  <form
    v-if="isActive === true"
    class="v-simple-inline-form"
    @submit.prevent="handleSubmit"
  >
    <VDropdown
      v-model="$v.formValues.user.$model"
      :options="userOptions"
      :user-select="true"
      :required="true"
      :error="errors.user"
      :ariaLabel="contextTranslate('Employee', context)"
      :placeholder="contextTranslate('Select employee', context)"
      class="v-simple-inline-form__input"
    />
    <NcButton class="v-simple-inline-form__button" @click="handleCancel">
      {{ contextTranslate('Cancel', context) }}
    </NcButton>
    <NcButton
      native-type="submit"
      type="primary"
      class="v-simple-inline-form__button"
    >
      {{ contextTranslate('Add', context) }}
    </NcButton>
  </form>
  <NcButton v-else @click="toggleActive"> {{ contextTranslate('Add employee') }} </NcButton>
</template>

<script>
import { NcButton } from "@nextcloud/vue";
import { required } from "vuelidate/lib/validators";
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

import VDropdown from "@/common/shared/components/VDropdown/VDropdown.vue";

import { FIELD_IS_REQUIRED_ERROR } from "@/common/shared/lib/constants";

const REQUIRED_FIELDS = ["user"];

export default {
  name: "AssignUserToRoleForm",
  mixins: [contextualTranslationsMixin],
  props: {
    userOptions: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["onSubmit"],
  components: {
    NcButton,
    VDropdown,
  },
  data() {
    return {
      context: 'admin/users',
      isActive: false,
      formValues: {
        user: null,
      },
    };
  },
  validations: {
    formValues: {
      user: {
        required,
      },
    },
  },
  computed: {
    errors() {
      const requiredFields = REQUIRED_FIELDS.reduce((accum, key) => {
        const field = this.$v.formValues[key];

        if (field.$invalid && field.$dirty && field.required === false) {
          return {
            ...accum,
            [key]: (this.contextTranslate(FIELD_IS_REQUIRED_ERROR, this.context)),
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
    handleDropFrom() {
      this.formValues.user = null;

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
