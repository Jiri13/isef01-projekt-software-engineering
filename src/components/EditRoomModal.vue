<template>
  <div class="modal-inner" style="max-width:640px;width:100%;background:white;border-radius:8px;padding:20px;">
    <h2>⚙️ Raum bearbeiten</h2>

    <form @submit.prevent="saveRoom">
      <div class="form-group">
        <label class="form-label">Raumname</label>
        <input class="form-input" v-model="form.name" required />
      </div>

      <div class="form-group">
        <label class="form-label">Spielmodus</label>
        <select class="form-input" v-model="form.gameMode">
          <option value="cooperative">Kooperativ</option>
          <option value="competitive">Kompetitiv</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Schwierigkeitsgrad</label>
        <select class="form-input" v-model="form.difficulty">
          <option value="easy">Leicht</option>
          <option value="medium">Mittel</option>
          <option value="hard">Schwer</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Max. Teilnehmer</label>
        <input type="number" min="2" max="99" class="form-input" v-model.number="form.maxParticipants" />
      </div>

      <div class="form-group">
        <label class="form-label">Quiz auswählen</label>
        <select class="form-input" v-model.number="form.quizID">
          <option :value="0">— Kein Quiz verknüpfen —</option>
          <option v-for="q in quizzes" :key="q.quizID" :value="q.quizID">
            {{ q.title }} (ID: {{ q.quizID }})
          </option>
        </select>
      </div>

      <div style="display:flex;gap:12px;margin-top:16px;">
        <button type="button" class="btn btn-secondary" @click="close">Abbrechen</button>
        <button type="submit" class="btn btn-primary">💾 Speichern</button>
      </div>
    </form>
  </div>
</template>

<script>
import { useSessionStore } from '@/stores/session'
import axios from 'axios'

export default {
  emits: ['update:modelValue', 'updated'],
  props: {
    modelValue: { type: Boolean, default: false },
    roomToEdit: { type: Object, default: null }
  },
  data() {
    return {
      session: useSessionStore(),
      form: {
        name: '',
        gameMode: 'cooperative',
        difficulty: 'medium',
        maxParticipants: 8,
        quizID: 0
      },
      quizzes: []
    }
  },
  // WICHTIG: Wenn das Modal per v-if eingeblendet wird, feuert mounted()
  mounted() {
    if (this.modelValue) {
      this.loadQuizzes();
      this.fillForm();
    }
  },
  watch: {
    // Falls das Modal nicht zerstört, sondern nur ausgeblendet würde (v-show), brauchen wir das hier:
    modelValue(val) {
      if (val) {
        this.loadQuizzes();
        this.fillForm();
      }
    },
    // Falls sich der Raum ändert, während das Modal offen ist
    roomToEdit: {
      deep: true,
      handler() {
        if (this.modelValue) this.fillForm();
      }
    }
  },
  methods: {
    close() {
      this.$emit('update:modelValue', false)
    },
    fillForm() {
      if (!this.roomToEdit) return;

      console.log("Fülle Formular mit:", this.roomToEdit); // Debugging

      // Daten übernehmen und Fallbacks setzen
      this.form.name = this.roomToEdit.name || this.roomToEdit.room_name || '';

      const gm = this.roomToEdit.gameMode || this.roomToEdit.play_mode || 'cooperative';
      this.form.gameMode = gm.toLowerCase();

      const diff = this.roomToEdit.difficulty || 'medium';
      this.form.difficulty = diff.toLowerCase();

      this.form.maxParticipants = this.roomToEdit.maxParticipants || this.roomToEdit.max_participants || 8;
      this.form.quizID = this.roomToEdit.quizID || 0;
    },
    async loadQuizzes() {
      try {
        const { data } = await axios.get('/api/listQuizzes.php')
        this.quizzes = data || []
      } catch (e) {
        console.error(e)
      }
    },
    async saveRoom() {
      try {
        const payload = {
          roomID: this.roomToEdit.id || this.roomToEdit.roomID, // IDs abfangen
          userID: this.session.userID,
          name: this.form.name,
          playMode: this.form.gameMode,
          difficulty: this.form.difficulty,
          maxParticipants: this.form.maxParticipants,
          quizID: this.form.quizID
        }

        const res = await axios.post('/api/updateRoom.php', payload)
        if (res.data && res.data.ok) {
          alert("Raum erfolgreich aktualisiert!");
          this.$emit('updated');
          this.close();
        }
      } catch (e) {
        console.error(e);
        alert("Fehler beim Speichern: " + (e.response?.data?.error || e.message));
      }
    }
  }
}
</script>

<style scoped>
.modal-inner { }
</style>