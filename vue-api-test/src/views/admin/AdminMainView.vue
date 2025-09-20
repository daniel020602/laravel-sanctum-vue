<template>
    <div class="container mx-auto p-4 self-center bg-cyan-100 rounded-lg mt-4">
        <h1 class="text-2xl font-bold text-center p-4 m-4 items-center">Adminisztrációs felület</h1>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 justify-items-center">
                <div class="admin-item">statisztika</div>
            
            <div class="admin-item">rendelések</div>
            <RouterLink :to="{name: 'admin-menu'}">
                <div class="admin-item">étlap szerkesztése</div>
            </RouterLink>
            <RouterLink :to="{name: 'admin-weeks'}">
                <div class="admin-item">heti menü kiírása</div>
            </RouterLink>
            <RouterLink :to="{name: 'admin-users'}">
                <div class="admin-item">felhasználók kezelése</div>
            </RouterLink>
        </div>
    </div>
</template>