import { defineStore } from "pinia";

export const useSessionStore = defineStore('session', {
  state: () => ({
    loggedIn: false,
    userID: null,
    userRole: null,   // <- NEU
    firstName: null,  // <- NEU
    lastName: null,   // <- NEU
  }),
  // falls vorhanden:
  persist: {
    paths: ['loggedIn', 'userID', 'userRole', 'firstName', 'lastName'], // <- ERGÄNZEN
  },
});
