<template>
    <div class="container" style="max-width: 500px; margin-top: 50px;">
        <div class="card">
            <h2 style="text-align: center; margin-bottom: 32px; color: #007bff;">
                🎯 IU Quiz
            </h2>

            <div v-if="failedLogin" id="loginError" style="display: inline-block; text-align: center;"
                class="alert-error">E-Mail oder Passwort falsch</div>

            <form @submit.prevent="attemptLogin()">
                <div class="form-group">
                    <label class="form-label">E-Mail</label>
                    <input v-model="enteredEmail" type="text" id="email" class="form-input" required
                        placeholder="julian.schork@iu-study.org">
                </div>

                <div class="form-group">
                    <label class="form-label">Passwort</label>
                    <input v-model="enteredPassword" type="password" id="password" class="form-input" required
                        placeholder="Beliebiges Passwort">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    🚀 Anmelden
                </button>

            </form>

            <div style="margin-top: 24px; padding: 16px; background: #f8f9fa; border-radius: 8px;">
                <h4>👤 Demo-Benutzer:</h4>
                <p><strong>Julian:</strong> julian.schork@iu-study.org pw:1</p>
                <p><strong>Marie:</strong> marie.appel@iu-study.org pw:2</p>
                <p><strong>Felix:</strong> felix.hagen@iu-study.org pw:3</p>
                <p><strong>Jerôme:</strong> jerome.krueckeberg@iu-study.org pw:4</p>
                <!-- <p style="margin-top: 8px;"><em>💡 Tipp: Beliebiges Passwort verwenden</em></p> -->
            </div>
        </div>
    </div>
</template>

<script>
import router from "@/router/index"
import { useSessionStore } from "@/stores/session"
import usersData from '../files/users.json'

export default {
    data() {
        const sessionStore = useSessionStore()

        return {
            sessionStore,
            users: usersData,
            enteredEmail: '',
            enteredPassword: '',
            failedLogin: false
        }
    },
    methods: {
        // Iteriert über alle user in users.json. Wenn, gleicher Name wird geprüft ob Passwort ebenfalls passend. Hashing fehlt noch
        attemptLogin() {
            usersData.forEach((user) => {
                if (this.enteredEmail == user.email && this.enteredPassword == user.password_hash) {
                    this.sessionStore.loggedIn = true;
                    this.saveUserIDInSessionStore(user.userID)
                    console.log('Login-Status:', this.sessionStore.loggedIn);
                    router.push('/');
                }
            })

            if (this.sessionStore.loggedIn == false) {
                this.failedLogin = true;
                this.enteredEmail = '';
                this.enteredPassword = '';
            }
        },
        saveUserIDInSessionStore(userID){
            this.sessionStore.userID = userID;
        },
        testLogin() {
            router.push('/dashboard')
        }
    }
}
</script>