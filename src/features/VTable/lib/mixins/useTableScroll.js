export const useTableScroll = {
  data() {
    return {
      scrollLeft: 0,
      scrollWidth: 0,
      clientWidth: 0,
    };
  },
  computed: {
    isScrolledHorizontally() {
      return this.scrollWidth > this.clientWidth && this.scrollLeft > 0;
    },
    isScrolledHorizontallyEnd() {
      if (this.clientWidth === 0) {
        return true;
      }

      return (
        this.scrollWidth <= this.clientWidth ||
        Math.ceil(this.scrollLeft) + this.clientWidth >= this.scrollWidth
      );
    },
  },
  methods: {
    updateScrollState(el) {
      this.scrollLeft = el.scrollLeft;
      this.scrollWidth = el.scrollWidth;
      this.clientWidth = el.clientWidth;
    },
    handleScroll(event) {
      this.updateScrollState(event.target);
    },
    handleResize() {
      const el = this.$refs.scrollArea?.$el;

      if (el) {
        this.updateScrollState(el);
      }
    },
  },
  mounted() {
    window.addEventListener("resize", this.handleResize);
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.handleResize);
  },
};
