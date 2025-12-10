<template>
  <div class="modal-inner" style="max-width:640px;width:100%;background:white;border-radius:8px;padding:20px;">
    <h2>🏠 Neuen Quiz-Raum erstellen</h2>

    <form @submit.prevent="createRoom">
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
        <label class="form-label">Schwierigkeitsgrad (Fallback)</label>
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
        <label class="form-label">Quiz auswählen (optional)</label>
        <select class="form-input" v-model.number="form.quizID">
          <option :value="0">— Kein Quiz verknüpfen —</option>
          <option v-for="q in quizzes" :key="q.quizID" :value="q.quizID">
            {{ q.title }} (ID: {{ q.quizID }})
          </option>
        </select>
        <small style="color:#666;">
          Wenn ein Quiz ausgewählt ist, werden die Fragen dieses Quizzes im Raum verwendet.
        </small>
      </div>

      <div style="display:flex;gap:12px;margin-top:16px;">
        <button type="button" class="btn btn-secondary" @click="close">Abbrechen</button>
        <button type="submit" class="btn btn-primary">Raum erstellen</button>
      </div>
    </form>
  </div>
</template>

<script>
import { useSessionStore } from '@/stores/session'
import axios from 'axios'

export default {
  emits: ['update:modelValue','created'],
  props: {
    modelValue: { type: Boolean, default: false }
  },
  data() {
    const session = useSessionStore()
    return {
      session,
      form: {
        name: '',
        gameMode: 'cooperative',
        difficulty: 'easy',
        maxParticipants: 8,
        quizID: 0
      },
      quizzes: [],
      loadingQuizzes: false
    }
  },
  watch: {
    modelValue(newVal) {
      if (newVal) {
        this.loadQuizzes()
      }
    }
  },
  mounted() {
    if (this.modelValue) {
      this.loadQuizzes()
    }
  },
  methods: {
    close() {
      this.$emit('update:modelValue', false)
    },
    async loadQuizzes() {
      try {
        this.loadingQuizzes = true
        const { data } = await axios.get('/api/listQuizzes.php')
        this.quizzes = data || []
      } catch (e) {
        console.error('Fehler beim Laden der Quizzes', e)
        this.quizzes = []
      } finally {
        this.loadingQuizzes = false
      }
    },
    async createRoom() {
      if (!this.form.name.trim()) {
        alert('Bitte einen Raumnamen angeben.')
        return
      }
      if (!this.session.userID) {
        alert('Kein Benutzer im SessionStore gefunden.')
        return
      }

      try {
        const payload = {
          name: this.form.name.trim(),
          playMode: this.form.gameMode,
          difficulty: this.form.difficulty,
          maxParticipants: this.form.maxParticipants,
          quizID: this.form.quizID > 0 ? this.form.quizID : null,
          userID: this.session.userID,
          addHostAsParticipant: true
        }

        const { data } = await axios.post('/api/addRoom.php', payload)

        if (!data || !data.ok || !data.room) {
          console.error('Unerwartete Antwort von addRoom.php', data)
          alert('Raum konnte nicht erstellt werden.')
          return
        }

        const newRoom = {
          id: data.room.id,
          name: data.room.name,
          gameMode: data.room.playMode || 'cooperative',
          difficulty: data.room.difficulty,
          code: data.room.code,
          hostID: data.room.hostID,
          started: data.room.started,
          quizID: data.room.quizID,
          participants: [],
          questions: [],
          maxParticipants: data.room.maxParticipants ?? this.form.maxParticipants
        }

        this.$emit('created', newRoom)
        this.$emit('update:modelValue', false)

        // Reset
        this.form.name = ''
        this.form.gameMode = 'cooperative'
        this.form.difficulty = 'easy'
        this.form.maxParticipants = 8
        this.form.quizID = 0

      } catch (e) {
        console.error('Fehler beim Erstellen des Raums', e?.response?.data || e)
        alert('Fehler beim Erstellen des Raums.')
      }
    }
  }
}
</script>

<style scoped>
.modal-inner { }
</style>