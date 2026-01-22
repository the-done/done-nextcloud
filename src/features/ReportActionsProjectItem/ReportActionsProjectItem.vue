/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <ReportActionsItem :hidden-actions-count="1">
    <template #actions>
      <slot name="actions" />
    </template>
    <template #hover-actions>
      <NcButton
        :aria-label="contextTranslate('Project data', context)"
        :to="{
          name: 'project-edit',
          params: { slug: modelData.project_id },
        }"
      >
        <template #icon>
          <InformationSlabCircleOutline :size="20" />
        </template>
      </NcButton>
    </template>
    <div class="flex items-center gap-2">
      <div class="text-base font-medium">
        {{ modelData.project_title }}
      </div>
      <VTimeChip :value="modelData.total" :variant="chipVariant" />
    </div>
  </ReportActionsItem>
</template>

<script>
import { NcButton } from "@nextcloud/vue";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import InformationSlabCircleOutline from "vue-material-design-icons/InformationSlabCircleOutline.vue";

import { ReportActionsItem, VTimeChip } from "@/shared/components";

export default {
  name: "ReportActionsProjectItem",
  mixins: [contextualTranslationsMixin],
  data: () => ({
    context: "admin/users",
  }),
  props: {
    modelData: {
      type: Object,
      default: () => ({}),
    },
    activeDate: {
      type: Date,
      default: null,
    },
    activeRangeType: {
      type: String,
      default: null,
    },
    chipVariant: {
      type: String,
      default: "",
    },
  },
  components: {
    NcButton,
    InformationSlabCircleOutline,
    ReportActionsItem,
    VTimeChip,
  },
};
</script>
