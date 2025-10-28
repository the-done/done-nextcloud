<template>
  <VScrollArea>
    <div
      :class="['flex flex-col size-full', contentClass]"
      data-component-id="VPageContent"
    >
      <slot />
    </div>
  </VScrollArea>
</template>

<script>
import VScrollArea from "@/common/shared/components/VScrollArea/VScrollArea.vue";

export default {
  name: "VPageContent",
  props: {
    contentClass: {
      type: String,
      default: "",
    },
  },
  components: {
    VScrollArea,
  },
};
</script>
