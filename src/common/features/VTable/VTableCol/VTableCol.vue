<template>
  <td :class="['v-table-col', customClass]" :data-name="name">
    <slot />
  </td>
</template>

<script>
export default {
  name: "VTableCol",
  props: {
    name: {
      type: String,
      default: "",
    },
    customClass: {
      type: [String, Object, Array],
      default: "",
    },
  },
};
</script>
