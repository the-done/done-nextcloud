<template>
  <VPage>
    <DynamicTable
      v-model="tableData"
      :all-columns-ordering.sync="columnsWithControls"
      :loading="tableIsLoading"
      :source="source"
      :settings="settings"
      :empty-content-description="
        contextTranslate('We could not find data for your teams.', context)
      "
      @on-fetch="handleFetchData"
    >
      <template #toolbar-left>
        <NcBreadcrumbs>
          <NcBreadcrumb
            :name="contextTranslate('Teams', context)"
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
            {{ contextTranslate("Create", context) }}
          </NcActionButton>
        </NcActions>
        <ExportButton :source="source" context-name="admin/teams" />
      </template>
      <template #controls="{ row: { slug, slug_type } }">
        <NcActions :inline="1">
          <NcActionButton
            v-if="getCommonPermission('canReadTeamsList') === true"
            :aria-label="contextTranslate('Preview', context)"
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
              {{ contextTranslate("Edit", context) }}
            </template>
          </NcActionButton>
          <NcActionButton
            v-if="getCommonPermission('canDeleteTeams') === true"
            :force-name="true"
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
        <NcButton :to="{ name: 'team-new' }" type="primary" class="v-mt-1">
          <template #icon> <Plus /> </template>
          {{ contextTranslate("Add team", context) }}
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

import Flag from "vue-material-design-icons/Flag.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";
import EyeOutline from "vue-material-design-icons/EyeOutline.vue";
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import { VPage } from "@/common/widgets/VPage";
import { DynamicTable } from "@/admin/widgets/DynamicTable";
import ExportButton from "@/admin/widgets/ExportButton.vue";

import { fetchTeamsTableData, deleteTeam } from "@/admin/entities/teams/api";

import { usePermissionStore } from "@/admin/app/store/permission";

import { dynamicTableMixin } from "@/common/shared/mixins/dynamicTableMixin";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { MAP_DYNAMIC_TABLE_SOURCES } from "@/admin/entities/dynamicTables/constants";

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
    t,
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
      if (!confirm(t("done", "Do you really want to delete the team?"))) {
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
