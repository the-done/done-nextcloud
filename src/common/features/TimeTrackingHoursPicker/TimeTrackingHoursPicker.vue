<template>
  <div>
    <div v-for="item in 8" :key="item" :class="buttonWrapperClass">
      <NcButton
        :type="String(value) === String(item) ? 'primary' : 'secondary'"
        wide
        @click="() => handleClick(item)"
      >
        {{ n("done", "%n hour", "%n hours", item) }}
      </NcButton>
    </div>
  </div>
</template>

<script>
import { NcButton } from "@nextcloud/vue";
import { n } from "@nextcloud/l10n";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "TimeTrackingHoursPicker",
  emits: ["input"],
  components: {
    NcButton,
  },
  mixins: [contextualTranslationsMixin],
  data: () => ({
    context: "user/time-tracking",
  }),
  props: {
    value: {
      type: [String, Number],
      default: "",
    },
    buttonWrapperClass: {
      type: [String, Object, Array],
      default: "",
    },
  },
  methods: {
    n,
    handleClick(item) {
      this.$emit("input", item);
    },
  },
};
</script>
