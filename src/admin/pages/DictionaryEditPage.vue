<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          v-for="(item, index) in breadcrumbs"
          :key="index"
          :name="contextTranslate(item.title, context)"
          :to="item.path"
          :forceIconText="index === 0"
        >
          <template v-if="item.icon" #icon>
            <component v-if="item.icon" :is="item.icon" />
            <BookOpenVariant v-else />
          </template>
        </NcBreadcrumb>
        <NcBreadcrumb :name="pageTitle" />
      </NcBreadcrumbs>
    </VToolbar>
    <VPageLayout>
      <VPageContent>
        <VPagePadding>
          <VForm class="md:max-w-[425px]" @submit="handleSubmit">
            <VTextField
              v-model="$v.formValues.name.$model"
              :error="errors.name"
              :required="true"
              :label="contextTranslate('Title', context)"
            />
            <VTextField
              v-model="$v.formValues.sort.$model"
              :error="errors.sort"
              :required="true"
              :label="contextTranslate('Sorting', context)"
            />
            <div class="v-flex v-flex--justify-end">
              <NcButton native-type="submit">
                {{ contextTranslate("Save", context) }}
              </NcButton>
            </div>
          </VForm>
        </VPagePadding>
      </VPageContent>
    </VPageLayout>
  </VPage>
</template>

<script>
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcActions,
  NcActionButton,
  NcButton,
} from "@nextcloud/vue";
import { required, minValue } from "vuelidate/lib/validators";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import BookOpenVariant from "vue-material-design-icons/BookOpenVariant.vue";

import {
  fetchDictionaryItemBySlug,
  createDictionaryItem,
  updateDictionaryItem,
  getNextSortInDict,
} from "../entities/dictionaries/api";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
} from "@/common/widgets/VPage";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import VForm from "@/common/shared/components/VForm/VForm.vue";
import VTextField from "@/common/shared/components/VTextField/VTextField.vue";

import {
  FIELD_IS_REQUIRED_ERROR,
  FIELD_WRONG_VALUE_ERROR,
} from "@/common/shared/lib/constants";

const REQUIRED_FIELDS = ["name", "sort"];
const MIN_NUMBER_FIELDS = ["sort"];

export default {
  name: "DictionaryPositionsPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    NcButton,
    BookOpenVariant,
    VPage,
    VPageLayout,
    VPageContent,
    VPagePadding,
    VToolbar,
    VForm,
    VTextField,
  },
  props: {
    additionalProps: {
      type: Object,
      default: () => ({}),
    },
  },
  data: () => ({
    isLoading: false,
    rawData: null,
    formValues: {
      name: "",
      sort: "",
    },
    context: "admin/users",
  }),
  validations: {
    formValues: {
      name: {
        required,
      },
      sort: {
        required,
        minValue: minValue(0),
      },
    },
  },
  computed: {
    slug() {
      return this.$route.params.slug;
    },
    isEdit() {
      return Boolean(this.slug);
    },
    dictionaryTitle() {
      return this.additionalProps.dictionaryTitle;
    },
    breadcrumbs() {
      return this.additionalProps.breadcrumbs;
    },
    pageTitle() {
      if (this.isEdit === false) {
        return this.contextTranslate("Create", this.context);
      }

      if (this.isLoading === true) {
        return "";
      }

      return this.rawData?.name || this.slug;
    },
    parentPath() {
      return this.breadcrumbs[this.breadcrumbs.length - 1]?.path;
    },
    errors() {
      const minNumberFields = MIN_NUMBER_FIELDS.reduce((accum, key) => {
        const field = this.$v.formValues[key];

        if (field.$invalid && field.$dirty && field.minValue === false) {
          return {
            ...accum,
            [key]: this.contextTranslate(FIELD_WRONG_VALUE_ERROR, this.context),
          };
        }

        return accum;
      }, {});

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
        ...minNumberFields,
        ...requiredFields,
      };
    },
  },
  methods: {
    t,
    navigateParent() {
      if (!this.parentPath) {
        return;
      }

      this.$router.push(this.parentPath);
    },
    async handleCreate() {
      try {
        await createDictionaryItem(this.dictionaryTitle, {
          ...this.formValues,
        });

        this.navigateParent();
      } catch (e) {
        console.log(e);
      }
    },
    async handleUpdate() {
      try {
        await updateDictionaryItem(
          this.dictionaryTitle,
          this.slug,
          this.rawData.slug_type,
          this.formValues
        );

        this.navigateParent();
      } catch (e) {
        console.log(e);
      }
    },
    handleSubmit() {
      this.$v.$touch();

      if (this.$v.$invalid === true) {
        return;
      }

      if (this.isEdit === true) {
        this.handleUpdate();

        return;
      }

      this.handleCreate();
    },
    setFormValues({ name, sort }) {
      this.formValues = {
        name,
        sort,
      };
    },
    async handleFetchData() {
      this.isLoading = true;

      try {
        const data = await fetchDictionaryItemBySlug(this.dictionaryTitle, {
          slug: this.slug,
        });

        this.rawData = { ...data };

        this.setFormValues(data);
      } catch (e) {
        console.log(e);
      } finally {
        this.isLoading = false;
      }
    },
    async fetchNextSortInDict() {
      try {
        const nextNumber = await getNextSortInDict(this.dictionaryTitle);

        this.formValues.sort = nextNumber ? String(nextNumber) : "0";
      } catch (e) {
        console.log(e);
      }
    },
    init() {
      if (this.isEdit === false) {
        this.fetchNextSortInDict();

        return;
      }

      this.handleFetchData();
    },
  },
  mounted() {
    this.init();
  },
};
</script>
