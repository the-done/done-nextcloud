<template>
  <VPageContent>
    <VPagePadding>
      <AssignProjectToTeamForm
        :project-options="projectOptions"
        :loading="isDictionaryLoading"
        @onSubmit="handleSubmit"
      />
    </VPagePadding>
    <VTable
      v-if="tableData && tableData.length > 0"
      v-model="tableData"
      :columns="columns"
      :loading="isLoading"
    >
      <template #controls="{ row: { slug, slug_type } }">
        <NcActions :inline="1">
          <NcActionButton
            :aria-label="contextTranslate('Delete', context)"
            @click="() => handleDelete({ slug, slug_type })"
          >
            <template #icon>
              <TrashCanOutline />
            </template>
          </NcActionButton>
        </NcActions>
      </template>
    </VTable>
  </VPageContent>
</template>

<script>
import { NcActions, NcActionButton } from "@nextcloud/vue";
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import { VPageContent, VPagePadding } from "@/common/widgets/VPage";
import { VTable } from "@/common/features/VTable";

import AssignProjectToTeamForm from "@/admin/widgets/AssignProjectToTeamForm/AssignProjectToTeamForm.vue";

import {
  getProjectsInTeam,
  addTeamToProject,
  removeTeamFromProject,
} from "@/admin/entities/teams/api";
import { fetchProjects } from "@/common/entities/projects/api";

import { handleRestErrors } from "@/common/shared/lib/helpers";

export default {
  name: "TeamsEditProjectsPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcActions,
    NcActionButton,
    TrashCanOutline,
    VPageContent,
    VPagePadding,
    VTable,
    AssignProjectToTeamForm,
  },
  data() {
    return {
      context: 'admin/users',
      isLoading: true,
      isDictionaryLoading: true,
      tableData: [],
      projectOptions: [],
      columns: [
        {
          label: "",
          key: "controls",
        },
        {
          label: this.contextTranslate('Project', this.context),
          key: "project_id",
        },
      ],
    };
  },
  computed: {
    slug() {
      return this.$route.params.slug;
    },
  },
  methods: {
    t,
    async fetchDictionaries() {
      this.isDictionaryLoading = true;

      try {
        const promises = [fetchProjects()];

        const [{ data: projectOptions }] = await Promise.all(promises);

        this.projectOptions = projectOptions;
      } catch (e) {
        console.log(e);
      } finally {
        this.isDictionaryLoading = false;
      }
    },
    async handleFetchData() {
      this.isLoading = true;

      try {
        const { data } = await getProjectsInTeam(this.slug);

        this.tableData = data;
      } catch (e) {
        handleRestErrors(e);
      } finally {
        this.isLoading = false;
      }
    },
    async handleDelete({ slug, slug_type }) {
      if (!confirm(this.contextTranslate('Are you sure you want to delete the record?'))) {
        return;
      }

      try {
        await removeTeamFromProject({ slug, slug_type });

        this.handleFetchData();
      } catch (e) {
        handleRestErrors(e);
      }
    },
    async handleSubmit({ project }) {
      this.isLoading = true;

      try {
        await addTeamToProject({
          team: { slug: this.slug },
          project: { slug: project.slug, slug_type: project.slug_type },
        });

        this.handleFetchData();
      } catch (e) {
        handleRestErrors(e);

        this.isLoading = false;
      }
    },
  },
  mounted() {
    this.handleFetchData();
    this.fetchDictionaries();
  },
};
</script>
