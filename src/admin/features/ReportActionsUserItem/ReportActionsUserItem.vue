/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <ReportActionsItem
    :hidden-actions-count="2"
    :class="[
      'report-actions-user-item',
      userPosition === '' && 'report-actions-user-item--no-position',
    ]"
  >
    <template #actions>
      <slot name="actions" />
    </template>
    <template #hover-actions>
      <NcButton
        :aria-label="contextTranslate('Employee statistics', context)"
        @click="() => handleClickUserStatistics({ slug: modelData.user_slug })"
      >
        <template #icon>
          <ClockTimeEightOutline :size="20" />
        </template>
      </NcButton>
      <NcButton
        :aria-label="contextTranslate('Employee profile', context)"
        :to="{ name: 'staff-edit', params: { slug: modelData.user_slug } }"
      >
        <template #icon>
          <Account :size="20" />
        </template>
      </NcButton>
    </template>
    <div class="report-actions-user-item__content">
      <NcListItemIcon :name="modelData.user_name" :subname="userPosition" />
      <span class="report-actions-user-item__time">
        <VTimeChip :value="modelData.total" :variant="chipVariant" />
      </span>
    </div>
  </ReportActionsItem>
</template>

<script>
import { NcListItemIcon, NcButton } from "@nextcloud/vue";
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

import Account from "vue-material-design-icons/Account.vue";
import ClockTimeEightOutline from "vue-material-design-icons/ClockTimeEightOutline.vue";

import ReportActionsItem from "@/admin/shared/components/ReportActionsItem/ReportActionsItem.vue";
import VTimeChip from "@/common/shared/components/VTimeChip/VTimeChip.vue";

import { redirectToUserStatistics } from "@/admin/shared/lib/helpers";
import { getJoinString } from "@/common/shared/lib/helpers";

export default {
  name: "ReportActionsUserItem",
  mixins: [contextualTranslationsMixin],
  data: () => ({
    context: 'admin/users',
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
    showPosition: {
      type: Boolean,
      default: true,
    },
  },
  components: {
    NcListItemIcon,
    NcButton,
    Account,
    ClockTimeEightOutline,
    ReportActionsItem,
    VTimeChip,
  },
  computed: {
    userPosition() {
      const { user_roles_in_project } = this.modelData;

      if (this.showPosition === false) {
        return "";
      }

      if (!user_roles_in_project || user_roles_in_project.length === 0) {
        return this.t('done', 'No role in project');
      }

      return getJoinString(user_roles_in_project, ", ");
    },
  },
  methods: {
    t,
    handleClickUserStatistics({ slug }) {
      redirectToUserStatistics({
        slug,
        activeDate: this.activeDate,
        activeRangeType: this.activeRangeType,
        $router: this.$router,
      });
    },
  },
};
</script>

<style scoped>
.report-actions-user-item__content {
  display: flex;
  gap: 16px;
}

.report-actions-user-item__time {
  margin-top: 4px;
}

.report-actions-user-item--no-position .report-actions-user-item__content {
  align-items: center;
}

.report-actions-user-item--no-position .report-actions-user-item__time {
  margin-top: 0;
}
</style>
