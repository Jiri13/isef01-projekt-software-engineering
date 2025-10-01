import { defineStore } from "pinia";

export const useSessionStore = defineStore('session', {
    state: () => ({
            loggedIn: false,
            userID: null
    }),
    persist: {
        paths: ['loggedIn', 'userID']
    }
})