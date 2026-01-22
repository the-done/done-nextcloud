<template>
  <div class="flex-[0_0_auto] flex flex-wrap">
    <RouterLink
      v-for="item in value"
      :key="item.key"
      :to="item.to"
      :exact="item.exact"
      class="v-tabs-link flex items-center min-h-[52px] py-1 px-4 border-b-[2px] font-bold"
    >
      {{ item.label }}
    </RouterLink>
  </div>
</template>

<script>
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "VTabs",
  mixins: [contextualTranslationsMixin],
  props: {
    value: {
      type: Array,
      default: [],
    },
  },
};
</script>

<style scoped>
.v-tabs-link {
  border-color: transparent;
}

.v-tabs-link.router-link-active {
  color: var(--color-primary-element);
  border-bottom-color: var(--color-primary-element);
}
</style>
