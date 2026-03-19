/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage data-component-id="UsersPreviewPage">
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('Users', context)"
          :to="{ name: 'staff-table' }"
          forceIconText
        >
          <template #icon>
            <AccountMultiple />
          </template>
        </NcBreadcrumb>
        <NcBreadcrumb :name="pageTitle" />
      </NcBreadcrumbs>

      <template #actions>
        <NcActions :force-name="true">
          <NcActionButton :to="{ name: 'staff-edit', params: { slug } }">
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

import AccountMultiple from "vue-material-design-icons/AccountMultiple.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";

import {
  VPage,
  VPageLayout,
  VPageContent,
  VPagePadding,
  EntityPreview,
} from "@/widgets";

import { VToolbar } from "@/shared/components";

import { getDataToViewEntity } from "@/entities/common/api";
import { fetchUserPublicDataBySlug } from "@/entities/users/api";

import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { entityPreviewMixin } from "@/shared/lib/mixins/entityPreviewMixin";

import { redirectNotFoundPage } from "@/shared/lib/helpers/navigation";

import { MAP_ENTITY_SOURCE } from "@/shared/lib/constants/entity";

export default {
  name: "UsersPreviewPage",
  mixins: [contextualTranslationsMixin, entityPreviewMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    AccountMultiple,
    Pencil,
    VPage,
    VPageLayout,
    VPageContent,
    VPagePadding,
    VToolbar,
    EntityPreview,
  },
  data: () => ({
    source: MAP_ENTITY_SOURCE["user"],
    isLoading: false,
    publicData: null,
    entityData: null,
    context: "admin/users",
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
        return (
          this.publicData.full_name +
          (this.publicData.pname
            ? `, ${this.publicData.pname.toLowerCase()}`
            : "")
        );
      }

      return String(this.slug);
    },
  },
  methods: {
    handleClickBlock(key) {
      switch (key) {
        case "main": {
          this.$router.push({ name: "staff-edit", slug: this.slug });

          break;
        }
        case "directions": {
          this.$router.push({ name: "staff-directions", slug: this.slug });

          break;
        }
        case "roles": {
          this.$router.push({ name: "staff-roles", slug: this.slug });

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
          fetchUserPublicDataBySlug({ slug: this.slug }),
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
        console.error(e);

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
