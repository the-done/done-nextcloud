/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage data-id="ProjectsPreviewPage">
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('Projects', context)"
          :to="{ name: 'project-table' }"
          forceIconText
        >
          <template #icon>
            <BookCog />
          </template>
        </NcBreadcrumb>
        <NcBreadcrumb :name="pageTitle" />
      </NcBreadcrumbs>

      <template #actions>
        <NcActions :force-name="true">
          <NcActionButton :to="{ name: 'project-edit', params: { slug } }">
            <template #icon>
              <Pencil />
            </template>
            {{ contextTranslate("Edit", context) }}
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
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import BookCog from "vue-material-design-icons/BookCog.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";

import {
  getDataToViewEntity,
  saveEntityImage,
} from "@/admin/entities/common/api";
import { fetchProjectPublicDataBySlug } from "@/common/entities/projects/api";

import { EntityPreview } from "@/admin/widgets/EntityPreview";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
} from "@/common/widgets/VPage";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";

import { MAP_ENTITY_SOURCE } from "@/common/shared/lib/constants";

export default {
  name: "ProjectsPreviewPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    BookCog,
    Pencil,
    VPage,
    VPageLayout,
    VPageContent,
    VPagePadding,
    VToolbar,
    EntityPreview,
  },
  data: () => ({
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
          this.$router.push({ name: "project-edit", slug: this.slug });

          break;
        }
        case "staff": {
          this.$router.push({ name: "project-staff", slug: this.slug });

          break;
        }
        case "appearance": {
          this.$router.push({ name: "project-edit", slug: this.slug }); // todo appearance editing

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
          fetchProjectPublicDataBySlug({ slug: this.slug }),
          getDataToViewEntity({
            slug: this.slug,
            source: MAP_ENTITY_SOURCE["project"],
          }),
        ];

        const [{ data: publicData }, { data: entityData }] = await Promise.all(
          promises
        );

        this.publicData = publicData;
        this.entityData = entityData.data;
      } catch (e) {
        console.log(e);
      } finally {
        this.isLoading = false;
      }
    },
    async handleFileUpload({ fieldName, image }) {
      try {
        await saveEntityImage({
          slug: this.slug,
          fieldName,
          image,
          source: MAP_ENTITY_SOURCE["project"],
        });

        this.handleFetchData();
      } catch (error) {
        console.error("Error uploading file:", error);
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
