<template>
  <div>
    <nav class="navbar">
      <div class="container">
        <div class="navbar-content">
          <div class="navbar-brand">🎯 Multiplayer Quiz</div>
            <div class="nav-links">
              <button class="btn btn-secondary" @click.prevent="goBack()">Zurück zum Dashboard</button>
            </div>
          </div>
      </div>
    </nav>

    <div class="container">
      <div v-if="loading" class="card">⏳ Raum wird geladen…</div>
      <div v-if="error" class="card" style="color:#dc3545">{{ error }}</div>

      <div v-if="room && currentQuestionIndex >= room.questions.length">
        <div class="card" style="text-align:center;padding:36px;">
          <h2>🏁 Quiz beendet</h2>
          <p>Dein Ergebnis: {{ score }} / {{ room.questions.length }}</p>
          <button class="btn btn-primary" @click="goBack">Zurück zum Dashboard</button>
          <button
              class="btn btn-secondary"
              @click="manageQuestions"
              style="padding:4px 10px;font-size:12px;"
          >
            🔧 Fragen verwalten
          </button>
        </div>
      </div>

      <div v-else-if="room">
        <div class="card">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
              <h3>{{ room.name }}</h3>
              <div style="color:#666">Schwierigkeit: {{ getDifficultyText(room.difficulty) }} • Modus: {{ room.gameMode }}</div>
            </div>
            <div>
              <small>Frage {{ currentQuestionIndex + 1 }} / {{ room.questions.length }}</small>
            </div>
          </div>

          <div style="margin-top:18px;">
            <div style="font-size:20px;font-weight:600;margin-bottom:12px">{{ currentQuestion?.text }}</div>

            <div v-if="currentQuestion.type === 'multiple_choice'">
              <div v-for="(opt, i) in currentQuestion.options" :key="i" class="answer-option"
                   :class="{'selected': selectedAnswer === i, 'correct': reveal && i === currentQuestion.correctAnswer, 'incorrect': reveal && selectedAnswer === i && i !== currentQuestion.correctAnswer }"
                   @click="selectAnswer(i)">
                {{ String.fromCharCode(65 + i) }}) {{ opt }}
              </div>
            </div>

            <div v-else-if="currentQuestion.type === 'true_false'">
              <div class="answer-option" :class="{'selected': selectedAnswer === 0, 'correct': reveal && currentQuestion.correctAnswer === 0}" @click="selectAnswer(0)">✅ Wahr</div>
              <div class="answer-option" :class="{'selected': selectedAnswer === 1, 'correct': reveal && currentQuestion.correctAnswer === 1}" @click="selectAnswer(1)">❌ Falsch</div>
            </div>

            <div v-else-if="currentQuestion.type === 'text_input'">
              <input class="form-input" v-model="textInputAnswer" placeholder="Antwort eingeben..." />
            </div>

            <div style="margin-top:16px;">
              <button class="btn btn-primary" :disabled="!canSubmit" @click="submitAnswer">✅ Antwort bestätigen</button>
              <button v-if="reveal" class="btn btn-primary" @click="nextQuestion" style="margin-left:8px;">➡️ Nächste Frage</button>
            </div>

            <div v-if="feedback" style="margin-top:12px;" :class="feedbackClass">{{ feedback }}</div>
            <div v-if="currentQuestion.explanation && reveal" style="margin-top:12px;padding:12px;background:#f8f9fa;border-left:4px solid #007bff;border-radius:6px;">💡 {{ currentQuestion.explanation }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import router from '@/router'
import { useSessionStore } from '@/stores/session'

export default {
  data() {
    const sessionStore = useSessionStore()
    return {
      sessionStore,
      room: null,
      loading: false,
      error: null,
      currentQuestionIndex: 0,
      selectedAnswer: null,
      textInputAnswer: '',
      reveal: false,
      score: 0,
      feedback: ''
    }
  },
  computed: {
    currentQuestion() {
      return this.room?.questions?.[this.currentQuestionIndex] ?? null
    },
    canSubmit() {
      if (!this.currentQuestion) return false
      if (this.currentQuestion.type === 'text_input') {
        return this.textInputAnswer.trim().length > 0 && !this.reveal
      }
      return this.selectedAnswer !== null && !this.reveal
    },
    feedbackClass() {
      return this.reveal
          ? (this.lastAnswerCorrect ? 'alert alert-success' : 'alert alert-error')
          : ''
    },
    computed: {
      currentQuestion() {
        return this.room?.questions?.[this.currentQuestionIndex] ?? null
      },
      canSubmit() {
        if (!this.currentQuestion) return false
        if (this.currentQuestion.type === 'text_input') {
          return this.textInputAnswer.trim().length > 0 && !this.reveal
        }
        return this.selectedAnswer !== null && !this.reveal
      },
      feedbackClass() {
        return this.reveal
            ? (this.lastAnswerCorrect ? 'alert alert-success' : 'alert alert-error')
            : ''
      },
      isHost() {
        if (!this.room) return false
        const me   = Number(this.sessionStore?.userID)
        const host = Number(this.room.hostID)
        return !Number.isNaN(me) && !Number.isNaN(host) && me === host
      }
    },
  },
  mounted() {
    this.loadRoom()
  },
  methods: {
    async loadRoom() {
      this.loading = true
      this.error = null
      const id = this.$route.params.id

      try {
        let room = null

        // 1) API: getRoom.php (mit echten Fragen)
        try {
          const res = await axios.get('/api/getRoom.php', { params: { roomID: id } })
          if (res && res.data && !res.data.error) {
            room = res.data
          }
        } catch (e) {
          console.warn('getRoom.php failed, trying fallbacks', e?.response?.status, e?.response?.data)
        }

        // 2) Fallback: history.state
        if (!room && history.state && history.state.room) {
          room = history.state.room
        }

        // 3) Fallback: room_X aus localStorage
        if (!room) {
          const cached = localStorage.getItem(`room_${id}`)
          if (cached) {
            try {
              room = JSON.parse(cached)
            } catch (e) {
              console.warn('Failed to parse room cache', e)
            }
          }
        }

        // 4) Fallback: quiz_rooms
        if (!room) {
          const allRoomsRaw = localStorage.getItem('quiz_rooms')
          if (allRoomsRaw) {
            try {
              const parsed = JSON.parse(allRoomsRaw)
              const found = (parsed || []).find(r => String(r.id) === String(id))
              if (found) room = found
            } catch (e) {
              console.warn('Failed to parse quiz_rooms', e)
            }
          }
        }

        if (!room) {
          this.error = 'Raum nicht gefunden.'
          this.room = null
          return
        }

        if (!Array.isArray(room.participants)) room.participants = []
        if (!Array.isArray(room.questions)) room.questions = []

        this.room = room
        console.log('Room hostID:', this.room.hostID, 'Session userID:', this.sessionStore.userID)


      } catch (e) {
        console.error(e)
        this.error = 'Fehler beim Laden des Raums.'
      } finally {
        this.loading = false
      }
    },

    // Button „Fragen verwalten“
    manageQuestions() {
      if (!this.room?.quizID) {
        alert('Diesem Raum ist kein Quiz zugeordnet.')
        return
      }
      this.$router.push({
        path: '/questions',
        query: { quizID: this.room.quizID }
      })
    },

    selectAnswer(i) {
      if (this.reveal) return
      this.selectedAnswer = i
    },
    submitAnswer() {
      if (!this.currentQuestion) return
      let correct = false

      if (this.currentQuestion.type === 'text_input') {
        const ua = this.textInputAnswer.trim().toLowerCase()
        const ca = (this.currentQuestion.correctAnswerText || '').toLowerCase()
        correct = ua === ca
      } else {
        correct = this.selectedAnswer === this.currentQuestion.correctAnswer
      }

      if (correct) this.score++
      this.reveal = true
      this.lastAnswerCorrect = correct
      this.feedback = correct
          ? '✅ Richtig!'
          : `❌ Falsch. Richtige Antwort: ${this.getCorrectLabel()}`
    },
    getCorrectLabel() {
      if (!this.currentQuestion) return ''
      if (this.currentQuestion.type === 'text_input') {
        return this.currentQuestion.correctAnswerText
      }
      return this.currentQuestion.options?.[this.currentQuestion.correctAnswer] ?? ''
    },
    nextQuestion() {
      this.currentQuestionIndex++
      this.selectedAnswer = null
      this.textInputAnswer = ''
      this.reveal = false
      this.feedback = ''
    },
    getDifficultyText(d) {
      switch (d) {
        case 'easy': return 'Leicht'
        case 'medium': return 'Mittel'
        case 'hard': return 'Schwer'
        default: return d
      }
    },
    goBack() {
      router.push('/dashboard')
    }
  }
}
</script>

<style scoped>
.answer-option{padding:12px;margin:8px 0;border:2px solid #ddd;border-radius:6px;cursor:pointer;transition:all 0.15s}
.answer-option.selected{background:#007bff;color:white;border-color:#007bff}
.answer-option.correct{background:#28a745;color:white;border-color:#28a745}
.answer-option.incorrect{background:#dc3545;color:white;border-color:#dc3545}
.alert{padding:12px;border-radius:6px;margin-top:12px}
.alert-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
.alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
</style>
