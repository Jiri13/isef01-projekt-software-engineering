<template>
  <div>
    <nav class="navbar"><div class="container"><div class="navbar-content"><div class="navbar-brand">🎯 Multiplayer Quiz</div></div></div></nav>

    <div class="container">
      <div v-if="loading" class="card">⏳ Raum wird geladen…</div>
      <div v-if="error" class="card" style="color:#dc3545">{{ error }}</div>

      <div v-if="room && currentQuestionIndex >= room.questions.length">
        <div class="card" style="text-align:center;padding:36px;">
          <h2>🏁 Quiz beendet</h2>
          <p>Dein Ergebnis: {{ score }} / {{ room.questions.length }}</p>
          <button class="btn btn-primary" @click="goBack">Zurück zum Dashboard</button>
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
import questionsFile from '@/files/questions.json'

export default {
  data() {
    return {
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
      if (this.currentQuestion.type === 'text_input') return this.textInputAnswer.trim().length > 0 && !this.reveal
      return this.selectedAnswer !== null && !this.reveal
    },
    feedbackClass() {
      return this.reveal ? (this.lastAnswerCorrect ? 'alert alert-success' : 'alert alert-error') : ''
    }
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
        // try API first
        const res = await axios.get(`/api/getRoom.php`, { params: { roomID: id } }).catch(() => null)
        if (res && res.data) {
          this.room = res.data
        } else if (history.state && history.state.room) {
          this.room = history.state.room
        } else {
          // try localStorage fallback
          const cached = localStorage.getItem(`room_${id}`)
          if (cached) this.room = JSON.parse(cached)
        }

        if (!this.room) {
          // try to fall back to the global rooms list saved by Dashboard (quiz_rooms) which may contain the room
          const allRoomsRaw = localStorage.getItem('quiz_rooms')
          if (allRoomsRaw) {
            try {
              const parsed = JSON.parse(allRoomsRaw)
              const found = (parsed || []).find(r => String(r.id) === String(id))
              if (found) this.room = found
            } catch (e) {
              console.warn('Failed to parse quiz_rooms', e)
            }
          }
        }

        if (!this.room) {
          this.error = 'Raum nicht gefunden.'
        } else {
          // ensure questions array exists and contains actual question objects
          if (!Array.isArray(this.room.questions)) this.room.questions = []

          const hasNulls = this.room.questions.some(q => q === null || typeof q === 'number' || typeof q === 'string')

          // If questions are missing or are only placeholders, fill them from local questions file
          if (!this.room.questions.length || hasNulls) {
            // filter questions by room.difficulty (if set), otherwise use all
            const diff = (this.room.difficulty || '').toString().trim().toLowerCase()
            let pool = Array.isArray(questionsFile) ? questionsFile.map(q => ({
              id: q.questionID ?? q.id,
              text: q.question_text ?? q.text,
              type: q.type ?? 'multiple_choice',
              options: q.options || [],
              correctAnswer: q.correctAnswer,
              correctAnswerText: typeof q.correctAnswer === 'string' ? q.correctAnswer : (q.correctAnswerText || ''),
              explanation: q.explanation,
              timeLimit: q.timeLimit ?? 30,
              difficulty: q.difficulty ?? 'easy'
            })) : []

            if (diff) {
              pool = pool.filter(q => (q.difficulty || '').toLowerCase() === diff)
            }

            // shuffle pool and take as many as room.questionsCount or room.questions.length or default 10
            const shuffle = arr => {
              const a = arr.slice();
              for (let i = a.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [a[i], a[j]] = [a[j], a[i]]
              }
              return a
            }

            // determine how many questions to take: prefer explicit questionsCount, else existing length, else fall back to 10 or pool length
            let count = 10
            if (typeof this.room.questionsCount === 'number' && this.room.questionsCount > 0) count = this.room.questionsCount
            else if (Array.isArray(this.room.questions) && this.room.questions.length > 0) count = this.room.questions.length
            count = Math.min(count, pool.length)
            const selected = shuffle(pool).slice(0, count)
            this.room.questions = selected
          }
        }
      } catch (e) {
        console.error(e)
        this.error = 'Fehler beim Laden des Raums.'
      } finally {
        this.loading = false
      }
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
      this.feedback = correct ? '✅ Richtig!' : `❌ Falsch. Richtige Antwort: ${this.getCorrectLabel()}`
    },
    getCorrectLabel() {
      if (!this.currentQuestion) return ''
      if (this.currentQuestion.type === 'text_input') return this.currentQuestion.correctAnswerText
      return this.currentQuestion.options?.[this.currentQuestion.correctAnswer] ?? ''
    },
    nextQuestion() {
      this.currentQuestionIndex++
      this.selectedAnswer = null
      this.textInputAnswer = ''
      this.reveal = false
      this.feedback = ''
      if (this.currentQuestionIndex >= (this.room?.questions?.length || 0)) {
        // finished
      }
    },
    getDifficultyText(d) {
      switch(d){ case 'easy': return 'Leicht'; case 'medium': return 'Mittel'; case 'hard': return 'Schwer'; default: return d }
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
