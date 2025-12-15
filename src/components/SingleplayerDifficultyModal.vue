<template>
  <div class="modal">
    <div class="modal-content">
      <h1 style="text-align: center;">Einzelspieler</h1>

      <div id="sp-no-game-running" v-if="!singleplayerStore.hasStarted">
        <!-- QUIZ-AUSWAHL -->
        <div id="sp-modal-quiz">
          <h2 style="margin-bottom: 24px;">Quiz auswählen:</h2>
          <div style="height: 250px; overflow-y: auto; margin-bottom: 24px;">
            <div
                v-for="quiz in getQuizzesFromUserQuizCatalogue()"
                :key="quiz.quizID"
                class="card"
                :class="{ selected: quiz.quizID === selectedQuizID && mode === 'quiz' }"
                style="border: 2px solid #007bff; margin-bottom: 16px; position: relative;"
            >
              <div style="cursor: pointer;" @click.prevent="selectQuiz(quiz)">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; align-items: flex-start;">
                  <div>
                    <h3>
                      {{ quiz.title }}
                      <small style="font-weight: normal; color: #666;">Ersteller: {{ quiz.creatorName }}</small>
                    </h3>
                    <p>{{ quiz.quiz_description }}</p>
                    <p>Studiengang: {{ quiz.category }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- KATALOG-MODUS -->
        <div id="sp-modal-catalogue">
          <h2 style="margin-bottom: 24px;">Fragen aus dem gesamten Katalog:</h2>
          <div class="form-group">
            <label class="form-label">Wählen Sie den Schwierigkeitsgrad:</label>

            <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
              <label
                  class="difficulty-easy"
                  :class="{ 'selected-difficulty': selectedDifficulty === 'easy' && mode === 'catalogue' }"
                  style="cursor: pointer; padding: 16px; border: 2px solid #28a745; border-radius: 8px; text-align: center;"
              >
                <input
                    type="radio"
                    name="singlePlayerDifficulty"
                    value="easy"
                    v-model="selectedDifficulty"
                    @change="mode='catalogue'"
                    style="display:none;"
                >
                <div style="font-weight: 600;">🟢 Leicht</div>
              </label>

              <label
                  class="difficulty-medium"
                  :class="{ 'selected-difficulty': selectedDifficulty === 'medium' && mode === 'catalogue' }"
                  style="cursor: pointer; padding: 16px; border: 2px solid #ffc107; border-radius: 8px; text-align: center;"
              >
                <input
                    type="radio"
                    name="singlePlayerDifficulty"
                    value="medium"
                    v-model="selectedDifficulty"
                    @change="mode='catalogue'"
                    style="display:none;"
                >
                <div style="font-weight: 600;">🟡 Mittel</div>
              </label>

              <label
                  class="difficulty-hard"
                  :class="{ 'selected-difficulty': selectedDifficulty === 'hard' && mode === 'catalogue' }"
                  style="cursor: pointer; padding: 16px; border: 2px solid #dc3545; border-radius: 8px; text-align: center;"
              >
                <input
                    type="radio"
                    name="singlePlayerDifficulty"
                    value="hard"
                    v-model="selectedDifficulty"
                    @change="mode='catalogue'"
                    style="display:none;"
                >
                <div style="font-weight: 600;">🔴 Schwer</div>
              </label>
            </div>
          </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 24px;">
          <button type="button" class="btn btn-secondary" @click.prevent="$emit('update:modelValue', false)" style="flex: 1;">
            Abbrechen
          </button>
          <button type="button" class="btn btn-primary" @click.prevent="startSingleplayer()" style="flex: 1;">
            ✅ Spiel starten
          </button>
        </div>
      </div>

      <div id="sp-game-running" style="margin:auto" v-else>
        <p>Es läuft bereits ein Quiz</p>
        <button type="button" class="btn btn-secondary" @click.prevent="$emit('update:modelValue', false)" style="flex: 1;">
          Abbrechen
        </button>
        <button type="button" class="btn btn-danger" @click.prevent="resetSingleplayerStore()">Neues Quiz starten</button>
        <button type="button" class="btn btn-primary" @click.prevent="goToSingleplayerPage()">Fortsetzen</button>
      </div>
    </div>
  </div>
</template>

<script>
import { useSessionStore } from '@/stores/session'
import { useSingleplayerStore } from '@/stores/singleplayer'
import router from '@/router/index'
import axios from 'axios'

export default {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  data() {
    const sessionStore = useSessionStore()
    const singleplayerStore = useSingleplayerStore()
    return {
      sessionStore,
      singleplayerStore,
      quizzes: [],
      questions: [],
      selectedQuizID: null,
      selectedQuizTitle: '',
      mode: '',
      selectedDifficulty: null,
      loading: false,
      error: null
    }
  },
  methods: {
    selectQuiz(quiz) {
      this.mode = 'quiz'
      this.selectedQuizID = quiz.quizID
      this.selectedQuizTitle = quiz.title
      this.selectedDifficulty = null
    },
    async startSingleplayer() {
      // Store vorfüllen/leeren
      this.singleplayerStore.questions = []
      this.singleplayerStore.score = 0
      this.singleplayerStore.finished = false

      try {
        this.loading = true
        this.error = null

        if (this.mode === 'quiz') {
          if (!this.selectedQuizID) {
            alert('Bitte zuerst ein Quiz auswählen.')
            return
          }

          // FRAGEN + OPTIONEN zum ausgewählten Quiz
          const { data } = await axios.get('/api/getQuizQuestions.php', {
            params: { quizID: this.selectedQuizID }
          })
          const questions = Array.isArray(data?.questions) ? data.questions : []

          // Optionen zufällig mischen
          for (const q of questions) {
            if (Array.isArray(q.options)) {
              q.options = q.options
                  .map(value => ({ value, sort: Math.random() }))
                  .sort((a, b) => a.sort - b.sort)
                  .map(({ value }) => value)
            }
          }

          if (!questions.length) {
            alert('Für dieses Quiz wurden keine Fragen gefunden.')
            return
          }

          this.singleplayerStore.quiz = {
            quizID: this.selectedQuizID,
            title: this.selectedQuizTitle
                || (this.quizzes.find(q => q.quizID === this.selectedQuizID)?.title ?? 'Quiz'),
            questions: questions.map(q => q.questionId)
          }
          this.singleplayerStore.questions = questions

        } else if (this.mode === 'catalogue') {
          // erst prüfen & abbrechen
          if (!this.selectedDifficulty) {
            alert('Bitte zuerst einen Schwierigkeitsgrad wählen oder ein Quiz auswählen.')
            return
          }

          const diff = (this.selectedDifficulty || 'easy').toLowerCase()

          // FRAGEN + OPTIONEN aus Katalog (by difficulty)
          const { data } = await axios.get('/api/getQuestionsByDifficulty.php', {
            params: { difficulty: diff, limit: 20 }
          })
          let questions = Array.isArray(data?.questions) ? data.questions : []

          // Nur Fragen mit Optionen
          questions = questions.filter(q => Array.isArray(q.options) && q.options.length > 0)

          // Optionen zufällig mischen
          for (const q of questions) {
            if (Array.isArray(q.options)) {
              q.options = q.options
                  .map(value => ({ value, sort: Math.random() }))
                  .sort((a, b) => a.sort - b.sort)
                  .map(({ value }) => value)
            }
          }

          if (!questions.length) {
            alert('Für den gewählten Schwierigkeitsgrad wurden keine Fragen gefunden.')
            return
          }

          this.singleplayerStore.quiz = {
            quizID: null,
            title: `Katalog – ${diff}`,
            questions: questions.map(q => q.questionId)
          }
          this.singleplayerStore.questions = questions

        } else {
          alert('Bitte wähle ein Quiz oder einen Schwierigkeitsgrad aus dem Katalog.')
          return
        }

        // Einheitliche Start-Initialisierung
        this.singleplayerStore.currentQuestionIndex = 0
        this.singleplayerStore.hasStarted = true
        this.$emit('update:modelValue', false) // Modal schließen
        router.push('/singleplayer')

      } catch (e) {
        this.error = e?.response?.data?.error || 'Fehler beim Starten.'
        console.error(e)
      } finally {
        this.loading = false
      }
    },

    resetSingleplayerStore() {
      // Wichtig: Index niemals null setzen
      this.singleplayerStore.hasStarted = false
      this.singleplayerStore.quizID = null
      this.singleplayerStore.quiz = null
      this.singleplayerStore.questions = []
      this.singleplayerStore.currentQuestionIndex = 0  // <-- fix
      this.singleplayerStore.score = 0
      this.singleplayerStore.finished = false
    },

    goToSingleplayerPage() {
      router.push('/singleplayer')
    },

    getQuizzesFromUserQuizCatalogue() {
      return Array.isArray(this.quizzes) ? this.quizzes : []
    }
  },
  async mounted() {
    try {
      this.loading = true
      const { data } = await axios.get('/api/getQuizzes.php', {
        params: { userID: this.sessionStore.userID }
      })
      this.quizzes = Array.isArray(data) ? data : []
    } catch (e) {
      this.error = e?.response?.data?.error || 'Konnte Quizzes nicht laden.'
      console.error(e)
    } finally {
      this.loading = false
    }
  }
}
</script>