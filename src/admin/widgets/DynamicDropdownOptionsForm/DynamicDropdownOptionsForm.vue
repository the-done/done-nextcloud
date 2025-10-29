/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VAside :value="active" @input="handleClose">
    <template #title>
      {{ modelData.title }}
    </template>
    <div v-if="isLoading === true" class="relative w-full h-full">
      <VLoader absolute />
    </div>
    <template v-else>
      <div class="v-border-bottom mb-4 p-4">
        <DynamicDropdownOptionsItem @on-submit="handleSaveDropdownOption" />
      </div>
      <div
        v-if="options?.length === 0"
        class="text-(--color-text-lighter) px-4"
      >
        {{ contextTranslate("No options available", context) }}
      </div>
      <Draggable
        v-else
        :value="optionsList"
        :animation="150"
        handle="[data-handle]"
        class="flex flex-col gap-2 px-4"
        @input="handleUpdateOptionsOrdering"
      >
        <DynamicDropdownOptionsItem
          v-for="item in optionsList"
          :key="item.slug"
          :value="item"
          @on-submit="handleSaveDropdownOption"
          @on-delete="handleDeleteDropdownOption"
        />
      </Draggable>
    </template>
  </VAside>
</template>

<script>
import Draggable from "vuedraggable";

import {
  fetchDropdownOptions,
  saveDropdownOption,
  deleteDropdownOption,
  reorderDropdownOptions,
} from "@/admin/entities/dynamicFields/api";

import { VAside } from "@/common/shared/components/VAside";
import VLoader from "@/common/shared/components/VLoader/VLoader.vue";

import { DynamicDropdownOptionsItem } from "./components/DynamicDropdownOptionsItem";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { handleRestErrors } from "@/common/shared/lib/helpers";

export default {
  name: "DynamicDropdownOptionsForm",
  mixins: [contextualTranslationsMixin],
  emits: ["on-close"],
  components: {
    Draggable,
    VAside,
    VLoader,
    DynamicDropdownOptionsItem,
  },
  props: {
    active: {
      type: Boolean,
      default: false,
    },
    modelData: {
      type: Object,
      default: () => ({}),
    },
  },
  data: () => ({
    isLoading: false,
    options: [],
    tempOrderedOptions: [],
  }),
  computed: {
    optionsList() {
      if (this.tempOrderedOptions?.length > 0) {
        return this.tempOrderedOptions;
      }

      return this.options;
    },
  },
  methods: {
    t,
    handleClose() {
      this.$emit("on-close");
    },
    setOptions({ slug, option_label }) {
      const result = {
        slug: slug,
        label: option_label,
      };

      if (this.tempOrderedOptions?.length > 0) {
        this.tempOrderedOptions.push(result);

        return;
      }

      this.options.push(result);
    },
    async handleSaveDropdownOption({ slug, label }) {
      try {
        const { data } = await saveDropdownOption({
          slug,
          dyn_field_id: this.modelData.slug,
          option_label: label,
        });

        this.$notify({
          text: this.contextTranslate(
            "Record saved successfully",
            this.context
          ),
          type: "success",
          duration: 2 * 1000,
        });

        if (slug) {
          return;
        }

        this.setOptions(data);
      } catch (e) {
        handleRestErrors(e);
      }
    },
    async handleDeleteDropdownOption({ slug }) {
      try {
        await deleteDropdownOption({
          slug,
        });

        const result = this.options.filter((item) => item.slug !== slug);

        this.options = result;

        this.$notify({
          text: t("done", "Field deleted successfully"),
          type: "success",
          duration: 2 * 1000,
        });
      } catch (e) {
        handleRestErrors(e);
      }
    },
    async handleUpdateOptionsOrdering(value) {
      this.tempOrderedOptions = value;

      await reorderDropdownOptions({
        dyn_field_id: this.modelData.slug,
        option_ids: value.map((item) => item.slug),
      });
    },
    async init() {
      this.isLoading = true;

      try {
        const { data } = await fetchDropdownOptions(this.modelData.slug);

        this.tempOrderedOptions = [];

        this.options = data.map((item) => ({
          slug: item.slug,
          label: item.option_label,
        }));
      } catch (e) {
        handleRestErrors(e);
      } finally {
        this.isLoading = false;
      }
    },
  },
  watch: {
    "modelData.slug"() {
      this.init();
    },
  },
};
</script>
