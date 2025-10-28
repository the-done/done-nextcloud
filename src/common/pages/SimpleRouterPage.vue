<template>
  <RouterView v-bind="$props" />
</template>

<script>
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

export default {
  name: "SimpleRouterPage",
  mixins: [contextualTranslationsMixin],
  props: {
    additionalProps: {
      type: Object,
      default: () => ({}),
    },
  },
  methods: {
    t,
  },
};
</script>
