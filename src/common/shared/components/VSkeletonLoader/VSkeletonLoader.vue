<template>
  <div class="skeleton-loader" :style="styles">
    <div
      v-if="type === 'box'"
      class="skeleton-box"
      :class="{ 'skeleton-animated': animated }"
    />
    <div
      v-else-if="type === 'text'"
      class="skeleton-text"
      :class="{ 'skeleton-animated': animated }"
    >
      <div v-for="(line, index) in lines" :key="index" class="skeleton-line" />
    </div>
    <div
      v-else-if="type === 'circle'"
      class="skeleton-circle"
      :class="{ 'skeleton-animated': animated }"
    />
    <slot v-else />
  </div>
</template>

<script>
export default {
  name: "SkeletonLoader",
  props: {
    type: {
      type: String,
      default: "box",
      validator: (value) => ["box", "text", "circle", "custom"].includes(value),
    },
    width: {
      type: [String, Number],
      default: "100%",
    },
    height: {
      type: [String, Number],
      default: "var(--default-clickable-area)",
    },
    lines: {
      type: Number,
      default: 3,
    },
    animated: {
      type: Boolean,
      default: true,
    },
    bgColor: {
      type: String,
      default: "#eee",
    },
    highlightColor: {
      type: String,
      default: "#f5f5f5",
    },
    borderRadius: {
      type: String,
      default: "var(--vs-border-radius)",
    },
    lineSpacing: {
      type: String,
      default: "8px",
    },
  },

  computed: {
    styles() {
      return {
        "--bg-color": this.bgColor,
        "--highlight-color": this.highlightColor,
        "--border-radius": this.borderRadius,
        "--line-spacing": this.lineSpacing,
        width: typeof this.width === "number" ? `${this.width}px` : this.width,
        height:
          typeof this.height === "number" ? `${this.height}px` : this.height,
      };
    },
  },
};
</script>

<style scoped>
.skeleton-loader {
  display: inline-block;
  overflow: hidden;
  position: relative;
  vertical-align: top;
}

.skeleton-box,
.skeleton-circle,
.skeleton-line {
  background-color: var(--bg-color);
  border-radius: var(--border-radius);
  width: 100%;
  height: 100%;
}

.skeleton-circle {
  border-radius: 50%;
}

.skeleton-text {
  width: 100%;
}

.skeleton-line {
  margin-bottom: var(--line-spacing);
}

.skeleton-line:last-child {
  margin-bottom: 0;
  width: 80%;
}

.skeleton-animated {
  position: relative;
  overflow: hidden;
}

.skeleton-animated::after {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(
    90deg,
    transparent,
    var(--highlight-color),
    transparent
  );
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}
</style>
