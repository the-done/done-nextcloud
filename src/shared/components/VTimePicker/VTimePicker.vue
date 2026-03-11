/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <div data-component-id="VTimePicker" class="flex items-center gap-2">
    <div class="flex flex-col items-center gap-1">
      <NcButton
        type="tertiary"
        :aria-label="contextTranslate('Increase hours')"
        @click="handleChangeHours(1)"
      >
        <template #icon>
          <ChevronUp :size="20" />
        </template>
      </NcButton>
      <VTextField
        :value="hours"
        class="v-time-picker-input"
        @input="handleUpdateHours"
      />
      <NcButton
        type="tertiary"
        :aria-label="contextTranslate('Decrease hours')"
        @click="handleChangeHours(-1)"
      >
        <template #icon>
          <ChevronDown :size="20" />
        </template>
      </NcButton>
    </div>

    <div class="font-medium">:</div>

    <div class="flex flex-col items-center gap-1">
      <NcButton
        type="tertiary"
        :aria-label="contextTranslate('Increase minutes')"
        @click="handleChangeMinutes(15)"
      >
        <template #icon>
          <ChevronUp :size="20" />
        </template>
      </NcButton>
      <VTextField
        :value="minutes"
        class="v-time-picker-input"
        @input="handleUpdateMinutes"
      />
      <NcButton
        type="tertiary"
        :aria-label="contextTranslate('Decrease minutes')"
        @click="handleChangeMinutes(-15)"
      >
        <template #icon>
          <ChevronDown :size="20" />
        </template>
      </NcButton>
    </div>
  </div>
</template>

<script>
import { NcButton } from "@nextcloud/vue";

import ChevronUp from "vue-material-design-icons/ChevronUp.vue";
import ChevronDown from "vue-material-design-icons/ChevronDown.vue";

import { VTextField } from "@/shared/components";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "VTimePicker",
  components: {
    NcButton,
    VTextField,
    ChevronUp,
    ChevronDown,
  },
  mixins: [contextualTranslationsMixin],
  emits: ["input"],
  props: {
    value: {
      type: Number,
      default: 0,
    },
  },
  computed: {
    hours() {
      return Math.floor(this.value / 60);
    },
    minutes() {
      return this.value % 60;
    },
  },
  methods: {
    emitValue(hours, minutes) {
      const totalMinutes = hours * 60 + minutes;

      this.$emit("input", totalMinutes);
    },
    handleUpdateHours(value) {
      const hours = Math.max(0, parseInt(value) || 0);

      this.emitValue(hours, this.minutes);
    },
    handleUpdateMinutes(value) {
      const minutes = Math.max(0, Math.min(59, parseInt(value) || 0));

      this.emitValue(this.hours, minutes);
    },
    handleChangeHours(step) {
      const newHours = Math.max(0, this.hours + step);

      this.emitValue(newHours, this.minutes);
    },
    handleChangeMinutes(step) {
      let newMinutes = this.minutes + step;
      let newHours = this.hours;

      if (newMinutes >= 60) {
        newHours++;
        newMinutes = 0;
      } else if (newMinutes < 0) {
        if (newHours > 0) {
          newHours--;
          newMinutes = 45;
        } else {
          newMinutes = 0;
        }
      }

      this.emitValue(newHours, newMinutes);
    },
  },
};
</script>

<style scoped>
.v-time-picker-input {
  width: 80px;
}

.v-time-picker-input:deep(input) {
  text-align: center;
}
</style>
