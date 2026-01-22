export const abortControllerMixin = {
  data: () => ({
    abortController: new AbortController(),
  }),
  methods: {
    resetAbortController() {
      this.abortController.abort();

      this.abortController = new AbortController();
    },
    handleCatchAbortControllerError(e) {
      console.log(e);

      if (e.code !== "ERR_CANCELED") {
        this.isLoading = false;
      }
    },
  },
};
