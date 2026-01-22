/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

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

import Account from "vue-material-design-icons/Account.vue";
import ClockTimeEightOutline from "vue-material-design-icons/ClockTimeEightOutline.vue";

import { ReportActionsItem, VTimeChip } from "@/shared/components";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { redirectToUserStatistics } from "@/shared/lib/helpers/navigation";
import { getJoinString } from "@/shared/lib/helpers/string";

export default {
  name: "ReportActionsUserItem",
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
        return this.contextTranslate("No role in project", this.context);
      }

      return getJoinString(user_roles_in_project, ", ");
    },
  },
  methods: {
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
