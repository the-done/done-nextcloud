<template>
  <div class="v-table-edit-cell-dropdown">
    <NcButton
      v-if="isActive === false"
      :disabled="loading === true"
      type="tertiary"
      :ariaLabel="contextTranslate('Edit', context)"
      class="v-table-edit-cell-dropdown__label"
      @click="handleOpenEdit"
    >
      <template #icon>
        <NcLoadingIcon v-if="loading === true" />
        <Pencil v-else :size="20" />
      </template>
      {{ activeValueLabel }}
    </NcButton>
    <div v-else class="v-simple-inline-form v-flex--nowrap">
      <VDropdown
        v-model="modelValue"
        :options="options"
        :value-label="valueLabel"
        :aria-label="ariaLabel"
        :style="{ width: '360px' }"
      />
      <NcButton
        v-if="isValueChanged === true"
        :disabled="loading"
        :ariaLabel="contextTranslate('Save', context)"
        @click="handleSubmit"
      >
        <template #icon>
          <CheckCircleOutline :size="20" />
        </template>
      </NcButton>
      <NcButton :ariaLabel="contextTranslate('Cancel', context)" @click="handleCloseEdit">
        <template #icon>
          <Close :size="20" />
        </template>
      </NcButton>
    </div>
  </div>
</template>

<script>
import { NcButton, NcLoadingIcon } from "@nextcloud/vue";
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

import Pencil from "vue-material-design-icons/Pencil.vue";
import CheckCircleOutline from "vue-material-design-icons/CheckCircleOutline.vue";
import Close from "vue-material-design-icons/Close.vue";

import VDropdown from "@/common/shared/components/VDropdown/VDropdown.vue";

export default {
  name: "VTableEditCellDropdown",
  components: {
    NcButton,
    NcLoadingIcon,
    Pencil,
    CheckCircleOutline,
    Close,
    VDropdown,
  },
  emits: ["onSubmit"],
  mixins: [contextualTranslationsMixin],
  props: {
    value: {
      type: [String, Number, Object],
      default: null,
    },
    options: {
      type: Array,
      default: () => [],
    },
    valueLabel: {
      type: String,
      default: "name",
    },
    ariaLabel: {
      type: String,
      default: "",
    },
    loading: {
      type: Boolean,
      default: true,
    },
  },
  data() {
    return {
      isActive: false,
      initValue: null,
      modelValue: null,
    };
  },
  computed: {
    activeValueLabel() {
      const { options, value, valueLabel } = this;

      if (!options || options.length === 0 || !value) {
        return "";
      }

      const result = options.find(
        (item) => item[valueLabel] === value[valueLabel]
      );

      if (!result) {
        return "Undefined";
      }

      return result[valueLabel];
    },
    isValueChanged() {
      if (!this.initValue || !this.modelValue) {
        return false;
      }

      return (
        this.initValue[this.valueLabel] !== this.modelValue[this.valueLabel]
      );
    },
  },
  methods: {
    t,
    handleOpenEdit() {
      this.isActive = true;
    },
    handleCloseEdit() {
      this.modelValue = { ...this.initValue };
      this.isActive = false;
    },
    handleSubmit() {
      this.$emit("onSubmit", {
        value: this.modelValue,
        initValue: this.initValue,
      });

      this.initValue = { ...this.modelValue };
      this.isActive = false;
    },
  },
  watch: {
    value(nextValue) {
      if (this.initValue === null) {
        this.modelValue = { ...nextValue };
        this.initValue = { ...nextValue };
      }
    },
  },
};
</script>

<style scoped>
.v-table-edit-cell-dropdown__label:deep(.button-vue__text) {
  font-weight: 400;
}
</style>
