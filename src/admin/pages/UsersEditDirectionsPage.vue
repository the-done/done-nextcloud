/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPageContent>
    <VPagePadding>
      <AssignDirectionToUserForm
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
      <template #controls="{ row: { slug } }">
        <NcActions :inline="1">
          <NcActionButton
            aria-label="Delete"
            @click="() => handleDelete({ slug })"
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

import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import { fetchDirectionsDictionary } from "@/admin/entities/dictionaries/api";
import {
  fetchUserDirections,
  addUserToDirection,
  deleteUserFromDirection,
} from "@/common/entities/users/api";

import { VPageContent, VPagePadding } from "@/common/widgets/VPage";

import { VTable } from "@/common/features/VTable";

import AssignDirectionToUserForm from "@/admin/widgets/AssignDirectionToUserForm/AssignDirectionToUserForm.vue";

import { handleRestErrors } from "@/common/shared/lib/helpers";
import { t } from '@nextcloud/l10n';
import { contextualTranslationsMixin } from '@/common/shared/mixins/contextualTranslationsMixin';

export default {
  name: "UsersEditDirectionsPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcActions,
    NcActionButton,
    TrashCanOutline,
    VPageContent,
    VPagePadding,
    VTable,
    AssignDirectionToUserForm,
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
          label: "Direction",
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

        this.directionOptions = directionOptions;
      } catch (e) {
        console.log(e);
      } finally {
        this.isDictionaryLoading = false;
      }
    },
    async handleFetchData() {
      this.isLoading = true;

      try {
        const { data } = await fetchUserDirections({ slug: this.slug });

        this.tableData = data;

        this.fetchDictionaries();
      } catch (e) {
        console.log(e);
      } finally {
        this.isLoading = false;
      }
    },
    async handleDelete({ slug }) {
      if (!confirm(this.contextTranslate('Are you sure you want to remove the role from the employee?', this.context))) {
        return;
      }

      try {
        await deleteUserFromDirection({ slug });

        this.handleFetchData();
      } catch (e) {
        console.log(e);

        handleRestErrors(e);
      }
    },
    async handleSubmit({ direction }) {
      this.isLoading = true;

      try {
        await addUserToDirection({
          user: { slug: this.slug },
          direction: { slug: direction.slug, slug_type: direction.slug_type },
        });

        this.handleFetchData();
      } catch (e) {
        console.log(e);

        handleRestErrors(e);

        this.isLoading = false;
      }
    },
  },
  mounted() {
    this.handleFetchData();
  },
};
</script>

<style scoped>
.users-edit-roles-page-table {
  width: 100%;
}
</style>
