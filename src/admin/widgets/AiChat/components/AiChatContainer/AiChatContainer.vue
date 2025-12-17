/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <div
    data-id="AiChatContainer"
    :class="{
      'fixed z-1000 w-(--ai-chat-width)': true,
      'flex flex-col bg-(--color-main-background) overflow-hidden': true,
      'rounded-(--body-container-radius) transition-all duration-200': true,
      'md:border-l border-(--color-border)': true,
      'bottom-(--body-container-margin) h-[calc(100%-var(--header-height)-var(--body-container-margin))]':
        isSidebarMode === true,
      'bottom-8 h-[500px] border border-(--color-border) shadow-xl':
        isSidebarMode === false,
      '-right-(--ai-chat-width)': isChatActive === false,
      'right-(--body-container-margin)':
        isSidebarMode === true && isChatActive === true,
      'right-8': isSidebarMode === false && isChatActive === true,
    }"
  >
    <div class="flex justify-between items-center min-h-[52px] px-4 py-2">
      <CommentQuestionOutline />
      <div class="flex items-center gap-4">
        <NcButton
          :aria-label="
            contextTranslate(isSidebarMode ? 'Collapse' : 'Expand', context)
          "
          type="tertiary"
          @click="toggleAiChatSidebarMode"
        >
          <template #icon>
            <ArrowCollapse v-if="isSidebarMode === true" :size="20" />
            <ArrowExpand v-else :size="20" />
          </template>
        </NcButton>
        <NcActions force-menu>
          <template #icon>
            <Cog :size="20" />
          </template>
          <NcActionButton
            :aria-label="contextTranslate('Clear history', context)"
            @click="() => handleResetHistory()"
          >
            <template #icon>
              <BackspaceOutline />
            </template>
            {{ contextTranslate("Clear history", context) }}
          </NcActionButton>
        </NcActions>
        <NcButton
          :aria-label="contextTranslate('Close', context)"
          type="tertiary"
          @click="toggleAiChat"
        >
          <template #icon>
            <Close :size="20" />
          </template>
        </NcButton>
      </div>
    </div>
    <div
      ref="messagesArea"
      class="relative flex-1 p-4 overflow-auto border-t border-(--color-border)"
    >
      <VLoader v-if="isInitLoading === true" absolute />
      <div
        v-for="(item, index) in messages"
        :key="`${index}_${item.id}`"
        :class="[
          'flex flex-col gap-1 mb-4',
          item.sender === 'user' ? 'items-end' : 'items-top',
        ]"
      >
        <div
          :class="[
            'max-w-[90%] py-2 px-4 rounded-(--border-radius-large) whitespace-pre-wrap',
            item.sender === 'user'
              ? 'bg-(--color-success) rounded-ee-[0px]'
              : 'bg-(--color-background-dark) rounded-es-[0px]',
          ]"
        >
          <!--
          -->{{ item.text
          }}<!--
      -->
        </div>
        <div class="text-sm text-(--color-text-maxcontrast)">
          {{ item.timestamp }}
        </div>
      </div>
    </div>
    <div
      v-show="isInitLoading === false && isErrorState === false"
      class="relative pt-3 pb-4 p-4 border-t border-(--color-border)"
    >
      <VLoaderDots v-if="isLoading" class="absolute top-8 left-8 z-100" />
      <VTextArea
        ref="textarea"
        v-model="userMessage"
        :placeholder="`${contextTranslate('Enter your message', context)}...`"
        :disabled="isLoading"
        :input-class="{
          'w-full min-w-full max-w-full transition-all duration-200': true,
          'min-h-[128px] max-h-[128px]': isSidebarMode === true,
          'min-h-[80px] max-h-[80px]': isSidebarMode === false,
        }"
        @on-keydown-enter="handleSubmitMessage"
        @on-keydown-enter-shift="handleAddNewLine"
      />
      <NcButton
        :aria-label="contextTranslate('Submit', context)"
        :disabled="!userMessage.trim() || isLoading"
        type="primary"
        wide
        @click="handleSubmitMessage"
      >
        {{ contextTranslate("Submit", context) }}
      </NcButton>
    </div>
  </div>
</template>

<script>
import { NcButton, NcActions, NcActionButton } from "@nextcloud/vue";
import { format } from "date-fns";
import { mapActions, mapState } from "pinia";

import CommentQuestionOutline from "vue-material-design-icons/CommentQuestionOutline.vue";
import ArrowCollapse from "vue-material-design-icons/ArrowCollapse.vue";
import ArrowExpand from "vue-material-design-icons/ArrowExpand.vue";
import Close from "vue-material-design-icons/Close.vue";
import Cog from "vue-material-design-icons/Cog.vue";
import BackspaceOutline from "vue-material-design-icons/BackspaceOutline.vue";

import VTextArea from "@/common/shared/components/VTextArea/VTextArea.vue";
import VLoader from "@/common/shared/components/VLoader/VLoader.vue";
import VLoaderDots from "@/common/shared/components/VLoaderDots/VLoaderDots.vue";

import { useAiChatStore } from "@/admin/app/store/aiChat";

import {
  submitMessage,
  fetchToken,
  healthCheck,
  fetchHistory,
  resetHistory,
} from "@/admin/entities/aiChat/api";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "AiChatContainer",
  mixins: [contextualTranslationsMixin],
  components: {
    NcButton,
    NcActions,
    NcActionButton,
    CommentQuestionOutline,
    ArrowCollapse,
    ArrowExpand,
    Close,
    Cog,
    BackspaceOutline,
    VTextArea,
    VLoader,
    VLoaderDots,
  },
  data() {
    return {
      isInitLoading: true,
      isInitiated: false,
      isErrorState: false,
      isLoading: false,
      userMessage: "",
      messages: [],
    };
  },
  computed: {
    ...mapState(useAiChatStore, [
      "isChatActive",
      "isSidebarMode",
      "isHealthChecked",
      "token",
    ]),
  },
  methods: {
    ...mapActions(useAiChatStore, [
      "toggleAiChat",
      "toggleAiChatSidebarMode",
      "setHealtCheck",
      "setToken",
    ]),
    getCurrentTime(value) {
      const date = value || new Date();

      return format(date, "dd.MM.yyyy HH:mm");
    },
    focusTextarea() {
      this.$nextTick(() => {
        this.$refs.textarea.$refs.input.focus();
      });
    },
    scrollToBottom() {
      this.$nextTick(() => {
        const container = this.$refs.messagesArea;

        if (!container) {
          return;
        }

        container.scrollTop = container.scrollHeight;
      });
    },
    handleAddNewLine() {
      this.userMessage += "\n";
    },
    handleAddNewMessage({ id, sender, text, timestamp }) {
      const messageTimestamp = this.getCurrentTime(timestamp);
      const messageId = id || messageTimestamp;

      this.messages.push({
        id: messageId,
        sender,
        text,
        timestamp: messageTimestamp,
      });
    },
    handleAddWelcomeMessage() {
      this.handleAddNewMessage({
        sender: "bot",
        text: this.contextTranslate("aiChatWelcomeMessage"),
      });
    },
    handleAddErrorMessage() {
      this.handleAddNewMessage({
        sender: "bot",
        text: this.contextTranslate("aiChatErrorMessage"),
      });
    },
    handleSetErrorState() {
      this.isErrorState = true;

      this.handleAddErrorMessage();
    },
    async handleFetchToken() {
      try {
        const data = await fetchToken();

        if (data?.ocs?.meta?.status === 'failure') {
          throw new Error(data.ocs.meta.message);
        }

        this.setToken(data.token);
      } catch (e) {
        console.log(e);

        throw new Error(e);
      }
    },
    async handleHealthCheck() {
      try {
        const { status } = await healthCheck();

        if (status !== "ok") {
          throw new Error(e);
        }
      } catch (e) {
        console.log(e);

        throw new Error(e);
      } finally {
        this.setHealtCheck(true);
      }
    },
    async handleFetchHistory() {
      try {
        const { data } = await fetchHistory();

        if (data?.length > 0) {
          this.messages = [...data];
        }
      } catch (e) {
        console.log(e);

        throw new Error(e);
      }
    },
    async handleSubmitMessage() {
      try {
        const message = this.userMessage.trim();

        if (!message || this.isLoading === true) {
          return;
        }

        this.handleAddNewMessage({
          sender: "user",
          text: message,
        });

        this.userMessage = "";
        this.isLoading = true;

        const [_userMessage, botMessage] = await submitMessage(message);

        this.handleAddNewMessage(botMessage);

        if (this.isChatActive === true) {
          this.focusTextarea();
        }
      } catch (e) {
        console.log(e);
      } finally {
        this.isLoading = false;
      }
    },
    async handleResetHistory() {
      try {
        this.messages = [];

        if (this.isErrorState === true) {
          this.handleAddErrorMessage();
        } else {
          this.handleAddWelcomeMessage();
        }

        await resetHistory();
      } catch (e) {
        console.log(e);
      }
    },
    async init() {
      try {
        if (this.isErrorState === true) {
          return;
        }

        if (this.isInitiated === true) {
          this.focusTextarea();

          return;
        }

        await this.handleFetchToken();
        await this.handleHealthCheck();
        await this.handleFetchHistory();

        if (this.messages.length === 0) {
          this.handleAddWelcomeMessage();
        }

        this.focusTextarea();
      } catch (e) {
        console.log(e);

        this.handleSetErrorState();
      } finally {
        this.isInitLoading = false;
        this.isInitiated = true;
      }
    },
  },
  watch: {
    isChatActive(value) {
      if (value === false) {
        return;
      }

      this.init();
    },
    messages: {
      handler() {
        this.$nextTick(() => {
          this.scrollToBottom();
        });
      },
      deep: true,
    },
  },
};
</script>
