<template>
  <NcActions
    :menu-name="menuName"
    :class="[token && 'token']"
    :manual-open="true"
    :open="isOpened"
    @click="handleOpenMenu"
    @close="handleDropMenu"
  >
    <template #icon>
      <Plus :size="20" />
    </template>
    <template v-if="menuState === 'selectTokenType'">
      <template v-for="item in menu">
        <NcActionButton
          v-if="item.visible !== false"
          :key="item.key"
          :disabled="item.disabled === true"
          @click="item.onClick()"
        >
          <template #icon>
            <component :is="item.icon" />
          </template>
          {{ item.label }}
        </NcActionButton>
      </template>
    </template>
    <template v-if="menuState === 'selectField'">
      <NcActionButton @click="handleResetMenu">
        <template #icon>
          <ChevronLeft :size="20" />
        </template>
        {{ contextTranslate("Back", context) }}
      </NcActionButton>
      <NcActionButton
        v-for="item in contractFieldOptions"
        :key="item.value"
        @click="handleSelectToken(item, 'field')"
      >
        {{ item.label }}
      </NcActionButton>
    </template>
    <template v-if="menuState === 'selectParameter'">
      <NcActionButton @click="handleResetMenu">
        <template #icon>
          <ChevronLeft :size="20" />
        </template>
        {{ contextTranslate("Back", context) }}
      </NcActionButton>
      <NcActionButton
        v-for="item in parameterMenu"
        :key="item.key"
        :disabled="item.disabled === true"
        @click="item.onClick()"
      >
        {{ item.label }}
      </NcActionButton>
    </template>
    <template v-if="menuState === 'selectNumber'">
      <NcActionButton @click="handleResetMenu">
        <template #icon>
          <ChevronLeft :size="20" />
        </template>
        {{ contextTranslate("Back", context) }}
      </NcActionButton>
      <NcActionInput
        v-model="numberInputValue"
        :label="this.contextTranslate('Number')"
        type="number"
        @submit="(e) => handleSelectToken(e, 'number')"
      />
    </template>
    <template v-if="menuState === 'selectOperator'">
      <NcActionButton @click="handleResetMenu">
        <template #icon>
          <ChevronLeft :size="20" />
        </template>
        {{ contextTranslate("Back", context) }}
      </NcActionButton>
      <NcActionButton
        v-for="item in operatorMenu"
        :key="item.key"
        :disabled="item.disabled === true"
        @click="item.onClick()"
      >
        {{ item.label }}
      </NcActionButton>
    </template>
  </NcActions>
</template>

<script>
import { NcActions, NcActionButton, NcActionInput } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";

import Plus from "vue-material-design-icons/Plus.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";
import ChevronLeft from "vue-material-design-icons/ChevronLeft.vue";
import AlphaFCircleOutline from "vue-material-design-icons/AlphaFCircleOutline.vue";
import AlphaPCircleOutline from "vue-material-design-icons/AlphaPCircleOutline.vue";
import AlphaNCircleOutline from "vue-material-design-icons/AlphaNCircleOutline.vue";
import PlusMinus from "vue-material-design-icons/PlusMinus.vue";
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import {
  getFieldName,
  getParameterName,
} from "@/shared/lib/helpers/contractFormula";

import { contractFieldOptions } from "@/shared/lib/constants/contractFormula";

export default {
  name: "ContractParameterFormulaToken",
  mixins: [contextualTranslationsMixin],
  components: {
    NcActions,
    NcActionButton,
    NcActionInput,
    Plus,
    Pencil,
    ChevronLeft,
    AlphaFCircleOutline,
    AlphaPCircleOutline,
    AlphaNCircleOutline,
    PlusMinus,
    TrashCanOutline,
  },
  props: {
    value: {
      type: Array,
      default: () => [],
    },
    index: {
      type: Number,
      default: 0,
    },
    token: {
      type: Object,
      default: null,
    },
    parameterOptions: {
      type: Array,
      default: () => [],
    },
  },
  emits: ["input", "on-add-token", "on-change-token", "on-delete-token"],
  data() {
    return {
      isOpened: false,
      numberInputValue: "",
      menuState: "selectTokenType",
      contractFieldOptions,
    };
  },
  computed: {
    lastToken() {
      return this.value?.length > 0 ? this.value[this.value.length - 1] : null;
    },
    isEdit() {
      return Boolean(this.token);
    },
    prevToken() {
      return this.value[this.index - 1];
    },
    parameterMenu() {
      return this.parameterOptions.map((item) => ({
        key: item.slug,
        label: item.contract_parameter_name,
        disabled: !item.value,
        onClick: () => this.handleSelectToken(item, "parameter"),
      }));
    },
    menu() {
      const isLastToken = this.value && this.value.length - 1 === this.index;
      const isNextTokenOperator =
        this.value?.length > 0 && this.lastToken?.type !== "operator";

      const isFieldMenuDisabled =
        (this.isEdit === false && isNextTokenOperator === true) ||
        (this.isEdit === true && this.token.type === "operator");

      const isOperatorMenuDisabled =
        this.isEdit === true && this.token.type !== "operator";

      const isDeleteButtonVisible =
        this.isEdit === true && isLastToken === true;

      return [
        {
          key: "field",
          label: this.contextTranslate("Contract field"),
          icon: AlphaFCircleOutline,
          disabled: isFieldMenuDisabled,
          onClick: () => this.handleSetMenuState("selectField"),
        },
        {
          key: "parameter",
          label: this.contextTranslate("Contract parameter"),
          icon: AlphaPCircleOutline,
          disabled: isFieldMenuDisabled || this.parameterMenu?.length === 0,
          onClick: () => this.handleSetMenuState("selectParameter"),
        },
        {
          key: "number",
          label: this.contextTranslate("Number"),
          icon: AlphaNCircleOutline,
          disabled: isFieldMenuDisabled,
          onClick: () => this.handleSetMenuState("selectNumber"),
        },
        {
          key: "operator",
          label: this.contextTranslate("Operator"),
          icon: PlusMinus,
          disabled: isOperatorMenuDisabled,
          onClick: () => this.handleSetMenuState("selectOperator"),
        },
        {
          key: "delete",
          label: this.contextTranslate("Delete"),
          icon: TrashCanOutline,
          visible: isDeleteButtonVisible,
          onClick: () => this.handleDeleteToken(),
        },
      ];
    },
    operatorMenu() {
      const isOpearatorDisabled =
        this.value?.length === 0 ||
        (this.prevToken &&
          this.prevToken.type === "operator" &&
          ["+", "-", "*", "/", "("].includes(this.prevToken.value));

      const isLeftParenthesisDisabled =
        (this.prevToken && this.prevToken.type !== "operator") ||
        (this.prevToken &&
          this.prevToken.type === "operator" &&
          this.prevToken.value === ")");

      const isRightParenthesisDisabled =
        this.value?.length === 0 ||
        (this.prevToken &&
          this.prevToken.type === "operator" &&
          ["+", "-", "*", "/", "("].includes(this.prevToken.value)) ||
        this.value?.some(
          (token) => token.type === "operator" && token.value === "(",
        ) === false;

      return [
        {
          key: "plus",
          label: "+",
          disabled: isOpearatorDisabled,
          onClick: () => this.handleSelectToken("+", "operator"),
        },
        {
          key: "minus",
          label: "-",
          disabled: isOpearatorDisabled,
          onClick: () => this.handleSelectToken("-", "operator"),
        },
        {
          key: "multiply",
          label: "*",
          disabled: isOpearatorDisabled,
          onClick: () => this.handleSelectToken("*", "operator"),
        },
        {
          key: "divide",
          label: "/",
          disabled: isOpearatorDisabled,
          onClick: () => this.handleSelectToken("/", "operator"),
        },
        {
          key: "leftParenthesis",
          label: "(",
          disabled: isLeftParenthesisDisabled,
          onClick: () => this.handleSelectToken("(", "operator"),
        },
        {
          key: "rightParenthesis",
          label: ")",
          disabled: isRightParenthesisDisabled,
          onClick: () => this.handleSelectToken(")", "operator"),
        },
      ];
    },
    menuName() {
      if (!this.token) {
        return;
      }

      switch (this.token.type) {
        case "field":
          return getFieldName(this.token.value);
        case "parameter":
          return getParameterName(this.token.id, this.parameterOptions);
        default:
          return this.token.value;
      }
    },
  },
  methods: {
    handleOpenMenu() {
      this.isOpened = true;
    },
    handleCloseMenu() {
      this.isOpened = false;
    },
    handleSetMenuState(value) {
      this.menuState = value;
    },
    handleDropNumberInput() {
      this.numberInputValue = "";
    },
    handleResetMenu() {
      this.handleSetMenuState("selectTokenType");
    },
    handleDropMenu() {
      this.handleCloseMenu();
      this.handleDropNumberInput();
      this.handleResetMenu();
    },
    handleAddToken(token) {
      this.$emit("on-add-token", token);

      this.handleDropMenu();
    },
    handleSelectToken(item, type) {
      let token = {};

      if (type === "number") {
        token = {
          type,
          value: this.numberInputValue,
        };
      } else if (type === "operator") {
        token = {
          type,
          value: item,
        };
      } else {
        token = {
          type,
          ...item,
        };
      }

      if (!this.token) {
        this.handleAddToken(token);

        return;
      }

      this.$emit("on-change-token", { index: this.index, token });

      this.handleDropMenu();
    },
    handleDeleteToken() {
      this.$emit("on-delete-token", this.index);
    },
  },
};
</script>

<style scoped>
.token:deep(.button-vue) {
  padding-right: 4px;
}

.token:deep(.button-vue__icon) {
  display: none;
}
</style>
