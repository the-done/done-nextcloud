/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

export const visibilityMixin = {
  data() {
    return {
      visibleItems: new Set(),
      loadedItems: new Set(),
      observer: null,
    };
  },
  methods: {
    setupIntersectionObserver() {
      this.observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            const id = entry.target.dataset.itemId;

            if (entry.isIntersecting) {
              this.visibleItems.add(id);
              this.loadedItems.add(id);

              this.$forceUpdate(); // Force update to draw content
            } else {
              this.visibleItems.delete(id);
            }
          });
        },
        {
          rootMargin: "200px 0px", // Offset for preload
          threshold: 0.01,
        }
      );
    },
    checkImmediateVisibility(element, id) {
      const rect = element.getBoundingClientRect();
      const isVisible =
        rect.top <= window.innerHeight + 200 &&
        rect.bottom >= -200 &&
        rect.left <= window.innerWidth &&
        rect.right >= 0;

      if (isVisible && !this.loadedItems.has(id)) {
        this.loadedItems.add(id);

        this.$forceUpdate();
      }
    },
    observeElement(element, id) {
      if (!element || !this.observer) {
        return;
      }

      element.dataset.itemId = id;

      this.observer.observe(element);

      // Check if item visible immediately
      this.checkImmediateVisibility(element, id);
    },
    isItemVisible(id) {
      return this.loadedItems.has(id);
    },
  },
  mounted() {
    this.setupIntersectionObserver();
  },
  beforeDestroy() {
    if (this.observer) {
      this.observer.disconnect();
    }
  },
};
