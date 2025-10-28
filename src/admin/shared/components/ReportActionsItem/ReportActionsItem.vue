<template>
  <div class="report-actions-item">
    <div v-if="hasActionsSlot === true" class="report-actions-item__actions">
      <slot name="actions" />
    </div>
    <div class="report-actions-item__content" :style="paddingLeft">
      <div class="report-actions-item__hover-actions">
        <slot name="hover-actions" />
      </div>
      <slot />
    </div>
  </div>
</template>

<script>
export default {
  name: "ReportActionsItem",
  props: {
    hiddenActionsCount: {
      type: Number,
      deafult: 0,
    },
  },
  computed: {
    hasActionsSlot() {
      return !!this.$slots.actions;
    },
    paddingLeft() {
      const { hiddenActionsCount } = this;

      return `--report-actions-item-padding-left: ${
        hiddenActionsCount * 32 + hiddenActionsCount * 8 + 32
      }px`;
    },
  },
};
</script>

<style scoped>
.report-actions-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.report-actions-item__content {
  position: relative;
}

.report-actions-item__hover-actions {
  opacity: 0;
  width: 0;
  position: absolute;
  top: 50%;
  left: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  overflow: hidden;
  transform: translateY(-50%);
  transition: opacity 0.2s ease, width 0.2s ease;
  background: linear-gradient(
    90deg,
    rgba(var(--color-main-background-rgb), 1) 79%,
    rgba(var(--color-main-background-rgb), 0) 100%
  );
  z-index: 10;
}

.report-actions-item__content:hover .report-actions-item__hover-actions {
  opacity: 1;
  width: var(--report-actions-item-padding-left);
}
</style>
