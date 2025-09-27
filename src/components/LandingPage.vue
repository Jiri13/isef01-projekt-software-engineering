<!-- JK: LandingPage.vue wird aufgerufen, wenn die Main-Page der Anwendung aufgerufen wird (/). Deshalb sind im Template sowohl die LoginPage-
(wenn nicht eingeloggt) als auch die DashboardPage-Komponente (wenn eingeloggt) -->

<template>
    <LoginPage v-if="sessionStore.loggedIn == false"></LoginPage>
    <DashboardPage v-if="sessionStore.loggedIn == true"></DashboardPage>
</template>

<script>

import { useSessionStore } from '@/stores/session'
import LoginPage from './LoginPage.vue';
import DashboardPage from './DashboardPage.vue';

export default {
    components: {
        LoginPage, DashboardPage
    },
    data() {
        const sessionStore = useSessionStore()

        return { sessionStore }
    }
}
</script>