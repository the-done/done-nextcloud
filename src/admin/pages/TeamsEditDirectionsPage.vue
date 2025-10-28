<template>
  <VPageContent>
    <VPagePadding>
      <AssignDirectionToTeamForm
        :direction-options="directionOptions"
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

import AssignDirectionToTeamForm from "@/admin/widgets/AssignDirectionToTeamForm/AssignDirectionToTeamForm.vue";

import {
  getDirectionsInTeam,
  addTeamToDirection,
  removeTeamFromDirection,
} from "@/admin/entities/teams/api";
import { fetchDirectionsDictionary } from "@/admin/entities/dictionaries/api";

import { handleRestErrors } from "@/common/shared/lib/helpers";

export default {
  name: "TeamsEditDirectionsPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcActions,
    NcActionButton,
    TrashCanOutline,
    VPageContent,
    VPagePadding,
    VTable,
    AssignDirectionToTeamForm,
  },
  data() {
    return {
      isLoading: true,
      isDictionaryLoading: true,
      tableData: [],
      directionOptions: [],
      columns: [
        {
          label: "",
          key: "controls",
        },
        {
          label: this.contextTranslate('Direction', this.context),
          key: "direction_id",
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
        const promises = [fetchDirectionsDictionary()];

        const [{ data: directionOptions }] = await Promise.all(promises);

        this.directionOptions = directionOptions.map((item) => ({
          ...item,
          id: String(item.id),
        }));
      } catch (e) {
        console.log(e);
      } finally {
        this.isDictionaryLoading = false;
      }
    },
    async handleFetchData() {
      this.isLoading = true;

      try {
        const { data } = await getDirectionsInTeam(this.slug);

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
        await removeTeamFromDirection({ slug, slug_type });

        this.handleFetchData();
      } catch (e) {
        handleRestErrors(e);
      }
    },
    async handleSubmit({ direction }) {
      this.isLoading = true;

      try {
        await addTeamToDirection({
          team: { slug: this.slug },
          direction: {
            slug: direction.slug,
            slug_type: direction.slug_type,
          },
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
