<template>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <div class="navbar-brand">🎯 IU Quiz Dashboard</div>
                <div class="nav-links">
                    <span>👋 ${globalState.user.username}</span>
                    <button class="btn btn-secondary" @click.prevent="logout()">Abmelden</button>
                </div>
            </div>
        </div>
    </nav>
</template>

<script>
import { useSessionStore } from '@/stores/session'
import router from '@/router/index'

export default {
    data() {
        const sessionStore = useSessionStore()

        return {
            sessionStore
        }
    },
    methods: {
        logout() {
            this.sessionStore.loggedIn = false;
            router.push('/')
        }
    }
}
</script>

<style scoped>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}
.navbar {
    background: white;
    border-bottom: 1px solid #ddd;
    padding: 16px 0;
    margin-bottom: 20px;
}

.navbar-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.navbar-brand {
    font-size: 24px;
    font-weight: 700;
    color: #007bff;
}

.nav-links {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}
</style>