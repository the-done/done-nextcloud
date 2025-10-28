<template>
  <section v-if="loading === false">
    <form
      v-if="isActive === true"
      class="v-simple-inline-form"
      @submit.prevent="handleSubmit"
    >
      <VDropdown
        v-model="$v.formValues.role.$model"
        :options="computedRoleOptions"
        :required="true"
        :error="errors.role"
        :ariaLabel="contextTranslate('Employee role', context)"
        :placeholder="contextTranslate('Select role', context)"
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
    <NcButton
      v-else-if="roleOptions && roleOptions.length > 0"
      @click="toggleActive"
    >
      {{ contextTranslate('Add role', context) }}
    </NcButton>
    <NcNoteCard v-else type="warning" :style="{ margin: '0' }">
      <template #icon>
        <BookOpenVariant :size="20" />
      </template>
      {{ contextTranslate('Roles dictionary is empty. Please contact the administrator.', context) }}
    </NcNoteCard>
  </section>
</template>

<script>
import { NcButton, NcNoteCard } from "@nextcloud/vue";
import { required } from "vuelidate/lib/validators";
import { mapState } from "pinia";

import BookOpenVariant from "vue-material-design-icons/BookOpenVariant.vue";

import VDropdown from "@/common/shared/components/VDropdown/VDropdown.vue";

import { usePermissionStore } from "@/admin/app/store/permission";

import { MAP_GLOBAL_ROLES_TO_PERMISSION } from "@/admin/entities/globalRoles/constants";
import { FIELD_IS_REQUIRED_ERROR } from "@/common/shared/lib/constants";
import { t } from '@nextcloud/l10n'
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

const REQUIRED_FIELDS = ["role"];

export default {
  name: "AssignRoleToUserForm",
  emits: ["onSubmit"],
  components: {
    NcButton,
    NcNoteCard,
    BookOpenVariant,
    VDropdown,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    roleOptions: {
      type: Array,
      default: () => [],
    },
    loading: {
      type: Boolean,
      default: true,
    },
  },
  data() {
    return {
      context: 'admin/users',
      isActive: false,
      formValues: {
        role: null,
      },
    };
  },
  validations: {
    formValues: {
      role: {
        required,
      },
    },
  },
  computed: {
    ...mapState(usePermissionStore, ["getCommonPermission"]),
    computedRoleOptions() {
      return this.roleOptions.reduce((accum, item) => {
        const permission = MAP_GLOBAL_ROLES_TO_PERMISSION[item.id];

        if (!permission) {
          return [...accum, { ...item, name: t('done', item.name) }];
        }

        const isAllowed = this.getCommonPermission(permission);

        if (isAllowed === true) {
          return [...accum, { ...item, name: t('done', item.name) }];
        }

        return accum;
      }, []);
    },
    errors() {
      const requiredFields = REQUIRED_FIELDS.reduce((accum, key) => {
        const field = this.$v.formValues[key];

        if (field.$invalid && field.$dirty && field.required === false) {
          return {
            ...accum,
            [key]: t('done', FIELD_IS_REQUIRED_ERROR),
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
      this.formValues.role = null;

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
