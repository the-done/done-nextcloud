<template>
  <div class="v-loader-dots">
    <span class="v-loader-dots__dot v-loader-dots__dot--1"></span>
    <span class="v-loader-dots__dot v-loader-dots__dot--2"></span>
    <span class="v-loader-dots__dot v-loader-dots__dot--3"></span>
  </div>
</template>

<script>
export default {
  name: "VLoaderDots",
};
</script>

<style scoped>
.v-loader-dots {
  display: flex;
  align-items: center;
  gap: 4px;
}

.v-loader-dots__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #666;
  animation: VLoaderDotsAnimation 1.5s infinite ease-in-out;
  opacity: 0.3;
}

.v-loader-dots__dot--1 {
  animation: VLoaderDotsAnimation 0.9s infinite;
}

.v-loader-dots__dot--2 {
  animation: VLoaderDotsAnimation 0.9s infinite 0.3s;
}

.v-loader-dots__dot--3 {
  animation: VLoaderDotsAnimation 0.9s infinite 0.6s;
}

@keyframes VLoaderDotsAnimation {
  0%,
  100% {
    opacity: 0.3;
  }
  33% {
    opacity: 1;
  }
}
</style>
