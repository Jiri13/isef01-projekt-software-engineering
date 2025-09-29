<template>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <div @click.prevent="returnToDashboard()" class="navbar-brand">🎯 IU Quiz</div>
                <div class="nav-links">
                    <span v-if="sessionStore.userID && usersData[sessionStore.userID -1]">👋 {{ usersData[sessionStore.userID - 1].first_name }}</span>
                    <button class="btn btn-secondary" @click.prevent="logout()">Abmelden</button>
                    <button @click.prevent="debug()">Debug</button>
                </div>
            </div>
        </div>
    </nav>
</template>

<script>
import { useSessionStore } from '@/stores/session'
import usersData from '../files/users.json'
import router from '@/router/index'

export default {
    data() {
        const sessionStore = useSessionStore()

        return {
            sessionStore,
            usersData
        }
    },
    methods: {
        logout() {
            this.sessionStore.loggedIn = false;
            router.push('/')
        },
        debug(){
            console.log("SessionStore.userID:", this.sessionStore.userID)
            console.log("SessionStore.loggedIn:", this.sessionStore.loggedIn)
            console.log("UsersData 0:", usersData[0].first_name)
            console.log("Test: ", usersData[this.sessionStore.userID - 1].first_name)
        },
        returnToDashboard(){
            router.push('/');
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
    cursor: pointer;
}

.nav-links {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}
</style>