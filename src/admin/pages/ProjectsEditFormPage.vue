<template>
  <VPageContent>
    <VPagePadding>
      <div
        v-if="hasWarnings === true"
        class="v-flex v-flex--column v-flex--gap-1 mb-4"
      >
        <NcNoteCard
          v-if="userOptions && userOptions.length === 0"
          type="warning"
          :style="{ margin: '0' }"
        >
          <template #icon>
            <Account :size="20" />
          </template>
          {{ contextTranslate("No available employees.", context) }}
          <RouterLink
            :to="{ name: 'staff-new' }"
            class="v-link v-link--underline"
          >
            {{ contextTranslate("Add employee", context) }}
          </RouterLink>
        </NcNoteCard>
        <NcNoteCard
          v-if="directionOptions && directionOptions.length === 0"
          type="warning"
          :style="{ margin: '0' }"
        >
          <template #icon>
            <BookOpenVariant :size="20" />
          </template>
          {{ contextTranslate("Directions dictionary is empty.", context) }}
          <RouterLink
            :to="{ name: 'dictionary-directions-new' }"
            class="v-link v-link--underline"
          >
            {{ contextTranslate("Add direction", context) }}
          </RouterLink>
        </NcNoteCard>
        <NcNoteCard
          v-if="projectStageOptions && projectStageOptions.length === 0"
          type="warning"
          :style="{ margin: '0' }"
        >
          <template #icon>
            <BookOpenVariant :size="20" />
          </template>
          {{ contextTranslate("Project stages dictionary is empty.", context) }}
          <RouterLink
            :to="{ name: 'dictionary-project-stages-new' }"
            class="v-link v-link--underline"
          >
            {{ contextTranslate("Add project stage", context) }}
          </RouterLink>
        </NcNoteCard>
        <NcNoteCard
          v-if="customerOptions && customerOptions.length === 0"
          type="warning"
          :style="{ margin: '0' }"
        >
          <template #icon>
            <BookOpenVariant :size="20" />
          </template>
          {{ contextTranslate("Customers dictionary is empty.", context) }}
          <RouterLink
            :to="{ name: 'dictionary-customers-new' }"
            class="v-link v-link--underline"
          >
            {{ contextTranslate("Add customer", context) }}
          </RouterLink>
        </NcNoteCard>
      </div>
      <FormCreator
        v-model="formValues"
        :descriptor="descriptor"
        :errors="errors"
        :validation="$v"
        :permissions-form-name="permissionsFormName"
        :sortable="isSortable"
        @on-submit="handleSubmit"
        @on-sort="handleSortFields"
        @on-delete-fields-ordering="handleDeleteFieldsOrdering"
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
</template>

<script>
import { NcButton, NcNoteCard } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";

import Account from "vue-material-design-icons/Account.vue";
import BookOpenVariant from "vue-material-design-icons/BookOpenVariant.vue";
/* import { format } from "date-fns"; */
import { minValue } from "vuelidate/lib/validators";

import { fetchUsers } from "@/common/entities/users/api";
import {
  createProject,
  updateProject,
  fetchProjectBySlug,
} from "@/common/entities/projects/api";
import { saveDynamicFieldsDataMultiple } from "@/admin/entities/dynamicFields/api";

import {
  fetchDirectionsDictionary,
  fetchProjectStagesDictionary,
  fetchCustomersDictionary,
} from "@/admin/entities/dictionaries/api";

import { pageFormCreatorMixin } from "@/common/shared/mixins/pageFormCreatorMixin";

import { VPageContent, VPagePadding } from "@/common/widgets/VPage";
import { FormCreator } from "@/common/widgets/FormCreator";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { getJoinString, handleRestErrors } from "@/common/shared/lib/helpers";

import { FIELD_WRONG_VALUE_ERROR } from "@/common/shared/lib/constants";
import { MAP_ENTITY_SOURCE } from "@/common/shared/lib/constants";

export default {
  name: "ProjectsEditFormPage",
  mixins: [pageFormCreatorMixin, contextualTranslationsMixin],
  components: {
    NcButton,
    NcNoteCard,
    VPageContent,
    VPagePadding,
    FormCreator,
    Account,
    BookOpenVariant,
  },
  data: () => ({
    context: "admin/projects",
    permissionsFormName: "project_card",
    directionOptions: [],
    projectStageOptions: [],
    customerOptions: [],
    userOptions: [],
    entityModel: {},
    isDictionaryLoading: true,
    isSortable: true,
    source: MAP_ENTITY_SOURCE["project"],
    formValues: {
      name: "", // Title *
      nickname: "", // Short name
      owner_id: null, // Project owner *
      project_manager_id: null, // Project manager *
      project_head_id: null, // Project head *
      direction_id: null, // Direction *
      stage_id: null, // Project stage *
      customer_id: null, // Customer *
    },
    /**
     *  dynamicFieldsDescriptor: [],
     *
     *  pageFormCreatorMixin
     */
  }),
  computed: {
    /**
     *  descriptor: [],
     *
     *  pageFormCreatorMixin
     */
    staticFieldsDescriptor() {
      return [
        {
          key: "name",
          type: "text",
          label: this.contextTranslate("Title", this.context),
          required: true,
        },
        {
          key: "nickname",
          type: "text",
          label: this.contextTranslate("Short project name", this.context),
          required: false,
        },
        {
          key: "customer_id",
          type: "select",
          label: this.contextTranslate("Customer", this.context),
          options: this.customerOptions,
          required: false,
          transformDataForRest: this.getFieldIdValue, // pageFormCreatorMixin
        },
        {
          key: "owner_id",
          type: "select",
          label: this.contextTranslate("Project owner", this.context),
          options: this.userOptions,
          valueLabel: "full_name",
          userSelect: true,
          required: true,
          transformDataForRest: this.getFieldIdValue, // pageFormCreatorMixin
        },
        {
          key: "project_head_id",
          type: "select",
          label: this.contextTranslate("Project head", this.context),
          options: this.userOptions,
          valueLabel: "full_name",
          userSelect: true,
          required: false,
          transformDataForRest: this.getFieldIdValue, // pageFormCreatorMixin
        },
        {
          key: "project_manager_id",
          type: "select",
          label: this.contextTranslate("Project manager", this.context),
          options: this.userOptions,
          valueLabel: "full_name",
          userSelect: true,
          required: false,
          transformDataForRest: this.getFieldIdValue, // pageFormCreatorMixin
        },
        {
          key: "stage_id",
          type: "select",
          label: this.contextTranslate("Project stage", this.context),
          options: this.projectStageOptions,
          required: false,
          transformDataForRest: this.getFieldIdValue, // pageFormCreatorMixin
        },
        {
          key: "direction_id",
          type: "select",
          label: this.contextTranslate("Direction", this.context),
          options: this.directionOptions,
          required: false,
          transformDataForRest: this.getFieldIdValue, // pageFormCreatorMixin
        },
      ];
    },
    slug() {
      return this.$route.params.slug;
    },
    isEdit() {
      return Boolean(this.slug);
    },
    hasWarnings() {
      if (this.isDictionaryLoading === true || this.isEdit === true) {
        return false;
      }

      return (
        (this.directionOptions && this.directionOptions.length === 0) ||
        (this.projectStageOptions && this.projectStageOptions.length === 0) ||
        (this.customerOptions && this.customerOptions.length === 0) ||
        (this.userOptions && this.userOptions.length === 0)
      );
    },
  },
  methods: {
    t,
    async handleCreate() {
      try {
        const { staticFields, dynamicFields } = this.transformDataForRest(); // pageFormCreatorMixin

        const { id } = await createProject(staticFields);

        if (dynamicFields?.length) {
          await saveDynamicFieldsDataMultiple({
            record_id: id,
            data: dynamicFields,
          });
        }

        this.$router.push({ name: "project-table" });
      } catch (e) {
        handleRestErrors(e);
      }
    },
    async handleUpdate() {
      try {
        const { staticFields, dynamicFields } = this.transformDataForRest(); // pageFormCreatorMixin

        await updateProject({
          slug: this.slug,
          slug_type: this.entityModel.slug_type,
          data: staticFields,
        });

        if (dynamicFields?.length) {
          await saveDynamicFieldsDataMultiple({
            record_id: this.entityModel.id,
            data: dynamicFields,
          });
        }

        this.$router.push({ name: "project-table" });
      } catch (e) {
        handleRestErrors(e);
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
    setStaticFormValues(payload) {
      const result = this.staticFieldsDescriptor.reduce((accum, item) => {
        const { key } = item;
        const value = payload[key];

        if (item.type === "date" || item.type === "datetime") {
          return {
            ...accum,
            [key]: value ? new Date(value) : null,
          };
        }

        if (
          ["owner_id", "project_manager_id", "project_head_id"].includes(
            key
          ) === true
        ) {
          const objectValue = this.userOptions.find(
            (item) => item.id === value
          );

          return {
            ...accum,
            [key]: objectValue,
          };
        }

        if (key === "direction_id") {
          const objectValue = this.directionOptions.find(
            (item) => item.id === value
          );

          if (objectValue) {
            return {
              ...accum,
              [key]: objectValue,
            };
          }

          return {
            ...accum,
            [key]: null,
          };
        }

        if (key === "stage_id") {
          const objectValue = this.projectStageOptions.find(
            (item) => item.id === value
          );

          if (objectValue) {
            return {
              ...accum,
              [key]: objectValue,
            };
          }

          return {
            ...accum,
            [key]: null,
          };
        }

        if (key === "customer_id") {
          const objectValue = this.customerOptions.find(
            (item) => item.id === value
          );

          if (objectValue) {
            return {
              ...accum,
              [key]: objectValue,
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

      this.formValues = result;
    },
    async handleFetchData() {
      try {
        const { data } = await fetchProjectBySlug({ slug: this.slug });

        this.entityModel = data;

        await this.initFormWithDynamicFields({
          itemId: data.id,
          data,
        }); // formDynamicFieldsMixin
      } catch (e) {
        console.log(e);
      }
    },
    async fetchDictionaries() {
      try {
        this.isDictionaryLoading = true;

        const promises = [
          fetchUsers(),
          fetchDirectionsDictionary(),
          fetchProjectStagesDictionary(),
          fetchCustomersDictionary(),
        ];

        const [
          { data: userOptions },
          { data: directionOptions },
          { data: projectStageOptions },
          { data: customerOptions },
        ] = await Promise.all(promises);

        this.userOptions = userOptions.map((item) => {
          const { id, lastname, name, middle_name, pname } = item;
          const fullName = getJoinString([lastname, name, middle_name]);
          const position = pname?.toLowerCase();

          return {
            ...item,
            id: String(id),
            full_name: fullName,
            subname: position,
          };
        });
        this.directionOptions = directionOptions;
        this.projectStageOptions = projectStageOptions;
        this.customerOptions = customerOptions;
      } catch (e) {
        console.log(e);
      } finally {
        this.isDictionaryLoading = false;
      }
    },
    async init() {
      await this.fetchDictionaries();

      if (this.isEdit === false) {
        await this.initFormWithDynamicFields(); // formDynamicFieldsMixin

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
