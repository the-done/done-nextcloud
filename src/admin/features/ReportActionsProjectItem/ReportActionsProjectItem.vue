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
      <div class="font-medium">
        {{ modelData.project_title }}
      </div>
      <VTimeChip :value="modelData.total" :variant="chipVariant" />
    </div>
  </ReportActionsItem>
</template>

<script>
import { NcListItemIcon, NcButton } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import InformationSlabCircleOutline from "vue-material-design-icons/InformationSlabCircleOutline.vue";

import ReportActionsItem from "@/admin/shared/components/ReportActionsItem/ReportActionsItem.vue";
import VTimeChip from "@/common/shared/components/VTimeChip/VTimeChip.vue";

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
    NcListItemIcon,
    NcButton,
    InformationSlabCircleOutline,
    ReportActionsItem,
    VTimeChip,
  },
  methods: {
    t,
  },
};
</script>
