import { defineStore } from "pinia";

export const useSessionStore = defineStore('session', {
    state: () => ({
            loggedIn: false
    }),
    persist:true
})