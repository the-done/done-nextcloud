<template>
  <VPage class="overflow-hidden">
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('Profile', context)"
          :to="{ name: 'profile' }"
          forceIconText
        >
          <template #icon>
            <Account />
          </template>
        </NcBreadcrumb>
      </NcBreadcrumbs>
    </VToolbar>
    <VPageLayout>
      <VPageContent class="relative">
        <VPagePadding>
          <VLoader v-if="isInitLoading === true" absolute />
          <EntityPreview
            v-else
            :model-value="entityData"
            :enable-appearance-preview="true"
            :click-block-enabled="false"
            @onFileUpload="handleFileUpload"
            @onColorSubmit="handleColorSubmit"
          />
        </VPagePadding>
      </VPageContent>
    </VPageLayout>
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb } from "@nextcloud/vue";
import Account from "vue-material-design-icons/Account.vue";
import { mapWritableState } from "pinia";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
} from "@/common/widgets/VPage";
import { EntityPreview } from "@/admin/widgets/EntityPreview";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import VLoader from "@/common/shared/components/VLoader/VLoader.vue";

import { useProfileStore } from "@/admin/app/store/profile";

import { fetchProfile } from "@/admin/entities/profile/api";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";
import { entityPreviewMixin } from "@/admin/shared/lib/mixins/entityPreviewMixin";

import { MAP_ENTITY_SOURCE } from "@/common/shared/lib/constants";

export default {
  name: "ProfilePage",
  mixins: [contextualTranslationsMixin, entityPreviewMixin],
  components: {
    VPage,
    VPageLayout,
    VPageContent,
    VPagePadding,
    VToolbar,
    VLoader,
    NcBreadcrumbs,
    NcBreadcrumb,
    EntityPreview,
    Account,
  },
  data: () => ({
    source: MAP_ENTITY_SOURCE["user"],
    isInitLoading: false,
    context: "admin/users",
  }),
  computed: {
    ...mapWritableState(useProfileStore, ["entityData", "slug", "isFetched"]),
  },
  methods: {
    async handleFetchData() {
      try {
        const { data, slug } = await fetchProfile();

        this.entityData = data;
        this.slug = slug;
        this.isFetched = true;
      } catch (e) {
        console.log(e);
      } finally {
        this.isInitLoading = false;
      }
    },
    init() {
      if (this.isFetched === true) {
        return;
      }

      this.isInitLoading = true;
      this.handleFetchData();
    },
  },
  mounted() {
    this.init();
  },
};
</script>
