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
      :empty-content-description="
        contextTranslate('We could not find data for your teams.')
      "
      @on-fetch="handleFetchData"
    >
      <template #toolbar-left>
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
        </NcBreadcrumbs>
      </template>
      <template #toolbar-controls>
        <NcActions :force-name="true">
          <NcActionButton
            v-if="getCommonPermission('canCreateTeams') === true"
            :to="{ name: 'team-new' }"
          >
            <template #icon>
              <Plus />
            </template>
            {{ contextTranslate("Create") }}
          </NcActionButton>
        </NcActions>
        <ExportButton :source="source" context-name="admin/teams" />
      </template>
      <template #controls="{ row: { slug, slug_type } }">
        <NcActions :inline="1">
          <NcActionButton
            v-if="getCommonPermission('canReadTeamsList') === true"
            :aria-label="contextTranslate('Preview')"
            @click="() => handleClickPreview(slug)"
          >
            <template #icon>
              <EyeOutline />
            </template>
          </NcActionButton>
          <NcActionButton
            v-if="getCommonPermission('canEditTeams') === true"
            :force-name="true"
            @click="() => handleClickEdit(slug)"
          >
            <template #icon>
              <Pencil />
              {{ contextTranslate("Edit") }}
            </template>
          </NcActionButton>
          <NcActionButton
            v-if="getCommonPermission('canDeleteTeams') === true"
            :force-name="true"
            @click="() => handleDelete({ slug, slug_type })"
          >
            <template #icon>
              <TrashCanOutline />
              {{ contextTranslate("Delete") }}
            </template>
          </NcActionButton>
        </NcActions>
      </template>
      <template #emptyContentActions>
        <NcButton :to="{ name: 'team-new' }" class="mt-2">
          <template #icon> <Plus /> </template>
          {{ contextTranslate("Add team") }}
        </NcButton>
      </template>
    </DynamicTable>
  </VPage>
</template>

<script>
import { mapState } from "pinia";
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcActions,
  NcActionButton,
  NcButton,
} from "@nextcloud/vue";

import Flag from "vue-material-design-icons/Flag.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";
import EyeOutline from "vue-material-design-icons/EyeOutline.vue";
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import { VPage, DynamicTable, ExportButton } from "@/widgets";

import { usePermissionStore } from "@/app/store/permission";

import { fetchTeamsTableData, deleteTeam } from "@/entities/teams/api";

import { dynamicTableMixin } from "@/shared/lib/mixins/dynamicTableMixin";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

import { MAP_DYNAMIC_TABLE_SOURCES } from "@/entities/dynamicTables/constants";

export default {
  name: "TeamsTablePage",
  mixins: [dynamicTableMixin, contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    NcButton,
    Flag,
    Plus,
    Pencil,
    EyeOutline,
    TrashCanOutline,
    VPage,
    DynamicTable,
    ExportButton,
  },
  data: () => ({
    isLoading: false,
    tableData: [],
  }),
  computed: {
    ...mapState(usePermissionStore, ["getCommonPermission"]),
    source() {
      return MAP_DYNAMIC_TABLE_SOURCES["team"];
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
    handleClickPreview(slug) {
      this.$router.push({ name: "team-preview", params: { slug } });
    },
    handleClickEdit(slug) {
      this.$router.push({ name: "team-edit", params: { slug } });
    },
    async handleFetchData() {
      try {
        this.tableIsLoading = true;

        const { data } = await fetchTeamsTableData();

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
          this.contextTranslate("Are you sure you want to delete the record?")
        )
      ) {
        return;
      }

      try {
        await deleteTeam({ slug, slug_type });

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
