// VScrollArea provides the surface; this mixin adds pointer dragging and pinned-edge shadows.
export default {
  data: () => ({ canDrag: false, drag: null, hasHiddenLeft: false, hasHiddenRight: false }),
  mounted() {
    this.resizeObserver = new ResizeObserver(() => this.measureScroll());
    this.syncScroll();
  },
  updated() { this.syncScroll(); },
  beforeDestroy() { this.endDrag(); this.resizeObserver?.disconnect(); },
  methods: {
    syncScroll() {
      const element = this.$el;
      if (element !== this.scrollElement) {
        this.resizeObserver?.disconnect();
        this.scrollElement = element;
        if (element) {
          this.resizeObserver?.observe(element);
          this.resizeObserver?.observe(element.querySelector("table"));
        }
      }
      this.measureScroll();
    },
    measureScroll() {
      const element = this.$el;
      this.canDrag = !!element && element.scrollWidth > element.clientWidth + 1;
      this.hasHiddenLeft = this.canDrag && element.scrollLeft > 1;
      this.hasHiddenRight = this.canDrag && element.scrollWidth - element.clientWidth - element.scrollLeft > 1;
    },
    startDrag(event) {
      if (!this.canDrag || event.button !== 0 || event.pointerType !== "mouse"
        || event.target.closest("button, a, input, select")) return;
      event.preventDefault();
      const element = event.currentTarget;
      this.drag = { pointerId: event.pointerId, x: event.clientX, left: element.scrollLeft, element };
      element.setPointerCapture(event.pointerId);
    },
    moveDrag(event) {
      if (!this.drag || this.drag.pointerId !== event.pointerId) return;
      this.drag.element.scrollLeft = this.drag.left - (event.clientX - this.drag.x);
    },
    endDrag() {
      const drag = this.drag;
      this.drag = null;
      if (drag?.element.hasPointerCapture(drag.pointerId)) drag.element.releasePointerCapture(drag.pointerId);
    },
  },
};
