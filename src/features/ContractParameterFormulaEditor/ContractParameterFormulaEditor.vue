<template>
  <div class="flex flex-col">
    <div class="flex flex-wrap items-center gap-1">
      <ContractParameterFormulaToken
        v-for="(token, index) in value"
        :key="index"
        :index="index"
        :token="token"
        :value="value"
        :parameter-options="parameterOptions"
        @on-change-token="handleChangeToken"
        @on-delete-token="handleDeleteToken"
      />
      <ContractParameterFormulaToken
        :value="value"
        :parameter-options="parameterOptions"
        :index="value.length"
        @on-add-token="handleAddToken"
      />
    </div>
    <ContractParameterFormulaPreview
      v-if="value?.length > 0"
      :value="value"
      class="mt-8 mb-4"
    />
  </div>
</template>

<script>
import { ContractParameterFormulaToken } from "./components/ContractParameterFormulaToken";
import { ContractParameterFormulaPreview } from "./components/ContractParameterFormulaPreview";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "ContractParameterFormulaEditor",
  mixins: [contextualTranslationsMixin],
  components: {
    ContractParameterFormulaToken,
    ContractParameterFormulaPreview,
  },
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
  emits: ["input"],
  methods: {
    handleSetMenuState(value) {
      this.menuState = value;
    },
    handleAddToken(token) {
      this.$emit("input", [...this.value, { ...token }]);
    },
    handleChangeToken({ index, token }) {
      const result = this.value.map((item, i) => {
        if (i === index) {
          return { ...token };
        }

        return item;
      });

      this.$emit("input", result);
    },
    handleDeleteToken(index) {
      this.$emit(
        "input",
        this.value.filter((_, i) => i !== index)
      );
    },
  },
};
</script>
