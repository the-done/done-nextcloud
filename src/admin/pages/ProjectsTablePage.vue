<template>
  <VPage>
    <DynamicTable
      v-model="tableData"
      :all-columns-ordering.sync="columnsWithControls"
      :loading="tableIsLoading"
      :source="source"
      :settings="settings"
      :empty-content-description="
        contextTranslate('No data found for your projects', context)
      "
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
            v-if="getCommonPermission('canReadProjectsList') === true"
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
        <NcButton :to="{ name: 'project-new' }" type="primary" class="v-mt-1">
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
import { t } from "@nextcloud/l10n";
import { mapState } from "pinia";

import BookCog from "vue-material-design-icons/BookCog.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import EyeOutline from "vue-material-design-icons/EyeOutline.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import { VPage } from "@/common/widgets/VPage";
import { DynamicTable } from "@/admin/widgets/DynamicTable";
import ExportButton from "@/admin/widgets/ExportButton.vue";

import {
  fetchProjectsTableData,
  deleteProject,
} from "@/common/entities/projects/api";

import { usePermissionStore } from "@/admin/app/store/permission";

import { dynamicTableMixin } from "@/common/shared/mixins/dynamicTableMixin";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { MAP_DYNAMIC_TABLE_SOURCES } from "@/admin/entities/dynamicTables/constants";

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
          (item) => item.key !== "controls"
        );
      },
    },
  },
  methods: {
    t,
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
      if (!confirm(t("done", "Do you really want to delete the project?"))) {
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
