/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <div data-component-id="EntityPreview" class="flex flex-col gap-8">
    <div v-if="enableAppearancePreview" class="flex flex-col gap-4">
      <div
        :style="{
          backgroundImage: backgroundImage?.file_url
            ? `url(${backgroundImage.file_url})`
            : null,
        }"
        class="relative h-[200px] max-w-[768px] rounded-(--border-radius-large) bg-(--color-background-dark) bg-cover bg-center"
      >
        <div
          v-if="!backgroundImage?.file_url"
          class="flex h-full justify-center"
        >
          <NcEmptyContent
            :name="contextTranslate('No background')"
            :description="contextTranslate('Click here to set background.')"
          >
            <template #icon>
              <ImageEditOutline />
            </template>
          </NcEmptyContent>
        </div>
        <input
          :style="{ height: '100%' }"
          type="file"
          class="absolute inset-0 opacity-0"
          @change="
            (e) =>
              handleFileUpload({
                fieldName: 'bg_image',
                image: e.target.files[0],
              })
          "
        />
      </div>
      <div class="flex gap-4">
        <NcPopover :triggers="['hover']" class="v-cursor v-cursor--pointer">
          <template #trigger="{ attrs }">
            <div v-bind="attrs" class="relative overflow-hidden">
              <NcAvatar
                :is-no-user="true"
                :size="48"
                :url="avatarImage?.file_url || ''"
                :display-name="contextTranslate('Avatar', context)"
              />
              <input
                :style="{ height: '100%' }"
                type="file"
                class="absolute inset-0 opacity-0 m-0! p-0! z-100"
                @change="
                  (e) =>
                    handleFileUpload({
                      fieldName: 'avatar',
                      image: e.target.files[0],
                    })
                "
              />
            </div>
          </template>
          <div class="p-4">{{ contextTranslate("Avatar", context) }}</div>
        </NcPopover>
        <NcPopover :triggers="['hover']" class="v-cursor v-cursor--pointer">
          <template #trigger="{ attrs }">
            <div v-bind="attrs" class="relative overflow-hidden">
              <NcAvatar
                :is-no-user="true"
                :size="48"
                :url="symbolImage?.file_url || ''"
                :display-name="contextTranslate('Symbol', context)"
              />
              <input
                :style="{ height: '100%' }"
                type="file"
                class="absolute inset-0 opacity-0 m-0! p-0! z-100"
                @change="
                  (e) =>
                    handleFileUpload({
                      fieldName: 'symbol',
                      image: e.target.files[0],
                    })
                "
              />
            </div>
          </template>
          <div class="p-4">{{ contextTranslate("Symbol", context) }}</div>
        </NcPopover>
        <NcPopover :triggers="['hover']" class="v-cursor v-cursor--pointer">
          <template #trigger="{ attrs }">
            <div
              v-bind="attrs"
              :style="{
                backgroundColor: entityColor?.value || 'transparent',
              }"
              :class="[
                'relative overflow-hidden size-[48px] rounded-full',
                !entityColor?.value &&
                  'border-2 border-(--color-border-dark) hover:border-(--color-border-maxcontrast)',
              ]"
            >
              <input
                :style="{ width: '100%', height: '100%' }"
                type="color"
                class="absolute inset-0 opacity-0 m-0! p-0! z-100"
                @change="handleColorSubmit"
              />
            </div>
          </template>
          <div class="p-4">{{ contextTranslate("Color", context) }}</div>
        </NcPopover>
      </div>
    </div>
    <div
      class="group/block inline-block pr-(--default-clickable-area) min-h-(--default-clickable-area)"
    >
      <div
        class="inline-block relative mb-2 text-xl font-medium min-w-[320px] pr-(--default-clickable-area) min-h-(--default-clickable-area)"
      >
        {{ contextTranslate("Main", context) }}
        <div
          v-if="clickBlockEnabled === true"
          class="absolute right-0 -top-1 group-hover/block:block md:hidden"
        >
          <NcActions>
            <NcActionButton @click="() => handleClickBlock('main')">
              <template #icon>
                <Pencil :size="20" />
              </template>
              {{ contextTranslate("Edit", context) }}
            </NcActionButton>
          </NcActions>
        </div>
      </div>
      <div
        v-if="modelData"
        class="pb-4 grid grid-cols-1 sm:gap-x-8 sm:gap-y-2 sm:grid-cols-[minmax(200px,max-content)_1fr]"
      >
        <div v-for="item in modelData" :key="item.title" class="contents">
          <div class="font-medium">
            {{ item.title }}
          </div>
          <div class="flex mb-4 sm:mb-0">
            <span v-if="item.value"> {{ item.value }}</span>
            <Cancel v-else class="text-(--color-placeholder-dark)" />
          </div>
        </div>
      </div>
    </div>
    <template v-if="externalData">
      <div v-for="block in externalData" :key="block.title" class="group/block">
        <div
          class="inline-block relative mb-2 text-xl font-medium min-w-[320px] pr-(--default-clickable-area) min-h-(--default-clickable-area)"
        >
          {{ block.title }}
          <div
            v-if="clickBlockEnabled === true"
            class="absolute right-0 -top-1 group-hover/block:block md:hidden"
          >
            <NcActions>
              <NcActionButton
                @click="() => handleClickBlock(block.frontend_type)"
              >
                <template #icon>
                  <Pencil :size="20" />
                </template>
                {{ contextTranslate("Edit", context) }}
              </NcActionButton>
            </NcActions>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <template v-if="block.value?.length > 0">
            <NcUserBubble
              v-for="(item, itemIndex) in block.value"
              :key="itemIndex"
              :display-name="item"
              :size="32"
            >
              <span class="p-2">
                {{ item }}
              </span>
            </NcUserBubble>
          </template>
          <div v-else class="text-(--color-text-maxcontrast)">
            {{ contextTranslate("No data found", context) }}
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import {
  NcActions,
  NcActionButton,
  NcUserBubble,
  NcAvatar,
  NcEmptyContent,
  NcPopover,
} from "@nextcloud/vue";

import Pencil from "vue-material-design-icons/Pencil.vue";
import Cancel from "vue-material-design-icons/Cancel.vue";
import ImageEditOutline from "vue-material-design-icons/ImageEditOutline.vue";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "EntityPreview",
  mixins: [contextualTranslationsMixin],
  emits: ["onClickBlock", "onFileUpload", "onColorSubmit"],
  components: {
    NcActions,
    NcActionButton,
    NcUserBubble,
    NcAvatar,
    NcEmptyContent,
    NcPopover,
    Pencil,
    Cancel,
    ImageEditOutline,
  },
  props: {
    modelValue: {
      type: Object,
      default: null,
    },
    enableAppearancePreview: {
      type: Boolean,
      default: false,
    },
    clickBlockEnabled: {
      type: Boolean,
      default: true,
    },
  },
  computed: {
    modelData() {
      if (!this.modelValue?.model_data) {
        return null;
      }

      return this.modelValue.model_data;
    },
    externalData() {
      if (!this.modelValue?.external_data) {
        return null;
      }

      return this.modelValue.external_data;
    },
    appearanceData() {
      if (!this.modelValue?.appearance_data) {
        return null;
      }

      return this.modelValue.appearance_data;
    },
    backgroundImage() {
      if (!this.appearanceData) {
        return null;
      }

      return this.appearanceData.find((item) => item.field_name === "bg_image");
    },
    avatarImage() {
      if (!this.appearanceData) {
        return null;
      }

      return this.appearanceData.find((item) => item.field_name === "avatar");
    },
    symbolImage() {
      if (!this.appearanceData) {
        return null;
      }

      return this.appearanceData.find((item) => item.field_name === "symbol");
    },
    entityColor() {
      if (!this.appearanceData) {
        return null;
      }

      return this.appearanceData.find((item) => item.field_name === "color");
    },
  },
  methods: {
    handleClickBlock(key) {
      this.$emit("onClickBlock", key);
    },
    handleFileUpload({ fieldName, image }) {
      this.$emit("onFileUpload", { fieldName, image });
    },
    handleColorSubmit(e) {
      const { value } = e.target;

      this.$emit("onColorSubmit", value);
    },
  },
};
</script>
