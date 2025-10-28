<template>
  <div
    class="v-toolbar flex-[0_0_auto] min-h-[52px] py-1 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 pr-4 pl-13"
    data-component-id="VToolbar"
  >
    <div v-if="title" class="font-medium uppercase">{{ title }}</div>
    <slot />
    <div v-if="hasActionsSlot" class="flex gap-2 flex-[0_0_auto]">
      <slot name="actions" />
    </div>
  </div>
</template>

<script>
export default {
  name: "VToolbar",
  props: {
    title: {
      type: String,
      default: "",
    },
  },
  computed: {
    hasActionsSlot() {
      return !!this.$slots.actions;
    },
  },
};
</script>

<style scoped>
.v-toolbar:deep(.breadcrumb) {
  width: auto;
  height: auto;
}

.v-toolbar:deep(.breadcrumb__crumbs) {
  flex-wrap: wrap;
}
</style>
