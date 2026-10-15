export default {
  data: () => ({ nativeFullscreen: false, fallbackFullscreen: false }),
  computed: { isFullscreen() { return this.nativeFullscreen || this.fallbackFullscreen; } },
  watch: { isFullscreen(value) { document.body.classList.toggle("done-matrix-fullscreen", value); } },
  mounted() {
    document.addEventListener("fullscreenchange", this.onFullscreenChange);
    document.addEventListener("keydown", this.onFullscreenKeydown, true);
  },
  beforeDestroy() {
    document.body.classList.remove("done-matrix-fullscreen");
    document.removeEventListener("fullscreenchange", this.onFullscreenChange);
    document.removeEventListener("keydown", this.onFullscreenKeydown, true);
    if (document.fullscreenElement === this.$el) document.exitFullscreen().catch(() => {});
  },
  methods: {
    clearReportSelection() {
      const selection = window.getSelection();
      if (!selection || selection.isCollapsed || !selection.rangeCount || !this.$el) return;
      if (selection.getRangeAt(0).intersectsNode(this.$el)) selection.removeAllRanges();
    },
    async enterFullscreen() {
      this.clearReportSelection();
      this.filtersOpen = false;
      this.closeDetails();
      this.exportMenuOpen = false;
      // Phone browsers do not consistently support fullscreen for arbitrary elements.
      if (!this.isMobile && this.$el.requestFullscreen) {
        try {
          await this.$el.requestFullscreen();
          return;
        } catch (error) {
          // Embedded browsers may deny native fullscreen; still offer a table-only viewport.
        }
      }
      this.fallbackFullscreen = true;
      this.$nextTick(() => this.$refs.matrixTable?.$el.focus());
    },
    onFullscreenChange() {
      this.clearReportSelection();
      const wasFullscreen = this.nativeFullscreen;
      this.nativeFullscreen = document.fullscreenElement === this.$el;
      if (this.nativeFullscreen) this.$nextTick(() => this.$refs.matrixTable?.$el.focus());
      else if (wasFullscreen) this.$nextTick(() => this.$refs.fullscreenButton?.$el.focus());
    },
    onFullscreenKeydown(event) {
      if (event.key !== "Escape" || !this.isFullscreen) return;
      event.preventDefault();
      event.stopPropagation();
      this.exitFullscreen();
    },
    exitFullscreen() {
      if (document.fullscreenElement === this.$el) document.exitFullscreen();
      else {
        this.fallbackFullscreen = false;
        this.$nextTick(() => this.$refs.fullscreenButton?.$el.focus());
      }
    },
  },
};
