export const dynamicTableMixin = {
  data() {
    return {
      tableIsLoading: false,
      allColumnsOrdering: [],
      tableData: [],
      settings: {},
    };
  },
  methods: {
    initDynamicTable({ allColumnsOrdering, data, settings }) {
      this.allColumnsOrdering = allColumnsOrdering;
      this.tableData = data;
      this.settings = settings;
    },
  },
};
