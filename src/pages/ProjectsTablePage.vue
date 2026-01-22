/** * SPDX-FileCopyrightText: 2025 The Done contributors *
SPDX-License-Identifier: MIT */

<template>
  <VPage>
    <DynamicTable
      v-model="tableData"
      :all-columns-ordering.sync="columnsWithControls"
      :loading="tableIsLoading"
      :source="source"
      :settings="settings"
      @on-fetch="handleFetchData"
    >
      <template #toolbar-left>
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
        </NcBreadcrumbs>
      </template>
      <template #toolbar-controls>
        <NcActions :force-name="true">
          <NcActionButton
            v-if="getCommonPermission('canCreateProjects')"
            :aria-label="contextTranslate('Create', context)"
            :to="{ name: 'project-new' }"
          >
            <template #icon>
              <Plus />
            </template>
            {{ contextTranslate("Create", context) }}
          </NcActionButton>
        </NcActions>
        <ExportButton :source="source" context-name="admin/projects" />
      </template>
      <template #controls="{ row: { slug, slug_type } }">
        <NcActions :inline="1">
          <NcActionButton
            v-if="
              getCommonPermission('canReadProjectsList') === true ||
              getCommonPermission('canViewProjectsListRelated') === true
            "
            :aria-label="contextTranslate('Preview', context)"
            @click="() => handleClickPreview(slug)"
          >
            <template #icon>
              <EyeOutline />
            </template>
          </NcActionButton>
          <NcActionButton
            v-if="getCommonPermission('canEditProjects')"
            :aria-label="contextTranslate('Edit', context)"
            @click="() => handleClickEdit(slug)"
          >
            <template #icon>
              <Pencil />
              {{ contextTranslate("Edit", context) }}
            </template>
          </NcActionButton>
          <NcActionButton
            v-if="getCommonPermission('canDeleteProjects')"
            :force-name="true"
            :aria-label="contextTranslate('Delete', context)"
            @click="() => handleDelete({ slug, slug_type })"
          >
            <template #icon>
              <TrashCanOutline />
              {{ contextTranslate("Delete", context) }}
            </template>
          </NcActionButton>
        </NcActions>
      </template>
      <template #emptyContentActions>
        <NcButton :to="{ name: 'project-new' }" class="mt-2">
          <template #icon> <Plus /> </template>
          {{ contextTranslate("Add project", context) }}
        </NcButton>
      </template>
    </DynamicTable>
  </VPage>
</template>

<script>
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcActions,
  NcActionButton,
  NcButton,
} from "@nextcloud/vue";
import { mapState } from "pinia";

import BookCog from "vue-material-design-icons/BookCog.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import EyeOutline from "vue-material-design-icons/EyeOutline.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import { VPage, DynamicTable, ExportButton } from "@/widgets";

import { usePermissionStore } from "@/app/store/permission";

import { fetchProjectsTableData, deleteProject } from "@/entities/projects/api";

import { dynamicTableMixin } from "@/shared/lib/mixins/dynamicTableMixin";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { MAP_DYNAMIC_TABLE_SOURCES } from "@/entities/dynamicTables/constants";

export default {
  name: "ProjectsTablePage",
  mixins: [dynamicTableMixin, contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    NcButton,
    BookCog,
    Plus,
    EyeOutline,
    Pencil,
    TrashCanOutline,
    VPage,
    DynamicTable,
    ExportButton,
  },
  data: () => ({
    context: "admin/projects",
  }),
  computed: {
    ...mapState(usePermissionStore, ["getCommonPermission"]),
    source() {
      return MAP_DYNAMIC_TABLE_SOURCES["project"];
    },
    columnsWithControls: {
      get() {
        return [
          {
            title: "",
            key: "controls",
            draggable: false,
            sortable: false,
            filterable: false,
            hideable: false,
            customClass: "w-[100px]",
          },
          ...this.allColumnsOrdering,
        ];
      },
      set(value) {
        this.allColumnsOrdering = value.filter(
          (item) => item.key !== "controls",
        );
      },
    },
  },
  methods: {
    handleClickPreview(slug) {
      this.$router.push({ name: "project-preview", params: { slug } });
    },
    handleClickEdit(slug) {
      this.$router.push({ name: "project-edit", params: { slug } });
    },
    async handleFetchData() {
      try {
        this.tableIsLoading = true;

        const { data } = await fetchProjectsTableData();

        this.initDynamicTable(data);
      } catch (e) {
        console.log(e);
      } finally {
        this.tableIsLoading = false;
      }
    },
    async handleDelete({ slug, slug_type }) {
      if (
        !confirm(
          this.contextTranslate("Are you sure you want to delete the record?"),
        )
      ) {
        return;
      }

      try {
        await deleteProject({ slug, slug_type });

        this.handleFetchData();
      } catch (e) {
        console.log(e);
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
