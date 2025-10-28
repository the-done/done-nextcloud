<template>
  <div class="flex flex-col h-full">
    <VToolbar>
      <slot name="toolbar-left" />
      <template #actions>
        <DynamicTableSettings
          :all-columns-ordering="allColumnsOrdering"
          @on-update-column-ordering="handleUpdateColumnsOrdering"
          @on-update-hidden-columns="handleUpdateHiddenColumns"
          @on-delete-all-columns-ordering="handleDeleteAllColumnsOrdering"
        />
        <slot name="toolbar-controls" />
      </template>
    </VToolbar>
    <div class="flex-[1_1_auto] flex flex-col overflow-hidden">
      <div class="flex items-center p-2 border-t border-(--color-border)">
        <DynamicTableSortStatus
          :all-columns-ordering="allColumnsOrdering"
          :settings="settings"
          class="pr-2"
          @on-delete="handelDeleteSort"
          @on-change="handleChangeSort"
          @on-update-sort-ordering="handleUpdateSortOrdering"
          @on-delete-all="handelDeleteAllSort"
        />
        <div
          v-if="filters && filters.length > 0"
          class="flex gap-2 items-center pl-2 border-l border-(--color-border)"
        >
          <template v-for="item in filters">
            <DynamicTableFilterStatus
              :key="item.key"
              :item="item"
              @on-change="handleChangeFilter"
              @on-delete="handleDeleteFilter"
            />
          </template>
          <VButton
            size="small"
            variant="tertiary"
            @click="handleDeleteAllFilters"
          >
            <template #icon>
              <Close :size="16" />
            </template>
          </VButton>
        </div>
      </div>
      <VTable
        :value="value"
        :columns="columns"
        :loading="loading"
        :unique-key="uniqueKey"
        :empty-content-title="emptyContentTitle"
        :empty-content-description="emptyContentDescription"
        :draggable="draggable"
        @on-update-columns-ordering="handleUpdateColumnsOrdering"
        @on-sort="handleSort"
        @on-create-filter="handleCreateFilter"
      >
        <template v-for="(_, slot) of $scopedSlots" v-slot:[slot]="scope">
          <slot :name="slot" v-bind="scope" />
        </template>
      </VTable>
    </div>
  </div>
</template>

<script>
import { t } from "@nextcloud/l10n";
import { mapState, mapWritableState } from "pinia";

import Close from "vue-material-design-icons/Close.vue";

import { VTable } from "@/common/features/VTable";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import VButton from "@/common/shared/components/VButton/VButton.vue";

import { DynamicTableSettings } from "./components/DynamicTableSettings";
import { DynamicTableSortStatus } from "./components/DynamicTableSortStatus";
import { DynamicTableFilterStatus } from "./components/DynamicTableFilterStatus";

import {
  saveTableSortColumnsMultiple,
  saveTableColumnView,
  saveTableSortWithinColumns,
  saveTableSortWithinColumnsMultiple,
  deleteTableSortWithinColumns,
  getConditionsForFilter,
  saveTableFilter,
  deleteTableFilter,
  deleteTableSortColumns,
} from "@/admin/entities/dynamicTables/api";

import { useDynamicTableStore } from "@/admin/app/store/dynamicTable";

export default {
  name: "DynamicTable",
  emits: ["on-fetch", "update:allColumnsOrdering"],
  components: {
    Close,
    VTable,
    VToolbar,
    VButton,
    DynamicTableSettings,
    DynamicTableSortStatus,
    DynamicTableFilterStatus,
  },
  props: {
    source: {
      type: Number,
      default: null,
    },
    value: {
      type: Array,
      default: () => [],
    },
    allColumnsOrdering: {
      type: Array,
      default: () => [],
    },
    uniqueKey: {
      type: String,
      default: "id",
    },
    loading: {
      type: Boolean,
      default: false,
    },
    emptyContentTitle: {
      type: String,
      default: t("done", "No data available"),
    },
    emptyContentDescription: {
      type: String,
      default: t("done", "We could not find data for your request."),
    },
    draggable: {
      type: Boolean,
      default: true,
    },
    settings: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {};
  },
  computed: {
    ...mapWritableState(useDynamicTableStore, ["conditions"]),
    ...mapState(useDynamicTableStore, [
      "filterConditionsFetched",
      "filterConditionsList",
    ]),
    columns() {
      return this.allColumnsOrdering.map((item) => {
        const { title, hidden } = item;

        return {
          ...item,
          visible: !hidden,
          label: title,
        };
      });
    },
    filters() {
      return this.allColumnsOrdering.filter(
        (item) => item.rules?.filter_settings
      );
    },
  },
  methods: {
    t,
    async fetchFilterConditions() {
      try {
        const { data } = await getConditionsForFilter();

        if (!data) {
          return [];
        }

        this.conditions.list = data;

        this.conditions.isFetched = true;
      } catch (e) {
        console.log(e);
      }
    },
    async handleUpdateColumnsOrdering(value) {
      this.$emit("update:allColumnsOrdering", value);

      const sortData = value.reduce((accum, item, index) => {
        return {
          ...accum,
          [index]: item.key,
        };
      }, {});

      try {
        await saveTableSortColumnsMultiple({
          sort_data: sortData,
          source: this.source,
          for_all: false,
        });
      } catch (e) {
        console.log(e);
      }
    },
    async handleUpdateHiddenColumns({ nextValue, item, isHidden }) {
      this.$emit("update:allColumnsOrdering", nextValue);

      const { key } = item;

      try {
        const { tableColumnView } = this.settings;
        const hide = !isHidden;
        const columnArray = tableColumnView[key];

        if (columnArray && columnArray.length > 0) {
          const column = columnArray[0];

          await saveTableColumnView({
            source: this.source,
            column: key,
            width: 100,
            hide,
            for_all: false,
            slug: column.slug,
          });

          return;
        }

        await saveTableColumnView({
          source: this.source,
          column: key,
          width: 100,
          hide,
          for_all: false,
        });
      } catch (e) {
        console.log(e);
      }
    },
    async handleDeleteAllColumnsOrdering() {
      try {
        await deleteTableSortColumns({ source: this.source });

        this.$emit("on-fetch");
      } catch (e) {
        console.log(e);
      }
    },
    async handleSort({ item, value }) {
      try {
        if (item.rules?.sort_settings) {
          await saveTableSortWithinColumns({
            ...item.rules.sort_settings,
            source: this.source,
            column: item.key,
            sort: value,
          });

          this.$emit("on-fetch");

          return;
        }

        const { tableSortWithinColumns } = this.settings;

        const currentSort = Object.keys(tableSortWithinColumns).reduce(
          (accum, key) => {
            const sortArray = tableSortWithinColumns[key];

            if (!sortArray) {
              return accum;
            }

            const sortItem = sortArray[0];

            if (!sortItem) {
              return accum;
            }

            return [
              ...accum,
              {
                column: key,
                sort: sortItem.sort_ordering === 1 ? "ASC" : "DESC",
                for_all: false,
              },
            ];
          },
          []
        );

        const payload = {
          source: this.source,
          sort_data: [
            ...currentSort,
            {
              column: item.key,
              sort: value,
              for_all: false,
            },
          ],
        };

        await saveTableSortWithinColumnsMultiple(payload);

        this.$emit("on-fetch");
      } catch (e) {
        console.log(e);
      }
    },
    async handelDeleteSort(item) {
      if (!item?.rules?.sort_settings) {
        return;
      }

      try {
        await deleteTableSortWithinColumns({
          slug: item.rules.sort_settings.slug,
        });

        this.$emit("on-fetch");
      } catch (e) {
        console.log(e);
      }
    },
    async handelDeleteAllSort() {
      try {
        await deleteTableSortWithinColumns({ source: this.source });

        this.$emit("on-fetch");
      } catch (e) {
        console.log(e);
      }
    },
    async handleChangeSort({ item, value }) {
      if (!item?.rules?.sort_settings) {
        return;
      }

      try {
        await saveTableSortWithinColumns({
          ...item.rules.sort_settings,
          source: this.source,
          column: item.key,
          sort: value,
        });

        this.$emit("on-fetch");
      } catch (e) {
        console.log(e);
      }
    },
    async handleUpdateSortOrdering(nextValue) {
      const sortKeys = nextValue.reduce(
        (accum, item) => [...accum, item.key],
        []
      );

      const nextAllColumnsOrdering = this.allColumnsOrdering.reduce(
        (accum, item) => {
          const index = sortKeys.findIndex((key) => key === item.key);

          if (index === -1) {
            return [...accum, item];
          }

          return [
            ...accum,
            {
              ...item,
              rules: {
                ...item.rules,
                sort_settings: {
                  ...item.rules.sort_settings,
                  sort_ordering: index,
                },
              },
            },
          ];
        },
        []
      );

      this.$emit("update:allColumnsOrdering", nextAllColumnsOrdering);

      const payload = {
        source: this.source,
        sort_data: nextValue.reduce((accum, item) => {
          return [
            ...accum,
            {
              column: item.key,
              sort: item.rules?.sort_settings?.sort || "ASC",
              for_all: false,
            },
          ];
        }, []),
      };

      await saveTableSortWithinColumnsMultiple(payload);

      this.$emit("on-fetch");
    },
    handleCreateFilter(item) {
      const { key } = item;

      if (this.filters.find((i) => i.key === key) !== undefined) {
        return;
      }

      const nextValue = this.allColumnsOrdering.map((column) => {
        if (column.key === item.key) {
          return {
            ...item,
            rules: {
              ...item.rules,
              filter_settings: {
                condition: 1,
                value: "",
              },
            },
          };
        }

        return column;
      });

      this.$emit("update:allColumnsOrdering", nextValue);
    },
    async handleChangeFilter({ item, value, conditionValue }) {
      try {
        if (item?.rules?.filter_settings) {
          await saveTableFilter({
            source: this.source,
            column: item.key,
            condition: conditionValue.value,
            value,
            for_all: false,
            slug: item.rules.filter_settings.slug,
          });

          this.$emit("on-fetch");

          return;
        }

        await saveTableFilter({
          source: this.source,
          column: item.key,
          condition: conditionValue.value,
          value,
          for_all: false,
        });

        this.$emit("on-fetch");
      } catch (e) {
        console.log(e);
      }
    },
    async handleDeleteFilter(item) {
      if (!item?.rules?.filter_settings) {
        return;
      }

      try {
        await deleteTableFilter({
          slug: item.rules.filter_settings.slug,
        });

        this.$emit("on-fetch");
      } catch (e) {
        console.log(e);
      }
    },
    async handleDeleteAllFilters() {
      try {
        await deleteTableFilter({ source: this.source });

        this.$emit("on-fetch");
      } catch (e) {
        console.log(e);
      }
    },
  },
  mounted() {
    if (this.filterConditionsFetched) {
      return;
    }

    this.fetchFilterConditions();
  },
};
</script>
