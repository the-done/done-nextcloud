/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

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
      </NcBreadcrumbs>
    </VToolbar>
    <VPageLayout>
      <VPageContent>
        <VLoader v-if="isInitLoading === true" />
        <template v-else>
          <DynamicFieldCreator
            class="v-border-bottom"
            :loading="isCreateSubmitLoading"
            @on-submit="handleCreateField"
          />
          <div
            v-if="fieldsModelData && fieldsModelData.length > 0"
            class="flex flex-col"
          >
            <DynamicFieldCreator
              v-for="(item, index) in fieldsModelData"
              :key="item.id"
              :modelData="item"
              :loading="loadingStack.includes(item.id)"
              :draggable="true"
              :class="{ 'v-border-top': index > 0 }"
              handle="[data-handle]"
              @on-submit="handleUpdateField"
              @on-delete="handleDeleteField"
              @on-open-dropdown-options-form="handleOpenDropdownOptionsForm"
              @on-open-comment-form="handleOpenCommentForm"
            />
          </div>
          <div v-else class="p-4 text-(--color-text-lighter)">
            {{ contextTranslate("No dynamic fields", context) }}
          </div>
        </template>
      </VPageContent>
    </VPageLayout>
    <DynamicDropdownOptionsForm
      :active="isDropdownOptionsFormActive"
      :model-data="dropdownOptionsModelData"
      @on-close="handleCloseDropdownOptionsForm"
    />
    <DynamicCommentFieldsForm
      :active="isCommentFormActive"
      :source="source"
      :model-data="commentFormModelData"
      @on-close="handleCloseCommentForm"
    />
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb, NcListItem } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";

import TextBoxPlus from "vue-material-design-icons/TextBoxPlus.vue";

import DynamicFieldCreator from "@/admin/widgets/DynamicFieldCreator/DynamicFieldCreator.vue";
import DynamicDropdownOptionsForm from "@/admin/widgets/DynamicDropdownOptionsForm/DynamicDropdownOptionsForm.vue";
import DynamicCommentFieldsForm from "@/admin/widgets/DynamicCommentFieldsForm/DynamicCommentFieldsForm.vue";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
} from "@/common/widgets/VPage";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import VLoader from "@/common/shared/components/VLoader/VLoader.vue";

import {
  fetchDynamicFieldsForSource,
  saveDynamicField,
  deleteDynamicField,
} from "@/admin/entities/dynamicFields/api";

import { getFieldCommentsBySource } from "@/admin/entities/fieldComments/api";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { handleRestErrors } from "@/common/shared/lib/helpers";

export default {
  name: "DynamicFieldsSourcePage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcListItem,
    TextBoxPlus,
    DynamicFieldCreator,
    DynamicDropdownOptionsForm,
    DynamicCommentFieldsForm,
    VPage,
    VPageLayout,
    VPageContent,
    VPagePadding,
    VToolbar,
    VLoader,
  },
  props: {
    additionalProps: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      context: "admin/users",
      isInitLoading: true,
      isCreateSubmitLoading: false,
      fieldsModelData: [],
      loadingStack: [],
      isDropdownOptionsFormActive: false,
      isCommentFormActive: false,
      dropdownOptionsModelData: {},
      commentFormModelData: {},
    };
  },
  computed: {
    source() {
      return this.additionalProps.source;
    },
    breadcrumbs() {
      return this.additionalProps.breadcrumbs;
    },
  },
  methods: {
    t,
    handleOpenDropdownOptionsForm(payload) {
      this.dropdownOptionsModelData = payload;
      this.isDropdownOptionsFormActive = true;
    },
    handleCloseDropdownOptionsForm() {
      this.isDropdownOptionsFormActive = false;
    },
    handleOpenCommentForm(payload) {
      this.commentFormModelData = payload;
      this.isCommentFormActive = true;
    },
    handleCloseCommentForm() {
      this.isCommentFormActive = false;
    },
    async handleDeleteField({ id }) {
      try {
        this.loadingStack.push(id);

        await deleteDynamicField(id);

        this.fieldsModelData = this.fieldsModelData.filter(
          (item) => item.id !== id
        );

        this.$notify({
          text: t("done", "Field deleted successfully"),
          type: "success",
          duration: 2 * 1000,
        });
      } catch (e) {
        handleRestErrors(e);
      } finally {
        this.loadingStack = this.loadingStack.filter((h) => h !== id);
      }
    },
    async handleUpdateField({ id, formValues }) {
      try {
        this.loadingStack.push(id);

        const { title, fieldType, required, multiple } = formValues;

        await saveDynamicField({
          slug: id,
          title,
          required,
          multiple,
          field_type: fieldType.id,
          source: this.source,
        });

        this.$notify({
          text: t("done", "Field updated successfully"),
          type: "success",
          duration: 2 * 1000,
        });
      } catch (e) {
        handleRestErrors(e);
      } finally {
        this.loadingStack = this.loadingStack.filter((h) => h !== id);
      }
    },
    async handleCreateField({ formValues }) {
      try {
        this.isCreateSubmitLoading = true;

        const { title, fieldType, required, multiple } = formValues;

        const {
          data: { slug },
        } = await saveDynamicField({
          title,
          required,
          multiple,
          field_type: fieldType.id,
          source: this.source,
        });

        this.fieldsModelData.push({
          title,
          required,
          multiple,
          field_type: fieldType.id,
          slug,
          id: slug,
          comment: null,
        });
      } catch (e) {
        handleRestErrors(e);
      } finally {
        this.isCreateSubmitLoading = false;
      }
    },
    async init() {
      try {
        this.isInitLoading = true;

        const promises = [
          fetchDynamicFieldsForSource(this.source),
          getFieldCommentsBySource(this.source),
        ];

        const [{ data: fieldsModelData }, { data: fieldComments }] =
          await Promise.all(promises);

        this.fieldsModelData = fieldsModelData.map((item) => {
          return {
            ...item,
            comment: fieldComments?.[item.slug] || null,
          };
        });
      } catch (e) {
        handleRestErrors(e);
      } finally {
        this.isInitLoading = false;
      }
    },
  },
  mounted() {
    this.init();
  },
  watch: {
    $route() {
      this.init();
    },
  },
};
</script>
