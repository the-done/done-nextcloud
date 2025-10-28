<template>
  <div class="md:flex" data-component-id="TimeTrackingEditForm">
    <form ref="form" class="md:max-w-[425px]" @submit.prevent="handleSubmit">
      <div class="flex flex-col gap-2 p-4">
        <VDatePicker
          :value="value.date"
          :required="true"
          :error="errors.date"
          :label="contextTranslate('Record date', context)"
          @input="(value) => handleUpdateModelValue('date', value)"
        />
        <VDropdown
          :value="value.project_id"
          :options="projectOptions"
          :required="true"
          :error="errors.project_id"
          :label="contextTranslate('Project', context)"
          :placeholder="contextTranslate('Select project', context)"
          @input="(value) => handleUpdateModelValue('project_id', value)"
        />
        <VSwitch
          :label="contextTranslate('Downtime', context)"
          :value="value.is_downtime"
          @input="(value) => handleUpdateModelValue('is_downtime', value)"
        />
        <VTextField
          v-if="value.is_downtime === false"
          :value="value.task_link"
          :label="contextTranslate('Task link', context)"
          @input="(value) => handleUpdateModelValue('task_link', value)"
        />
        <VTextArea
          v-if="value.is_downtime === false"
          :value="value.description"
          :required="true"
          :error="errors.description"
          :label="contextTranslate('Description', context)"
          @input="(value) => handleUpdateModelValue('description', value)"
        />
        <VTextArea
          :value="value.comment"
          :label="contextTranslate('Comment', context)"
          :required="value.is_downtime === true"
          :error="errors.comment"
          @input="(value) => handleUpdateModelValue('comment', value)"
        />
        <div class="flex flex-col">
          <div class="flex items-end gap-4">
            <div class="w-full">
              <VTextField
                :value="value.hours"
                :invalid="errors.time.invalid"
                :required="true"
                :label="contextTranslate('Time spent', context)"
                :placeholder="contextTranslate('Hours', context)"
                @input="(value) => handleUpdateModelValue('hours', value)"
              />
            </div>
            <div class="w-full">
              <VTextField
                :value="value.minutes"
                :invalid="errors.time.invalid"
                :placeholder="contextTranslate('Minutes', context)"
                @input="(value) => handleUpdateModelValue('minutes', value)"
              />
            </div>
          </div>
          <div
            v-if="errors.time.invalid"
            class="block text-sm text-(--color-element-error)"
          >
            {{ errors.time.message }}
          </div>
        </div>
      </div>
      <div class="flex flex-col py-4 gap-4 md:hidden">
        <div class="overflow-auto">
          <div class="w-fit px-4">
            <TimeTrackingHoursPicker
              :value="value.hours"
              class="flex gap-2"
              @input="(value) => handleUpdateQuickValue('hours', value)"
            />
          </div>
        </div>
        <div class="px-4">
          <TimeTrackingMinutesPicker
            :value="value.minutes"
            class="flex gap-2"
            button-wrapper-class="w-full sm:w-auto"
            @input="(value) => handleUpdateQuickValue('minutes', value)"
          />
        </div>
      </div>
      <div class="flex justify-end gap-4 p-4 mt-4 md:mt-0">
        <NcButton v-if="isEdit === false" @click="handleSubmitAndContinue">
          {{ contextTranslate("Done and add more...", context) }}
        </NcButton>
        <NcButton type="primary" native-type="submit">
          {{ contextTranslate("Done", context) }}
        </NcButton>
      </div>
    </form>
    <div class="hidden gap-4 w-full max-w-[425px] p-4 pt-11 md:flex">
      <TimeTrackingHoursPicker
        :value="value.hours"
        class="flex flex-col gap-2 w-full"
        @input="(value) => handleUpdateQuickValue('hours', value)"
      />
      <TimeTrackingMinutesPicker
        :value="value.minutes"
        class="flex flex-col gap-2 w-full"
        @input="(value) => handleUpdateQuickValue('minutes', value)"
      />
    </div>
  </div>
</template>

<script>
import { t, n } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { NcButton } from "@nextcloud/vue";
import { required, minValue, helpers } from "vuelidate/lib/validators";
import { format } from "date-fns";

import { TimeTrackingHoursPicker } from "@/common/features/TimeTrackingHoursPicker";
import { TimeTrackingMinutesPicker } from "@/common/features/TimeTrackingMinutesPicker";

import VDatePicker from "@/common/shared/components/VDatePicker/VDatePicker.vue";
import VDropdown from "@/common/shared/components/VDropdown/VDropdown.vue";
import VTextArea from "@/common/shared/components/VTextArea/VTextArea.vue";
import VTextField from "@/common/shared/components/VTextField/VTextField.vue";
import VSwitch from "@/common/shared/components/VSwitch/VSwitch.vue";

import { declOfNum } from "@/common/shared/lib/helpers";

import { FIELD_IS_REQUIRED_ERROR } from "@/common/shared/lib/constants";
import { customTranslationsMixin } from "@/common/shared/mixins/customTranslationsMixin";

const REQUIRED_FIELDS = ["date", "project_id", "description", "comment"];
const DATE_FIELDS = ["date"];
const ID_FIELDS = ["project_id"];

const validateTimeRequired = helpers.withParams(
  { message: t("done", "One of the fields is required") },
  (_value, { $props }) => {
    const { hours, minutes } = $props.value;

    return hours !== "" || minutes !== "";
  }
);

const validateHours = helpers.withParams(
  { message: t("done", "Invalid number of hours") },
  (_value, { $props }) => {
    const { hours } = $props.value;

    if (hours !== "") {
      return minValue(0)(hours);
    }

    return true;
  }
);

const validateMinutes = helpers.withParams(
  { message: t("done", "Invalid number of minutes") },
  (_value, { $props }) => {
    const { minutes } = $props.value;

    if (minutes !== "") {
      return minValue(0)(minutes);
    }

    return true;
  }
);

export default {
  name: "TimeTrackingEditForm",
  emits: ["input", "onSubmit", "onSubmitAndContinue"],
  mixins: [customTranslationsMixin, contextualTranslationsMixin],
  components: {
    NcButton,
    TimeTrackingHoursPicker,
    TimeTrackingMinutesPicker,
    VDatePicker,
    VDropdown,
    VTextArea,
    VTextField,
    VSwitch,
  },
  props: {
    value: {
      type: Object,
      default: () => ({}),
    },
    projectOptions: {
      type: Array,
      default: () => [],
    },
    isEdit: {
      type: Boolean,
      default: false,
    },
  },
  data: () => ({
    context: "user/time-tracking",
  }),
  validations() {
    const time = {
      validateTimeRequired,
      validateHours,
      validateMinutes,
    };

    if (this.value.is_downtime === false) {
      return {
        value: {
          date: {
            required,
          },
          project_id: {
            required,
          },
          description: {
            required,
          },
        },
        time,
      };
    }

    return {
      value: {
        date: {
          required,
        },
        project_id: {
          required,
        },
        comment: {
          required,
        },
      },
      time,
    };
  },
  computed: {
    errors() {
      const getTimeError = () => {
        const { $invalid, $dirty, $params, validateHours, validateMinutes } =
          this.$v.time;

        if ($invalid === false || $dirty === false) {
          return {
            invalid: false,
          };
        }

        if (validateHours === false) {
          return { invalid: true, message: $params.validateHours.message };
        }

        if (validateMinutes === false) {
          return { invalid: true, message: $params.validateMinutes.message };
        }

        return {
          invalid: true,
          message: $params.validateTimeRequired.message,
        };
      };

      const requiredFields = REQUIRED_FIELDS.reduce((accum, key) => {
        const field = this.$v.value[key];

        if (
          field &&
          field.$invalid &&
          field.$dirty &&
          field.required === false
        ) {
          return {
            ...accum,
            [key]: t("done", FIELD_IS_REQUIRED_ERROR),
          };
        }

        return accum;
      }, {});

      return {
        ...requiredFields,
        time: getTimeError(),
      };
    },
  },
  methods: {
    t,
    n,
    declOfNum(...rest) {
      return declOfNum(...rest);
    },
    handleUpdateModelValue(key, value) {
      this.$emit("input", {
        ...this.value,
        [key]: value,
      });

      this.$v.value[key]?.$touch();

      if (["hours", "minutes"].includes(key) === true) {
        this.$v.time.$touch();
      }
    },
    handleUpdateQuickValue(key, value) {
      const currentValue = this.value[key];

      if (currentValue === value) {
        this.handleUpdateModelValue(key, "");

        return;
      }

      this.handleUpdateModelValue(key, value);
    },
    transformSubmitData(payload) {
      return Object.keys(payload).reduce((accum, key) => {
        const value = payload[key];

        if (key === "hours") {
          return accum;
        }

        if (key === "minutes") {
          return {
            ...accum,
            [key]: Number(value) + Number(payload.hours) * 60,
          };
        }

        if (["description", "task_link"].includes(key) === true) {
          if (payload.is_downtime === true) {
            return accum;
          }

          return {
            ...accum,
            [key]: value,
          };
        }

        if (ID_FIELDS.includes(key) === true) {
          // If value is an object, extract the id, otherwise use value as is
          const id = typeof value === 'object' && value !== null ? value.id : value;

          return {
            ...accum,
            [key]: id,
          };
        }

        if (DATE_FIELDS.includes(key) === true) {
          return {
            ...accum,
            [key]: format(value, "dd.MM.yyyy"),
          };
        }

        return {
          ...accum,
          [key]: value,
        };
      }, {});
    },
    async handleSubmit(isContinue = false) {
      this.$v.$touch();

      if (this.$v.$invalid === true) {
        return;
      }

      const payload = this.transformSubmitData(this.value);

      if (isContinue === true) {
        this.$emit("onSubmitAndContinue", { payload, $v: this.$v });

        return;
      }

      this.$emit("onSubmit", { payload, $v: this.$v });
    },
    handleSubmitAndContinue() {
      this.handleSubmit(true);
    },
  },
};
</script>
