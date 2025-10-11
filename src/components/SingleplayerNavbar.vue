<template>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <div class="navbar-brand">🎮 Einzelspieler</div>
                <div class="nav-links">
                    <button class="btn btn-secondary" @click.prevent="goToDashboard()">Zurück zum Dashboard</button>
                </div>
            </div>
        </div>
    </nav>
</template>

<script>
import router from '@/router';

export default {
    methods: {
        goToDashboard() {
            router.push('/');
        }
    }
}
</script>