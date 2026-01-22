/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage data-id="TeamsPreviewPage">
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('Teams')"
          :to="{ name: 'team-table' }"
          forceIconText
        >
          <template #icon>
            <Flag />
          </template>
        </NcBreadcrumb>
        <NcBreadcrumb :name="pageTitle" />
      </NcBreadcrumbs>

      <template #actions>
        <NcActions :force-name="true">
          <NcActionButton :to="{ name: 'team-edit', params: { slug } }">
            <template #icon>
              <Pencil />
            </template>
            {{ contextTranslate("Edit") }}
          </NcActionButton>
        </NcActions>
      </template>
    </VToolbar>
    <VPageLayout>
      <VPageContent>
        <VPagePadding>
          <EntityPreview
            v-if="isLoading === false"
            :model-value="entityData"
            :enable-appearance-preview="true"
            @onClickBlock="handleClickBlock"
            @onFileUpload="handleFileUpload"
            @onColorSubmit="handleColorSubmit"
          />
        </VPagePadding>
      </VPageContent>
    </VPageLayout>
  </VPage>
</template>

<script>
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcActions,
  NcActionButton,
} from "@nextcloud/vue";

import Flag from "vue-material-design-icons/Flag.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";

import { getDataToViewEntity } from "@/entities/common/api";
import { fetchTeamPublicDataBySlug } from "@/entities/teams/api";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
  EntityPreview,
} from "@/widgets";
import { VToolbar } from "@/shared/components";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { entityPreviewMixin } from "@/shared/lib/mixins/entityPreviewMixin";

import { redirectNotFoundPage } from "@/shared/lib/helpers/navigation";

import { MAP_ENTITY_SOURCE } from "@/shared/lib/constants/entity";

export default {
  name: "TeamsPreviewPage",
  mixins: [contextualTranslationsMixin, entityPreviewMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    Flag,
    Pencil,
    VPage,
    VPageLayout,
    VPageContent,
    VPagePadding,
    VToolbar,
    EntityPreview,
  },
  data: () => ({
    source: MAP_ENTITY_SOURCE["team"],
    isLoading: false,
    publicData: null,
    entityData: null,
  }),
  computed: {
    slug() {
      return this.$route.params.slug;
    },
    pageTitle() {
      if (this.isLoading === true) {
        return "";
      }

      if (this.publicData) {
        return this.publicData.name;
      }

      return String(this.slug);
    },
  },
  methods: {
    handleClickBlock(key) {
      switch (key) {
        case "main": {
          this.$router.push({ name: "team-edit", slug: this.slug });

          break;
        }
        case "directions": {
          this.$router.push({ name: "team-directions", slug: this.slug });

          break;
        }
        case "projects": {
          this.$router.push({ name: "team-projects", slug: this.slug });

          break;
        }
        case "staff": {
          this.$router.push({ name: "team-staff", slug: this.slug });

          break;
        }
        default: {
          console.log("No action found");
        }
      }
    },
    async handleFetchData() {
      this.isLoading = true;

      try {
        const promises = [
          fetchTeamPublicDataBySlug({ slug: this.slug }),
          getDataToViewEntity({
            slug: this.slug,
            source: this.source,
          }),
        ];

        const [{ data: publicData }, { data: entityData }] = await Promise.all(
          promises
        );

        this.publicData = publicData;
        this.entityData = entityData.data;
      } catch (e) {
        console.log(e);

        redirectNotFoundPage(this.$router);
      } finally {
        this.isLoading = false;
      }
    },
    init() {
      this.handleFetchData();
    },
  },
  mounted() {
    this.init();
  },
};
</script>
