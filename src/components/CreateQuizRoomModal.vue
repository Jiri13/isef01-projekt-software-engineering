<template>
  <div class="modal">
    <div class="modal-content">
      <h2 style="margin-bottom: 24px;">🏠 Neuen Quiz-Raum erstellen</h2>

      <!-- Vue: submit über Methode statt inline-HTML-Handler -->
      <form @submit.prevent="submit">
        <div class="form-group">
          <label class="form-label">Raum Name</label>
          <input
              type="text"
              id="roomName"
              class="form-input"
              v-model.trim="name"
              required
              placeholder="Wirtschaftsinformatik Grundlagen"
          />
        </div>

        <div class="form-group">
          <label class="form-label">Schwierigkeitsgrad</label>
          <select id="roomDifficulty" class="form-input" v-model="difficulty">
            <option value="easy">🟢 Leicht</option>
            <option value="medium">🟡 Mittel</option>
            <option value="hard">🔴 Schwer</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Spielmodus</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <!-- Kooperativ -->
            <input
                type="radio" id="gm-coop" name="gameMode"
                value="cooperative" v-model="playMode"
                style="position:absolute;opacity:0;width:0;height:0;"
            />
            <label
                for="gm-coop"
                :style="playMode==='cooperative'
        ? 'cursor:pointer;padding:16px;border:2px solid #007bff;border-radius:8px;text-align:center;box-shadow:0 0 0 2px rgba(0,123,255,.15);'
        : 'cursor:pointer;padding:16px;border:2px solid #ddd;border-radius:8px;text-align:center;'">
              <div style="font-weight:600;color:#007bff;">🤝 Kooperativ</div>
            </label>

            <!-- Kompetitiv -->
            <input
                type="radio" id="gm-comp" name="gameMode"
                value="competitive" v-model="playMode"
                style="position:absolute;opacity:0;width:0;height:0;"
            />
            <label
                for="gm-comp"
                :style="playMode==='competitive'
        ? 'cursor:pointer;padding:16px;border:2px solid #dc3545;border-radius:8px;text-align:center;box-shadow:0 0 0 2px rgba(220,53,69,.15);'
        : 'cursor:pointer;padding:16px;border:2px solid #ddd;border-radius:8px;text-align:center;'">
              <div style="font-weight:600;color:#dc3545;">⚔️ Kompetitiv</div>
            </label>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Max. Teilnehmer</label>
          <input
              type="number"
              id="maxParticipants"
              class="form-input"
              v-model.number="maxParticipants"
              :min="2" :max="20"
          >
        </div>

        <div v-if="error" class="alert alert-error" style="margin-bottom:8px;">{{ error }}</div>

        <div style="display: flex; gap: 12px;">
          <button
              type="button"
              class="btn btn-secondary"
              @click.prevent="$emit('update:modelValue', false)"
              :disabled="loading"
              style="flex: 1;"
          >
            Abbrechen
          </button>
          <button type="submit" class="btn btn-primary" :disabled="loading" style="flex: 1;">
            {{ loading ? '… wird erstellt' : '✅ Raum erstellen' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { useSessionStore } from '@/stores/session'

export default {
  // JK: Prop via v-model kommt aus Dashboard
  props: {
    modelValue: { type: Boolean, default: false },
    // Falls du eine feste Quiz-ID nutzen willst, hier übergeben; sonst passe addRoom.php an
    defaultQuizID: { type: Number, default: 1 }
  },
  emits: ['update:modelValue', 'created'],
  data() {
    const sessionStore = useSessionStore()
    return {
      sessionStore,
      name: '',
      difficulty: 'medium',   // aus deinem Select
      playMode: 'cooperative',// aus deinen Radio-Buttons
      maxParticipants: 8,     // aus deinem Number-Input
      loading: false,
      error: null
    }
  },
  methods: {
    async submit() {
      this.error = null
      if (!this.name) {
        this.error = 'Bitte einen Raumnamen angeben.'
        return
      }
      try {
        this.loading = true
        const payload = {
          name: this.name,
          playMode: this.playMode,                   // 'cooperative' | 'competitive'
          difficulty: this.difficulty,               // 'easy' | 'medium' | 'hard'
          maxParticipants: this.maxParticipants,     // Zahl
          quizID: this.defaultQuizID,                // oder aus Parent mitgeben
          userID: this.sessionStore.userID,          // Host = angemeldeter User
          addHostAsParticipant: true                 // Komfort: Host gleich drin
        }

        const { data } = await axios.post('/api/addRooms.php', payload)
        if (!data?.ok) throw new Error(data?.error || 'Unbekannter Fehler')

        // Parent informieren + Modal schließen
        this.$emit('created', data.room)
        this.$emit('update:modelValue', false)

        // Reset
        this.name = ''
        this.difficulty = 'medium'
        this.playMode = 'cooperative'
        this.maxParticipants = 8
      } catch (e) {
        this.error = e?.response?.data?.error || e.message || 'Fehler beim Erstellen'
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
