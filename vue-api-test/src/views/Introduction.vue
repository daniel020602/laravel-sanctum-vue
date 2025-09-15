<template>
    <main>
        <div class="text-center">
            <h1 class="title">Üdvözöljük A Duna Bisztró weboldalán!</h1>
            <p>Fedezze fel ínycsiklandó ételeinket és barátságos légkörünket!</p>
            <RouterLink :class="{ 'text-blue-500 hover:underline': true }" :to="{ name: 'menu' }">Tekintse meg menünket</RouterLink>
            <br />
            <RouterLink :class="{ 'text-blue-500 hover:underline': true }" :to="{ name: 'subscription' }">Iratkozzon fel a jövő heti menüre</RouterLink>
        </div>
    </main>
</template>

