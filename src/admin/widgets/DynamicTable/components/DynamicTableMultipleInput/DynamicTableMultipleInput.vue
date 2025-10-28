<template>
  <div class="v-flex v-flex--column v-flex--gap-1">
    <div class="v-flex v-flex--align-center v-flex--gap-1">
      <VTextField v-model="inputValue" placeholder="Type a value" />
      <VButton size="small" variant="tertiary" @click="handleAdd">
        <template #icon>
          <Plus :size="16" />
        </template>
      </VButton>
    </div>
    <template v-if="value?.length">
      <DynamicTableMultipleInputItem
        v-for="(val, index) in value"
        :key="index"
        :value="val"
        :index="index"
        @on-delete="handleDelete"
      />
    </template>
  </div>
</template>

<script>
import Plus from "vue-material-design-icons/Plus.vue";

import { DynamicTableMultipleInputItem } from "../DynamicTableMultipleInputItem";

import VTextField from "@/common/shared/components/VTextField/VTextField.vue";
import VButton from "@/common/shared/components/VButton/VButton.vue";

export default {
  name: "DynamicTableMultipleInput",
  components: {
    Plus,
    DynamicTableMultipleInputItem,
    VTextField,
    VButton,
  },
  props: {
    value: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      inputValue: "",
    };
  },
  emits: ["input"],
  methods: {
    handleAdd() {
      const value = this.inputValue.trim();

      if (!value) {
        return;
      }

      const nextValue = [...this.value, value];

      this.inputValue = "";

      this.$emit("input", nextValue);
    },
    handleDelete(index) {
      const nextValue = this.value.filter((_, i) => i !== index);

      this.$emit("input", nextValue);
    },
  },
};
</script>
