<template>
  <div class="v-table-dropdown__item">
    <div
      class="v-table-dropdown__label v-flex v-flex--justify-between v-cursor v-cursor--pointer"
      @click="handleClick"
    >
      <div class="v-flex v-flex--gap-1">
        <FilterVariant :size="24" />
        Filter
      </div>
    </div>
  </div>
</template>

<script>
import FilterVariant from "vue-material-design-icons/FilterVariant.vue";
import ChevronRight from "vue-material-design-icons/ChevronRight.vue";
import { mapState } from "pinia";

import { useDynamicTableStore } from "@/admin/app/store/dynamicTable";

import VDropdown from "@/common/shared/components/VDropdown/VDropdown.vue";
import VTextField from "@/common/shared/components/VTextField/VTextField.vue";

export default {
  name: "VTableHeadFilter",
  emits: ["on-create-filter"],
  components: {
    FilterVariant,
    ChevronRight,
    VDropdown,
    VTextField,
  },
  props: {
    item: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {};
  },
  computed: {
    ...mapState(useDynamicTableStore, ["filterConditionsList"]),
  },
  methods: {
    handleClick() {
      this.$emit("on-create-filter", this.item);
    },
  },
};
</script>
