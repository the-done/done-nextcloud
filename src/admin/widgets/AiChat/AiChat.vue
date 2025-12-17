<template>
  <div data-id="AiChat">
    <div
      :class="{
        'fixed bottom-8 z-2000 transition-right duration-200': true,
        'right-8': isChatActive === false,
        '-right-[56px]': isChatActive === true,
      }"
    >
      <NcButton
        :aria-label="contextTranslate('Chat with AI', context)"
        type="primary"
        size="large"
        class="rounded-full!"
        @click="toggleAiChat"
      >
        <template #icon>
          <CommentQuestionOutline :size="24" />
        </template>
      </NcButton>
    </div>

    <AiChatContainer />
  </div>
</template>

<script>
import { NcButton } from "@nextcloud/vue";
import { mapActions, mapState } from "pinia";

import CommentQuestionOutline from "vue-material-design-icons/CommentQuestionOutline.vue";

import { AiChatContainer } from "./components/AiChatContainer";

import { useAiChatStore } from "@/admin/app/store/aiChat";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "AiChat",
  mixins: [contextualTranslationsMixin],
  components: {
    NcButton,
    CommentQuestionOutline,
    AiChatContainer,
  },
  computed: {
    ...mapState(useAiChatStore, ["isChatActive"]),
  },
  methods: {
    ...mapActions(useAiChatStore, ["toggleAiChat"]),
  },
};
</script>
