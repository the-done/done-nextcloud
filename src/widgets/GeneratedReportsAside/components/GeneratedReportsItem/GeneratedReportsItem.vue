/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <div
    data-component-id="GeneratedReportsItem"
    class="py-1 px-4 flex flex-col gap-2"
  >
    <div class="flex gap-4 items-start">
      <div class="flex-1 flex flex-col gap-1 items-start pt-4">
        <VChip
          v-if="modelData.type_title"
          :variant="chipVariant"
          :square="true"
          class="inline-block"
        >
          {{ modelData.type_title }}
        </VChip>
        <VDropdown
          v-model="modelData.project_id"
          :options="projectOptions"
          :aria-label="contextTranslate('Project', context)"
          :placeholder="contextTranslate('Select project', context)"
          :required="true"
          :error="errors.project_id"
          class="w-full"
        />
        <VTextArea
          v-model="modelData.description"
          :required="true"
          :error="errors.description"
          class="w-full"
          inputClass="min-w-full"
        />
      </div>
      <div class="flex-[0_0_auto]">
        <VTimePicker v-model="modelData.minutes" />
      </div>
    </div>
    <div class="flex items-center gap-4 justify-between">
      <NcButton
        v-if="modelData.task_link"
        :href="modelData.task_link"
        type="tertiary"
        target="_blank"
        :aria-label="contextTranslate('Open task link')"
      >
        <template #icon>
          <OpenInNew :size="20" />
        </template>
      </NcButton>
      <div class="flex items-center gap-4">
        <VButton
          :aria-label="contextTranslate('Accept')"
          :loading="loading"
          :style="{ width: 34, padding: 0 }"
          @click="handleAccept"
        >
          <template #icon>
            <Check :size="20" />
          </template>
        </VButton>

        <VButton
          variant="error"
          :aria-label="contextTranslate('Decline')"
          @click="handleDecline"
        >
          <template #icon>
            <Close :size="20" />
          </template>
        </VButton>
      </div>
    </div>
  </div>
</template>

<script>
import { NcButton } from "@nextcloud/vue";
import { required } from "vuelidate/lib/validators";

import OpenInNew from "vue-material-design-icons/OpenInNew.vue";
import Check from "vue-material-design-icons/Check.vue";
import Close from "vue-material-design-icons/Close.vue";

import {
  VTextArea,
  VTimePicker,
  VChip,
  VButton,
  VDropdown,
} from "@/shared/components";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { LOCALSTORAGE_LAST_REPORT_PROJECT_ID } from "@/shared/lib/constants/localStorage";
import { FIELD_IS_REQUIRED_ERROR } from "@/shared/lib/constants/validation";

const VARIANT_MAP = {
  deck: "secondary",
  calendar: "primary",
};

const REQUIRED_FIELDS = ["project_id", "description"];

export default {
  name: "GeneratedReportsItem",
  components: {
    NcButton,
    OpenInNew,
    Check,
    Close,
    VTextArea,
    VTimePicker,
    VChip,
    VButton,
    VDropdown,
  },
  mixins: [contextualTranslationsMixin],
  emits: ["update:minutes", "on-accept", "on-decline"],
  props: {
    item: {
      type: Object,
      required: true,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    projectOptions: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      modelData: {
        type_title: "",
        type_key: "",
        minutes: 0,
        description: "",
        task_link: "",
        project_id: null,
      },
    };
  },
  validations() {
    return {
      modelData: {
        project_id: {
          required,
        },
        description: {
          required,
        },
      },
    };
  },
  computed: {
    chipVariant() {
      return VARIANT_MAP[this.modelData.type_key] || "";
    },
    errors() {
      const requiredFields = REQUIRED_FIELDS.reduce((accum, key) => {
        const field = this.$v.modelData[key];

        if (
          field &&
          field.$invalid &&
          field.$dirty &&
          field.required === false
        ) {
          return {
            ...accum,
            [key]: this.contextTranslate(FIELD_IS_REQUIRED_ERROR),
          };
        }

        return accum;
      }, {});

      return {
        ...requiredFields,
      };
    },
  },
  methods: {
    handleAccept() {
      this.$v.$touch();

      if (this.$v.$invalid === true) {
        return;
      }

      const { uuid, date, description, minutes, task_link } = this.modelData;

      this.$emit("on-accept", {
        uuid,
        date,
        description,
        minutes,
        task_link,
        project_id: this.modelData.project_id.slug,
      });
    },
    handleDecline() {
      this.$emit("on-decline", this.modelData.uuid);
    },
  },
  mounted() {
    const localProjectId = localStorage.getItem(
      LOCALSTORAGE_LAST_REPORT_PROJECT_ID,
    );

    const projectOption = this.projectOptions.find(
      (item) => item.id === localProjectId,
    );

    if (projectOption) {
      this.modelData.project_id = projectOption;
    }
  },
  watch: {
    item: {
      handler: function (value) {
        if (value === null) {
          return;
        }

        this.modelData = { ...this.modelData, ...value };
      },
      immediate: true,
    },
  },
};
</script>
