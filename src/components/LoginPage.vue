<template>
  <div class="container" style="max-width: 500px; margin-top: 50px;">
    <div class="card">
      <h2 style="text-align: center; margin-bottom: 32px; color: #007bff;">
        🎯 IU Quiz
      </h2>

      <div
        v-if="failedLogin"
        id="loginError"
        style="display: inline-block; text-align: center;"
        class="alert-error"
      >
        E-Mail oder Passwort falsch
      </div>

      <form @submit.prevent="attemptLogin">
        <div class="form-group">
          <label class="form-label">E-Mail</label>
          <input
            v-model="enteredEmail"
            type="text"
            id="email"
            class="form-input"
            required
            placeholder="julian.schork@iu-study.org"
          />
        </div>

        <div class="form-group">
          <label class="form-label">Passwort</label>
          <input
            v-model="enteredPassword"
            type="password"
            id="password"
            class="form-input"
            required
            placeholder="Beliebiges Passwort"
          />
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
      </div>
    </div>
  </div>
</template>

<script>
import router from "@/router/index";
import { useSessionStore } from "@/stores/session";
import axios from "axios";

export default {
  data() {
    const sessionStore = useSessionStore();

    return {
      sessionStore,
      enteredEmail: "",
      enteredPassword: "",
      failedLogin: false,
      loading: false,
    };
  },
  methods: {
    async attemptLogin() {
      this.failedLogin = false;
      this.loading = true;

      try {
        const res = await axios.post("/api/login.php", {
          email: this.enteredEmail,
          password: this.enteredPassword,
        });

        if (res.data && res.data.ok) {
          const user = res.data.user;

          this.sessionStore.loggedIn = true;
          this.sessionStore.userID = user.userID;
          this.sessionStore.userRole = user.user_role;
          this.sessionStore.firstName = user.first_name;
          this.sessionStore.lastName = user.last_name;

          console.log("Login-Status:", this.sessionStore.loggedIn);

          // Wohin man nach dem Login will:
          router.push("/");
        } else {
          this.failedLogin = true;
          this.enteredPassword = "";
        }
      } catch (e) {
        console.error("Login failed:", e);
        this.failedLogin = true;
        this.enteredPassword = "";
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
