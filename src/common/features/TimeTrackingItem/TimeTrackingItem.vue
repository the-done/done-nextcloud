/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div class="flex items-center relative">
    <DragVertical
      v-if="draggable"
      :size="20"
      data-handle
      class="flex-0 cursor-grab"
    />
    <div class="time-tracking-item-shadow w-full lg:max-w-[868px] relative">
      <div
        class="flex items-center justify-between gap-6 w-full p-1 pb-2 lg:pr-[100px] bg-(--color-background-hover) border border-(--color-background-darker) rounded-(--border-radius-element)"
      >
        <div class="flex flex-col pl-2">
          <div
            v-if="modelData.is_downtime === true || modelData.time"
            class="flex gap-2 mt-2 mb-1"
          >
            <VChip
              v-if="modelData.time"
              :square="true"
              :variant="modelData.is_downtime === true ? 'warning' : 'dark'"
              class="nowrap"
            >
              {{ modelData.time }}
            </VChip>
            <VChip
              v-if="modelData.is_downtime === true"
              :square="true"
              variant="warning"
              class="nowrap"
            >
              {{ contextTranslate("Downtime", context) }}
            </VChip>
          </div>
          <div v-if="name" class="font-medium">
            {{ name }}
          </div>
          <div
            v-if="modelData.description"
            class="text-(--color-text-maxcontrast)"
          >
            {{ modelData.description }}
          </div>
          <div v-if="modelData.comment" class="text-(--color-text-maxcontrast)">
            ({{ modelData.comment }})
          </div>
        </div>
        <div class="flex items-center gap-1">
          <NcButton
            v-if="disabled === false"
            type="secondary"
            :aria-label="contextTranslate('Edit', context)"
            :to="{
              name: 'time-tracking-edit',
              params: { slug: modelData.slug },
            }"
          >
            <template #icon>
              <Pencil :size="20" />
            </template>
          </NcButton>
          <NcButton
            v-if="modelData.task_link"
            :href="modelData.task_link"
            type="tertiary"
            target="_blank"
            :aria-label="contextTranslate('Open task link', context)"
          >
            <template #icon>
              <OpenInNew :size="20" />
            </template>
          </NcButton>
          <NcActions v-if="disabled === false">
            <NcActionButton @click="handleCopy">
              <template #icon>
                <ContentCopy />
                {{ contextTranslate("Copy", context) }}
              </template>
            </NcActionButton>
            <NcActionButton @click="handleDelete">
              <template #icon>
                <TrashCanOutline />
                {{ contextTranslate("Delete", context) }}
              </template>
            </NcActionButton>
          </NcActions>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { NcButton, NcActions, NcActionButton } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import Pencil from "vue-material-design-icons/Pencil.vue";
import OpenInNew from "vue-material-design-icons/OpenInNew.vue";
import ContentCopy from "vue-material-design-icons/ContentCopy.vue";
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";
import DragVertical from "vue-material-design-icons/DragVertical.vue";

import VChip from "@/common/shared/components/VChip/VChip.vue";

export default {
  name: "TimeTrackingItem",
  emits: ["onCopy", "onDelete"],
  components: {
    NcButton,
    NcActions,
    NcActionButton,
    Pencil,
    OpenInNew,
    ContentCopy,
    TrashCanOutline,
    DragVertical,
    VChip,
  },
  mixins: [contextualTranslationsMixin],
  data: () => ({
    context: "user/time-tracking",
  }),
  props: {
    modelData: {
      type: Object,
      default: {},
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    showProject: {
      type: Boolean,
      default: true,
    },
    draggable: {
      type: Boolean,
      default: false,
    },
  },
  computed: {
    name() {
      if (this.showProject === true) {
        return (
          this.modelData.pname ||
          this.modelData.project_id ||
          this.modelData.slug ||
          this.modelData.id
        );
      }

      return "";
    },
  },
  methods: {
    t,
    handleCopy() {
      this.$emit("onCopy", this.modelData);
    },
    handleDelete() {
      this.$emit("onDelete", {
        slug: this.modelData.slug,
        slug_type: this.modelData.slug_type,
      });
    },
  },
};
</script>

<style scoped>
.time-tracking-item-shadow:after {
  content: "";
  display: none;
  position: absolute;
  top: 0;
  right: 0;
  width: 100px;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent 0%,
    var(--color-main-background) 100%
  );
}

@media (width >= 1024px) {
  .time-tracking-item-shadow:after {
    display: block;
  }
}
</style>
