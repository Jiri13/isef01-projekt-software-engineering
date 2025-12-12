<template>
  <SingleplayerNavbar />

  <!-- Laufendes Quiz -->
  <div class="container" v-if="!singleplayerStore.finished">
    <div class="card" v-if="currentQuestion">
      <div style="margin-bottom: 24px;">
        <div style="background: #f8f9fa; height: 8px; border-radius: 4px; margin-bottom: 16px;">
          <div
            style="height: 100%; border-radius: 4px; transition: width 0.3s ease; background: #007bff;"
            :style="{ width: progressBarWidth }"
          />
        </div>
        <div style="text-align: center; color: #666; font-size: 14px;">
          Frage: {{ (singleplayerStore.currentQuestionIndex ?? 0) + 1 }}
          von {{ singleplayerStore.questions.length }} | Punkte:
          {{ singleplayerStore.score }}
        </div>
      </div>

      <div style="font-size: 20px; font-weight: 600; margin-bottom: 24px; text-align: center;">
        {{ currentQuestion?.questionText ?? '—' }}
      </div>

      <!-- MULTIPLE_CHOICE / TRUE_FALSE -->
      <template v-if="!isTextInputQuestion">
        <div
          id="singlePlayerOptions"
          :class="{ 'disabled-options': isAnswerSubmitted }"
          v-for="(option, index) in currentQuestion?.options || []"
          :key="option.optionId ?? index"
        >
          <div
            class="answer-option"
            :class="getOptionClasses(option)"
            @click.prevent="select(option)"
          >
            {{ option.optionText }}
          </div>
        </div>
      </template>

      <!-- TEXT_INPUT -->
      <template v-else>
        <div class="form-group">
          <label for="textInputAnswer" class="form-label">Ihre Antwort:</label>
          <input
            id="textInputAnswer"
            v-model="textInputAnswer"
            type="text"
            class="form-control"
            :readonly="isAnswerSubmitted"
            placeholder="Antwort eingeben …"
          />
        </div>

        <div
          v-if="isAnswerSubmitted && textInputCorrectAnswer"
          class="singleplayer-explanation"
          style="margin-top: 16px;"
        >
          Richtige Antwort: <strong>{{ textInputCorrectAnswer }}</strong>
        </div>
      </template>

      <!-- Erklärung -->
      <div
        id="singleplayerExplanation"
        class="singleplayer-explanation"
        v-if="isAnswerSubmitted && currentQuestionHasExplanation"
      >
        {{ currentQuestion.explanation }}
      </div>

      <button
        id="submitSinglePlayerBtn"
        class="btn btn-primary"
        @click.prevent="submitAnswer()"
        :disabled="isSubmitDisabled"
        style="width: 100%; margin-top: 24px; font-size: 16px; padding: 16px;"
      >
        {{ buttonText }}
      </button>
    </div>

    <!-- Fallback, falls currentQuestion (noch) nicht da ist -->
    <div class="card" v-else>
      Lade Frage …
    </div>
  </div>

  <!-- Ergebnis -->
  <div class="container" v-if="singleplayerStore.finished">
    <div class="card" style="text-align: center; padding: 48px;">
      <h1 :style="ratingColor">{{ rating }}</h1>

      <div
        class="badge"
        style="padding: 8px 16px; border-radius: 20px; font-size: 16px; margin-bottom: 24px;"
      >
        Schwierigkeit: {{ currentQuestion?.difficulty ?? '—' }}
      </div>

      <div style="background: #f8f9fa; padding: 32px; border-radius: 12px; margin: 32px 0;">
        <div style="font-size: 36px; font-weight: 700; color: #007bff; margin-bottom: 16px;">
          {{ singleplayerStore.score }} / {{ singleplayerStore.questions.length }}
        </div>
        <div style="font-size: 24px; color: #666;">
          {{ Math.round((singleplayerStore.score / (singleplayerStore.questions.length || 1)) * 100) }} %
          richtig beantwortet
        </div>
      </div>

      <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
        <button class="btn btn-secondary" @click.prevent="goToDashboard()" style="padding: 16px 32px;">
          📊 Zum Dashboard
        </button>
      </div>
    </div>
  </div>

  <Teleport to="body">
    <div v-if="isShowingSinglePlayerModal" class="modal">
      <SingleplayerDifficultyModal v-model="isShowingSinglePlayerModal" />
    </div>
  </Teleport>
</template>

<script>
import SingleplayerNavbar from './SingleplayerNavbar.vue'
import router from '@/router'
import { useSingleplayerStore } from '@/stores/singleplayer'
import { useSessionStore } from '@/stores/session'
import SingleplayerDifficultyModal from './SingleplayerDifficultyModal.vue'
import axios from 'axios'

export default {
  components: {
    SingleplayerNavbar,
    SingleplayerDifficultyModal
  },
  data() {
    const singleplayerStore = useSingleplayerStore()
    const sessionStore = useSessionStore()

    const ButtonState = Object.freeze({
      SUBMIT: { text: '✅ Antwort bestätigen' },
      NEXT: { text: '➡️ Nächste Frage' },
      FINISH: { text: '🏁 Quiz beenden' }
    })

    return {
      singleplayerStore,
      sessionStore,
      selectedOptionId: null,
      isAnswerSubmitted: false,
      showResult: false,
      ButtonState,
      isShowingSinglePlayerModal: false,
      textInputAnswer: '',
      textInputCorrectAnswer: ''
    }
  },
  computed: {
    progressBarWidth() {
      const total = this.singleplayerStore.questions.length || 1
      const idx = Math.min(this.singleplayerStore.currentQuestionIndex ?? 0, total - 1)
      const progress = (idx / total) * 100
      return `${progress}%`
    },
    currentQuestion() {
      const idx = this.singleplayerStore.currentQuestionIndex ?? 0
      return this.singleplayerStore.questions[idx] ?? null
    },
    currentQuestionHasExplanation() {
      const exp = this.currentQuestion?.explanation
      return typeof exp === 'string' && exp.trim() !== ''
    },
    isTextInputQuestion() {
      const type = (this.currentQuestion?.questionType || '').toLowerCase()
      return type === 'text_input'
    },
    isSubmitDisabled() {
      if (this.isAnswerSubmitted) return false
      if (this.isTextInputQuestion) {
        return !this.textInputAnswer || this.textInputAnswer.trim() === ''
      }
      return this.selectedOptionId == null
    },
    isLastQuestion() {
      return (this.singleplayerStore.currentQuestionIndex ?? 0) ===
        (this.singleplayerStore.questions.length - 1)
    },
    buttonText() {
      if (!this.isAnswerSubmitted) return this.ButtonState.SUBMIT.text
      return this.isLastQuestion ? this.ButtonState.FINISH.text : this.ButtonState.NEXT.text
    },
    rating() {
      const percentage = (this.singleplayerStore.score / (this.singleplayerStore.questions.length || 1)) * 100
      if (percentage >= 90) return '🏆 Hervorragend!'
      if (percentage >= 70) return '🥈 Sehr gut!'
      if (percentage >= 50) return '🥉 Gut gemacht!'
      return '💪 Weiter üben!'
    },
    ratingColor() {
      const percentage = (this.singleplayerStore.score / (this.singleplayerStore.questions.length || 1)) * 100
      if (percentage >= 90) return { color: '#28a745', marginBottom: '32px' }
      if (percentage >= 70) return { color: '#17a2b8', marginBottom: '32px' }
      if (percentage >= 50) return { color: '#ffc107', marginBottom: '32px' }
      return { color: '#dc3545', marginBottom: '32px' }
    }
  },
  methods: {
    goToDashboard() {
      this.resetSingleplayerStore()
      router.push('/')
    },
    getOptionClasses(option) {
      const isSelected = this.selectedOptionId === option.optionId
      const classes = { selected: isSelected }
      if (this.isAnswerSubmitted) {
        if (option.isCorrect) {
          classes.correct = true
          classes.selected = false
        } else if (isSelected) {
          classes.incorrect = true
          classes.selected = false
        }
      }
      return classes
    },
    resetSingleplayerStore() {
      this.singleplayerStore.hasStarted = false
      this.singleplayerStore.quizID = null
      this.singleplayerStore.quiz = null
      this.singleplayerStore.questions = []
      this.singleplayerStore.currentQuestionIndex = 0
      this.singleplayerStore.score = 0
      this.singleplayerStore.finished = false
      this.selectedOptionId = null
      this.isAnswerSubmitted = false
      this.textInputAnswer = ''
      this.textInputCorrectAnswer = ''
    },
    select(option) {
      if (this.isAnswerSubmitted) return
      if (this.isTextInputQuestion) return
      this.selectedOptionId = option.optionId
    },
    async submitAnswer() {
      const q = this.currentQuestion
      if (!q) return

      // 1. Antwort auswerten
      if (!this.isAnswerSubmitted) {
        this.isAnswerSubmitted = true
        let isCorrect = false

        if (this.isTextInputQuestion) {
          // TEXT_INPUT: Text mit richtiger Option vergleichen
          const correctOpt = Array.isArray(q.options)
            ? q.options.find(o => o.isCorrect)
            : null
          const correctText = (correctOpt?.optionText || '').trim()
          this.textInputCorrectAnswer = correctText

          const userText = (this.textInputAnswer || '').trim()

          const norm = s =>
            s
              .toLowerCase()
              .replace(/\s+/g, ' ')
              .trim()

          if (correctText && norm(userText) === norm(correctText)) {
            isCorrect = true
          }
        } else {
          // MULTIPLE_CHOICE / TRUE_FALSE
          const chosen = Array.isArray(q.options)
            ? q.options.find(o => o.optionId === this.selectedOptionId)
            : null
          isCorrect = !!(chosen && chosen.isCorrect)
        }

        if (isCorrect) {
          this.singleplayerStore.score++
        }

        // Statistik speichern
        try {
          await axios.post('/api/saveSingleAnswer.php', {
            questionID: q.questionId,
            optionID: this.isTextInputQuestion ? null : this.selectedOptionId,
            isCorrect: isCorrect
          })
        } catch (e) {
          console.error('Fehler beim Speichern der Statistik:', e)
        }

        return
      }

      // 2. Weiter / Beenden
      const lastIndex = this.singleplayerStore.questions.length - 1
      if (this.singleplayerStore.currentQuestionIndex < lastIndex) {
        this.singleplayerStore.currentQuestionIndex++
        this.selectedOptionId = null
        this.isAnswerSubmitted = false
        this.textInputAnswer = ''
        this.textInputCorrectAnswer = ''
      } else {
        this.singleplayerStore.finished = true
      }
    },
    showSinglePlayerDifficultyModal() {
      this.isShowingSinglePlayerModal = true
    }
  }
}
</script>

<style scoped>
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.answer-option {
  padding: 12px 16px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  margin-bottom: 12px;
  cursor: pointer;
  user-select: none;
}
.answer-option.selected {
  border-color: #60a5fa;
  background: #0070ff;
}
.answer-option.correct {
  border-color: #22c55e;
  background: #00ca6a;
}
.answer-option.incorrect {
  border-color: #ef4444;
  background: #d30000;
}
.disabled-options .answer-option {
  pointer-events: none;
  opacity: 0.8;
}
.singleplayer-explanation {
  margin-top: 16px;
  padding: 12px;
  background: #f1f5f9;
  border-radius: 8px;
  font-size: 14px;
}
</style>
