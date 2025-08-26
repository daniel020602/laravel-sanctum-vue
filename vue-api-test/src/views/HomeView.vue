<script setup>
  import { ref } from 'vue';
  import { onMounted } from 'vue';
  import { RouterLink } from 'vue-router';
  import Introduction from './Introduction.vue';

</script>

<template>
  <main>
    <div class="text-center">
      <h1 class="title">óheló</h1>
      <h2>ez itt egy technikai próba</h2>
      <Introduction />
    </div>
  </main>
</template>
