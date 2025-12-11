<template>
    <DashboardNavbar />
    <div class="container">
        <h2>👤 Benutzerverwaltung</h2>

        <div class="card">
            <h3>Benutzer hinzufügen</h3>
            <input v-model="newUser.first_name" placeholder="Vorname" type="text" class="form-input">
            <input v-model="newUser.last_name" placeholder="Nachname" type="text" class="form-input">
            <input v-model="newUser.email" placeholder="E-Mail" type="email" class="form-input">
            <button @click="addUser()" :disabled="!newUser.first_name || !newUser.email" class="btn btn-primary">
                Hinzufügen
            </button>
        </div>

        <hr>

        <h3>📋 Aktuelle Nutzer ({{ users.length }})</h3>
        <ul class="card" :class="{ 'overflow': users.length > 5 }">
            <li v-for="user in users" class="user-item">
                <div>
                    <div v-if="!user.isEditing">
                        <label class="form-label">{{ user.first_name }} {{ user.last_name }}</label>
                        <span>({{ user.email }})</span>
                    </div>

                    <div v-else>
                        <input v-model="user.tempFirstName" type="text" class="form-input">
                        <input v-model="user.tempLastName" type="text" class="form-input">
                        <input v-model="user.tempEmail" type="email" class="form-input">
                    </div>
                </div>

                <div>
                    <button @click="toggleEdit(user)" class="btn btn-primary">
                        {{ user.isEditing ? 'Speichern' : 'Bearbeiten' }}
                    </button>

                    <button v-if="!user.isEditing" @click="deleteUser(user.userID)" class="btn btn-danger">
                        Löschen
                    </button>
                    <button v-else @click="cancelEdit(user)" class="btn btn-danger">
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
import users from '../files/users.json'

export default {
    components: { DashboardNavbar },
    data() {
        return {
            users,
            newUser: {
                first_name: '',
                last_name: '',
                email: '',
            },
            nextId: 10,
        };
    },
    methods: {
        addUser() {
            if (!this.newUser.first_name || !this.newUser.email) return;

            const user = {
                id: this.nextId++,
                first_name: this.newUser.first_name,
                last_name: this.newUser.last_name,
                email: this.newUser.email,
            };

            //Füge User zu DB hinzu

            // Lösche Daten in Formular
            this.newUser.first_name = '';
            this.newUser.last_name = '';
            this.newUser.email = '';
        },

        deleteUser(userID) {
            // Lösche User aus DB
        },

        toggleEdit(user) {
            if (user.isEditing) {
                if (user.tempEmail) {
                    user.email = user.tempEmail;
                }
                if (user.tempFirstName) {
                    user.first_name = user.tempFirstName; //in DB ändern
                }
                if (user.tempLastName) {
                    user.last_name = user.tempLastName; //in DB ändern
                }
                user.isEditing = false;
                delete user.tempEmail;
                delete user.tempFirstName;
                delete user.tempLastName;
            } else {
                user.tempEmail = user.email;
                user.tempFirstName = user.first_name;
                user.tempLastName = user.last_name
                user.isEditing = true;
            }
        },

        cancelEdit(user) {
            user.isEditing = false;
            delete user.tempEmail;
            delete user.tempFirstName;
            delete user.tempLastName;
        }

    }
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