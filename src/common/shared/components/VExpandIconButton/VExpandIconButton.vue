<template>
  <NcButton :aria-label="ariaLabel" @click="handleClick">
    <template #icon>
      <Minus v-if="expanded === true" :size="size" />
      <ChevronDown v-else :size="size" />
    </template>
  </NcButton>
</template>

<script>
import { NcButton } from "@nextcloud/vue";
import Minus from "vue-material-design-icons/Minus.vue";
import ChevronDown from "vue-material-design-icons/ChevronDown.vue";
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

export default {
  name: "VExpandIconButton",
  components: {
    NcButton,
    Minus,
    ChevronDown,
  },
  emits: ["on-click"],
  mixins: [contextualTranslationsMixin],
  props: {
    expanded: {
      type: Boolean,
      default: false,
    },
    ariaLabel: {
      type: String,
      default: "Expand",
    },
    size: {
      type: Number,
      default: 20,
    },
  },
  methods: {
    t,
    handleClick() {
      this.$emit("on-click", { expanded: this.expanded });
    },
  },
};
</script>
