/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VAside :value="active" @input="handleClose">
    <template #title>
      {{ contextTranslate("Comment for field") }}
      «{{ modelData.title }}»
    </template>
    <div v-if="isLoading === true" class="relative w-full h-full">
      <VLoader absolute />
    </div>
    <div v-else class="p-4">
      <VTextArea
        v-model="formValues.comment"
        :placeholder="contextTranslate('Comment for field')"
      />
      <div class="flex justify-end">
        <VButton :disabled="isLoading" @click="handleSubmitForm">
          {{ contextTranslate("Save") }}
        </VButton>
      </div>
    </div>
  </VAside>
</template>

<script>
import {
  saveFieldComment,
  deleteFieldComment,
} from "@/entities/fieldComments/api";

import { VAside, VLoader, VTextArea, VButton } from "@/shared/components";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { handleRestErrors } from "@/shared/lib/helpers/errors";

export default {
  name: "DynamicCommentFieldsForm",
  mixins: [contextualTranslationsMixin],
  emits: ["on-close"],
  components: {
    VAside,
    VLoader,
    VTextArea,
    VButton,
  },
  props: {
    active: {
      type: Boolean,
      default: false,
    },
    source: {
      type: Number,
      default: null,
    },
    modelData: {
      type: Object,
      default: () => ({}),
    },
  },
  data: () => ({
    isLoading: false,
    formValues: {
      comment: "",
    },
  }),
  methods: {
    handleClose() {
      this.$emit("on-close");
    },
    notifySuccess() {
      this.$notify({
        text: this.contextTranslate("Record saved successfully"),
        type: "success",
        duration: 2 * 1000,
      });
    },
    handleSuccess(payload) {
      this.notifySuccess();

      this.modelData.comment = payload;

      this.$emit("on-close");
    },
    async handleSubmitForm() {
      try {
        const commentText = this.formValues.comment.trim();

        if (commentText === "" && this.modelData.comment?.slug) {
          await deleteFieldComment({ commentId: this.modelData.comment.slug });

          this.handleSuccess(null);

          return;
        }

        const {
          data: { data },
        } = await saveFieldComment({
          source: this.source,
          field: this.modelData.slug,
          comment: commentText,
          commentId: this.modelData.comment?.slug || undefined,
        });

        this.handleSuccess(data);
      } catch (e) {
        handleRestErrors(e);
      }
    },
    async init() {
      this.formValues.comment = this.modelData.comment?.comment || "";
    },
  },
  watch: {
    "modelData.slug"() {
      this.init();
    },
  },
};
</script>
