<template>
  <section v-if="loading === false">
    <form
      v-if="isActive === true"
      class="v-simple-inline-form"
      @submit.prevent="handleSubmit"
    >
      <VDropdown
        v-model="$v.formValues.direction.$model"
        :options="directionOptions"
        :required="true"
        :error="errors.direction"
        :ariaLabel="contextTranslate('Direction', context)"
        :placeholder="contextTranslate('Direction', context)"
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
      v-else-if="directionOptions && directionOptions.length > 0"
      @click="toggleActive"
    >
      {{ contextTranslate('Add team to direction', context) }}
    </NcButton>
    <NcNoteCard v-else type="warning" :style="{ margin: '0' }">
      <template #icon>
        <BookOpenVariant :size="20" />
      </template>
      {{ contextTranslate('Directions dictionary is empty.', context) }}
      <RouterLink
        :to="{ name: 'dictionary-directions-new' }"
        class="v-link v-link--underline"
      >
        {{ contextTranslate('Add direction', context) }}
      </RouterLink>
    </NcNoteCard>
  </section>
</template>

<script>
import { NcButton, NcNoteCard } from "@nextcloud/vue";
import { required } from "vuelidate/lib/validators";

import BookOpenVariant from "vue-material-design-icons/BookOpenVariant.vue";

import VDropdown from "@/common/shared/components/VDropdown/VDropdown.vue";

import { FIELD_IS_REQUIRED_ERROR } from "@/common/shared/lib/constants";
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

const REQUIRED_FIELDS = ["direction"];

export default {
  name: "AssignDirectionToTeamForm",
  mixins: [contextualTranslationsMixin],
  props: {
    directionOptions: {
      type: Array,
      default: () => [],
    },
    loading: {
      type: Boolean,
      default: true,
    },
  },
  emits: ["onSubmit"],
  components: {
    NcButton,
    NcNoteCard,
    BookOpenVariant,
    VDropdown,
  },
  data() {
    return {
      context: 'admin/users',
      isActive: false,
      formValues: {
        direction: null,
      },
    };
  },
  validations: {
    formValues: {
      direction: {
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
            [key]: this.contextTranslate(FIELD_IS_REQUIRED_ERROR, this.context),
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
      this.formValues.direction = null;

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
