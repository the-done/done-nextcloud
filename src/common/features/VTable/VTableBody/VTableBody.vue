<template>
  <tbody :class="['v-table-body', dragInProcess === true && 'v-table-body--transparent']">
    <slot />
  </tbody>
</template>

<script>
export default {
  name: "VTableRow",
  props: {
    dragInProcess: {
      type: Boolean,
      default: false
    }
  }
};
</script>
