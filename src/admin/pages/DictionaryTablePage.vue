<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          v-for="(item, index) in breadcrumbs"
          :key="index"
          :name="contextTranslate(item.title, context)"
          :to="item.path"
          :forceIconText="index === 0"
        >
          <template v-if="item.icon" #icon>
            <component v-if="item.icon" :is="item.icon" />
            <BookOpenVariant v-else />
          </template>
        </NcBreadcrumb>
      </NcBreadcrumbs>

      <template #actions>
        <NcActions :force-name="true">
          <NcActionButton
            v-if="tableData && tableData.length > 0"
            :to="createLink"
          >
            <template #icon>
              <Plus />
            </template>
            {{ contextTranslate("Create", context) }}
          </NcActionButton>
        </NcActions>
      </template>
    </VToolbar>
    <VTable
      v-model="tableData"
      :columns="columns"
      :loading="isLoading"
      :empty-content-description="
        contextTranslate(
          'We could not find any data in this dictionary.',
          context
        )
      "
    >
      <template #controls="{ row }">
        <NcActions :inline="1">
          <NcActionButton
            :aria-label="contextTranslate('Edit', context)"
            @click="() => handleClickEdit(row)"
          >
            <template #icon>
              <Pencil />
            </template>
          </NcActionButton>
          <NcActionButton :force-name="true" @click="() => handleDelete(row)">
            <template #icon>
              <TrashCanOutline />
              {{ contextTranslate("Delete", context) }}
            </template>
          </NcActionButton>
        </NcActions>
      </template>

      <template #emptyContentActions>
        <NcButton :to="createLink" type="primary" class="v-mt-1">
          <template #icon> <Plus /> </template>
          {{ contextTranslate("Add a record to the dictionary", context) }}
        </NcButton>
      </template>
    </VTable>
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

import BookOpenVariant from "vue-material-design-icons/BookOpenVariant.vue";
import Plus from "vue-material-design-icons/Plus.vue";
import Pencil from "vue-material-design-icons/Pencil.vue";
import TrashCanOutline from "vue-material-design-icons/TrashCanOutline.vue";

import { VPage } from "@/common/widgets/VPage";
import { VTable } from "@/common/features/VTable";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";

import {
  fetchDictionary,
  deleteDictionaryItem,
} from "../entities/dictionaries/api";

import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "DictionaryPositionsPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcActions,
    NcActionButton,
    NcButton,
    BookOpenVariant,
    Plus,
    Pencil,
    TrashCanOutline,
    VPage,
    VToolbar,
    VTable,
  },
  props: {
    additionalProps: {
      type: Object,
      default: () => ({}),
    },
  },
  data: () => ({
    context: "admin/users",
    isLoading: false,
    columns: [
      {
        label: "",
        key: "controls",
      },
      {
        label: t("done", "Title"),
        key: "name",
      },
      {
        label: t("done", "Sorting"),
        key: "sort",
      },
    ],
    tableData: [],
  }),
  computed: {
    dictionaryTitle() {
      return this.additionalProps.dictionaryTitle;
    },
    breadcrumbs() {
      return this.additionalProps.breadcrumbs;
    },
    createLink() {
      const { path } = this.$route;

      return path + "new";
    },
  },
  methods: {
    t,
    async handleFetchData() {
      try {
        this.isLoading = true;

        const { data } = await fetchDictionary(this.dictionaryTitle);

        this.tableData = data;
      } catch (e) {
        console.log(e);
      } finally {
        this.isLoading = false;
      }
    },
    handleClickEdit({ slug }) {
      const { path } = this.$route;
      const editLink = path + slug;

      this.$router.push(editLink);
    },
    async handleDelete({ slug, slug_type }) {
      if (!confirm(t("done", "Are you sure you want to delete the record?"))) {
        return;
      }

      try {
        await deleteDictionaryItem(this.dictionaryTitle, { slug, slug_type });

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
  watch: {
    $route() {
      this.init();
    },
  },
};
</script>
