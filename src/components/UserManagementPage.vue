<template>
  <DashboardNavbar />

  <div class="container">
    <h2>👤 Benutzerverwaltung</h2>

    <!-- Benutzer hinzufügen -->
    <div class="card">
      <h3>Benutzer hinzufügen</h3>

      <input v-model="newUser.first_name" placeholder="Vorname" type="text" class="form-input">
      <input v-model="newUser.last_name" placeholder="Nachname" type="text" class="form-input">
      <input v-model="newUser.email" placeholder="E-Mail" type="email" class="form-input">

      <!-- NEU: Rolle auswählen -->
      <select v-model="newUser.user_role" class="form-input">
        <option disabled value="">Rolle auswählen</option>
        <option value="Creator">Creator (normaler Benutzer)</option>
        <option value="Admin">Admin</option>
      </select>

      <!-- NEU: Passwort setzen -->
      <input v-model="newUser.password" placeholder="Passwort" type="password" class="form-input">

      <button
        @click="addUser"
        :disabled="!newUser.first_name || !newUser.email || !newUser.user_role || !newUser.password || loading"
        class="btn btn-primary"
      >
        Hinzufügen
      </button>

      <!-- Feedback -->
      <p v-if="errorMessage" style="color:#dc3545; margin-top:8px;">{{ errorMessage }}</p>
      <p v-if="successMessage" style="color:#28a745; margin-top:8px;">{{ successMessage }}</p>
    </div>

    <hr>

    <!-- Aktuelle Nutzer -->
    <h3>📋 Aktuelle Nutzer ({{ users.length }})</h3>

    <ul class="card" :class="{ 'overflow': users.length > 5 }">
      <li v-for="user in users" :key="user.userID" class="user-item">
        <div>
          <!-- ANZEIGEMODUS -->
          <div v-if="!user.isEditing">
            <label class="form-label">
              {{ user.first_name }} {{ user.last_name }}
              <span style="font-size:12px; color:#666;">[{{ user.user_role }}]</span>
            </label>
            <span>({{ user.email }})</span>
          </div>

          <!-- EDIT-MODUS -->
          <div v-else>
            <input v-model="user.tempFirstName" type="text" class="form-input" placeholder="Vorname">
            <input v-model="user.tempLastName" type="text" class="form-input" placeholder="Nachname">
            <input v-model="user.tempEmail" type="email" class="form-input" placeholder="E-Mail">

            <select v-model="user.tempRole" class="form-input">
              <option value="Creator">Creator</option>
              <option value="Admin">Admin</option>
            </select>

            <!-- NEU: Passwort ändern -->
            <input
              v-model="user.tempPassword"
              type="password"
              class="form-input"
              placeholder="Passwort ändern (optional)"
            >
          </div>
        </div>

        <!-- BUTTONS -->
        <div>
          <button @click="toggleEdit(user)" class="btn btn-primary">
            {{ user.isEditing ? 'Speichern' : 'Bearbeiten' }}
          </button>

          <button
            v-if="!user.isEditing"
            @click="deleteUser(user.userID)"
            class="btn btn-danger"
          >
            Löschen
          </button>

          <button
            v-else
            @click="cancelEdit(user)"
            class="btn btn-danger"
          >
            Abbrechen
          </button>
        </div>
      </li>
    </ul>

    <p v-if="!users.length" class="no-users-message">Es sind keine Nutzer gespeichert.</p>
  </div>
</template>

<script>
import DashboardNavbar from './DashboardNavbar.vue';
import axios from 'axios';

export default {
  components: { DashboardNavbar },

  data() {
    return {
      users: [],
      newUser: {
        first_name: '',
        last_name: '',
        email: '',
        user_role: '',
        password: '',
      },
      loading: false,
      errorMessage: '',
      successMessage: '',
    };
  },

  async mounted() {
    await this.fetchUsers();
  },

  methods: {
    async fetchUsers() {
      this.errorMessage = '';
      try {
        const res = await axios.get('/api/getUsers.php');
        this.users = (res.data || []).map(u => ({
          ...u,
          isEditing: false,
        }));
      } catch (e) {
        console.error(e);
        this.errorMessage = 'Konnte Benutzerliste nicht laden.';
      }
    },

    async addUser() {
      this.errorMessage = '';
      this.successMessage = '';

      if (!this.newUser.first_name || !this.newUser.email || !this.newUser.password || !this.newUser.user_role) {
        this.errorMessage = 'Bitte alle Felder ausfüllen.';
        return;
      }

      this.loading = true;
      try {
        const res = await axios.post('/api/createUser.php', this.newUser);

        if (res.data?.ok) {
          this.users.push({
            ...res.data.user,
            isEditing: false,
          });

          this.successMessage = 'Benutzer erfolgreich angelegt!';
          this.newUser = { first_name: '', last_name: '', email: '', user_role: '', password: '' };
        } else {
          this.errorMessage = res.data?.error || 'Fehler beim Anlegen des Benutzers.';
        }

      } catch (e) {
        console.error(e);
        this.errorMessage = e.response?.data?.error || 'Fehler beim Anlegen des Benutzers.';
      } finally {
        this.loading = false;
      }
    },

    async deleteUser(userID) {
      if (!confirm('Soll der Benutzer wirklich gelöscht werden?')) return;

      try {
        const res = await axios.post('/api/deleteUser.php', { userID });

        if (res.data?.ok) {
          this.users = this.users.filter(u => u.userID !== userID);
        } else {
          this.errorMessage = res.data?.error || 'Fehler beim Löschen.';
        }
      } catch (e) {
        console.error(e);
        this.errorMessage = e.response?.data?.error || 'Fehler beim Löschen.';
      }
    },

    async toggleEdit(user) {
      this.errorMessage = '';
      this.successMessage = '';

      // SPEICHERN
      if (user.isEditing) {
        const payload = {
          userID: user.userID,
          first_name: user.tempFirstName,
          last_name: user.tempLastName,
          email: user.tempEmail,
          user_role: user.tempRole,
        };

        if (user.tempPassword?.trim() !== '') {
          payload.password = user.tempPassword.trim();
        }

        try {
          const res = await axios.post('/api/updateUser.php', payload);

          if (res.data?.ok) {
            user.first_name = payload.first_name;
            user.last_name = payload.last_name;
            user.email = payload.email;
            user.user_role = payload.user_role;

            user.isEditing = false;
            delete user.tempFirstName;
            delete user.tempLastName;
            delete user.tempEmail;
            delete user.tempRole;
            delete user.tempPassword;
          } else {
            this.errorMessage = res.data?.error || 'Fehler beim Speichern.';
          }

        } catch (e) {
          console.error(e);
          this.errorMessage = e.response?.data?.error || 'Fehler beim Speichern.';
        }

        return;
      }

      // EDIT-MODUS aktivieren (Felder befüllen)
      user.tempFirstName = user.first_name;
      user.tempLastName = user.last_name;
      user.tempEmail = user.email;
      user.tempRole = user.user_role;
      user.tempPassword = '';

      user.isEditing = true;
    },

    cancelEdit(user) {
      user.isEditing = false;
      delete user.tempFirstName;
      delete user.tempLastName;
      delete user.tempEmail;
      delete user.tempRole;
      delete user.tempPassword;
    },
  },
};
</script>

<style scoped>
.user-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  border-bottom: 1px solid #eee;
}
.user-item:last-child {
  border-bottom: none;
}
.overflow {
  height: 500px;
  overflow-y: auto;
  margin-bottom: 24px;
}
</style>
