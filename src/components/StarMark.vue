<template>
  <!-- Draw the mark explicitly so mobile browsers cannot turn it into an emoji. -->
  <svg class="star-mark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true" focusable="false">
    <path d="M12 2v20M2 12h20M5 5l14 14M5 19L19 5" />
  </svg>
</template>

<style scoped>
.star-mark {
  display: inline-block;
  width: 1em;
  height: 1em;
  vertical-align: -0.125em;
}
</style>
