<template>
  <div
    class="flex flex-col h-full border-t border-(--color-border) overflow-hidden md:flex-row"
    data-component-id="VPageLayout"
  >
    <slot />
  </div>
</template>

<script>
export default {
  name: "VPageLayout",
};
</script>
