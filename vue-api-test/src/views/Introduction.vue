<template>
    <main>
        <div class="text-center">
            <h1 class="title">Üdvözöljük A Duna Bisztró weboldalán!</h1>
            <p>Fedezze fel ínycsiklandó ételeinket és barátságos légkörünket!</p>
            <RouterLink :to="{ name: 'menu' }" class="text-blue-500 hover:underline">Tekintse meg menünket</RouterLink>
        </div>
    </main>
</template>

