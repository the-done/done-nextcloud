<template>
  <div :class="['pt-2 text-sm', 'border-t border-(--color-border-dark)']">
    {{ textFormula }}
  </div>
</template>

<script>
import { transformFormulaToString } from "@/shared/lib/helpers/contractFormula";

export default {
  name: "ContractParameterFormulaPreview",
  props: {
    value: {
      type: Array,
      default: () => [],
    },
    parameterOptions: {
      type: Array,
      default: () => [],
    },
  },
  computed: {
    textFormula() {
      return transformFormulaToString(this.value, this.parameterOptions);
    },
  },
};
</script>
