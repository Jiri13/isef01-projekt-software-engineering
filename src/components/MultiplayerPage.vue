<template>
  <div>
    <nav class="navbar">
      <div class="container navbar-content">
        <div class="navbar-brand">🎮 Multiplayer Raum: {{ room ? room.name : 'Lade...' }}</div>
        <div class="nav-links">
          <span v-if="room" class="badge" style="margin-right:12px;">Code: {{ room.code }}</span>
          <button class="btn btn-secondary" @click="leaveRoom">Verlassen</button>
        </div>
      </div>
    </nav>

    <div class="container" style="margin-top: 40px; max-width: 800px;">

      <div v-if="loading" style="text-align: center; padding: 40px;">
        <h2>⏳ Lade Raumdaten...</h2>
      </div>

      <div v-else-if="error" class="card" style="text-align: center; color: red;">
        <h3>❌ Fehler</h3>
        <p>{{ error }}</p>
        <button class="btn btn-primary" @click="$router.push('/')">Zurück zum Dashboard</button>
      </div>

      <div v-else-if="quizFinished" class="card" style="text-align: center;">
        <h2 style="color: #28a745; margin-bottom: 24px;">🎉 Quiz beendet!</h2>

        <p style="font-size: 1.2rem;">Du hast <strong>{{ score }}</strong> Fragen richtig beantwortet.</p>

        <div style="margin-top: 24px; display:flex; gap:12px; justify-content:center;">
          <button class="btn btn-secondary" @click="showStatistics">
            {{ isShowingStatistics ? 'Statistik verbergen' : '📊 Ergebnis anzeigen' }}
          </button>
          <button class="btn btn-primary" @click="$router.push('/')">🏠 Zum Dashboard</button>
        </div>

        <div v-if="isShowingStatistics" style="margin-top:24px; border-top:1px solid #eee; padding-top:16px;">

          <div v-if="room.gameMode === 'cooperative'" style="background: #e3f2fd; padding: 20px; border-radius: 8px;">
            <h3 style="color: #0d47a1;">🤝 Team-Leistung</h3>
            <p style="font-size: 1.1rem; margin-top: 10px;">
              Gemeinsam habt ihr erreicht:
            </p>
            <div style="font-size: 3rem; font-weight: bold; color: #007bff; margin: 10px 0;">
              {{ roomStats.teamTotalPoints }} <span style="font-size: 1.5rem;">Punkte</span>
            </div>
            <p style="color: #666;">
              Fantastische Zusammenarbeit! Jeder Punkt zählt für das Team.
            </p>
          </div>

          <div v-else>
            <h4 style="margin-bottom: 12px;">🏆 Rangliste</h4>
            <table style="width:100%; text-align:left; border-collapse: collapse;">
              <thead>
              <tr style="border-bottom:2px solid #ddd; background: #f9f9f9;">
                <th style="padding: 8px;">Rang</th>
                <th style="padding: 8px;">Name</th>
                <th style="padding: 8px; text-align: right;">Punkte</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="(p, i) in roomStats.leaderboard" :key="i" style="border-bottom:1px solid #eee;">
                <td style="padding: 8px;">
                  <span v-if="i===0">🥇</span>
                  <span v-else-if="i===1">🥈</span>
                  <span v-else-if="i===2">🥉</span>
                  <span v-else>{{ i + 1 }}.</span>
                </td>
                <td style="padding: 8px;">
                  {{ p.first_name }} {{ p.last_name }}
                  <span v-if="Number(p.userID) === Number(sessionStore.userID)" style="color:#007bff; font-weight:bold;">(Du)</span>
                </td>
                <td style="padding: 8px; text-align: right; font-weight: bold;">
                  {{ p.points }}
                </td>
              </tr>
              </tbody>
            </table>
            <p v-if="!roomStats.leaderboard || roomStats.leaderboard.length === 0">Keine Daten verfügbar.</p>
          </div>

        </div>
      </div>

      <div v-else-if="currentQuestion" class="card">

        <div style="display: flex; justify-content: space-between; margin-bottom: 16px; color: #666;">
          <span>Frage {{ currentQuestionIndex + 1 }} von {{ room.questions.length }}</span>
          <span>Punkte: {{ score }}</span>
        </div>

        <h3 style="margin-bottom: 24px; min-height: 60px;">
          {{ currentQuestion.text }}
        </h3>

        <div v-if="currentQuestion.type === 'text_input'">
          <input
              v-model="textInputAnswer"
              type="text"
              class="form-input"
              placeholder="Deine Antwort eingeben..."
              :disabled="reveal"
              @keyup.enter="submitAnswer"
          />
          <small style="color:#666;">Bestätigen mit Enter oder Button unten</small>
        </div>

        <div v-else>
          <div
              v-for="(opt, index) in currentQuestion.options"
              :key="index"
              class="answer-option"
              :class="{
              'selected': selectedAnswer === index,
              'correct': reveal && index === currentQuestion.correctAnswer,
              'incorrect': reveal && selectedAnswer === index && index !== currentQuestion.correctAnswer
            }"
              @click="!reveal ? selectAnswer(index) : null"
          >
            <span style="font-weight:bold; margin-right:8px;">{{ String.fromCharCode(65 + index) }})</span>
            {{ opt.text }}
          </div>
        </div>

        <div v-if="reveal" style="margin-top: 24px; padding: 16px; border-radius: 8px; text-align: center;"
             :style="{ background: lastAnswerCorrect ? '#d4edda' : '#f8d7da', color: lastAnswerCorrect ? '#155724' : '#721c24' }">
          <strong>{{ feedback }}</strong>
          <div v-if="currentQuestion.explanation" style="margin-top:8px; font-size:0.9rem; color:#555;">
            ℹ️ {{ currentQuestion.explanation }}
          </div>
        </div>

        <div style="margin-top: 24px; text-align: right;">
          <button v-if="!reveal" class="btn btn-primary" @click="submitAnswer" :disabled="!canSubmit">
            Antwort bestätigen
          </button>
          <button v-else class="btn btn-primary" @click="nextQuestion">
            {{ currentQuestionIndex < room.questions.length - 1 ? 'Nächste Frage ➡️' : 'Ergebnisse anzeigen 🏁' }}
          </button>
        </div>
      </div>

    </div>

    <ChatWidget v-if="room && room.id" :roomID="room.id" />
  </div>
</template>

<script>
import axios from 'axios'
import { useSessionStore } from '@/stores/session'
import ChatWidget from './ChatWidget.vue'

export default {
  components: {
    ChatWidget
  },
  data() {
    return {
      sessionStore: useSessionStore(),
      room: null,
      loading: true,
      error: null,

      currentQuestionIndex: 0,
      selectedAnswer: null,
      textInputAnswer: '',

      reveal: false,
      lastAnswerCorrect: false,
      feedback: '',
      score: 0,

      // Stats
      isShowingStatistics: false,
      roomStats: {
        leaderboard: [],
        teamTotalPoints: 0 // NEU: Initialwert
      }
    }
  },
  computed: {
    currentQuestion() {
      if (!this.room || !this.room.questions) return null
      return this.room.questions[this.currentQuestionIndex]
    },
    quizFinished() {
      return this.room && this.currentQuestionIndex >= this.room.questions.length
    },
    canSubmit() {
      if (this.currentQuestion.type === 'text_input') {
        return this.textInputAnswer.trim().length > 0
      }
      return this.selectedAnswer !== null
    }
  },
  mounted() {
    this.loadRoom()
  },
  methods: {
    async loadRoom() {
      let id = this.$route.params.id
      if (!id && history.state?.roomID) id = history.state.roomID
      if (!id && this.sessionStore.currentRoomID) id = this.sessionStore.currentRoomID

      if (!id) {
        this.error = "Keine Raum-ID gefunden. Bitte über das Dashboard beitreten."
        this.loading = false
        return
      }
      this.sessionStore.currentRoomID = id

      try {
        const res = await axios.get('/api/getRoom.php', { params: { roomID: id } })

        if (res.data && res.data.room) {
          this.room = res.data.room
          // Normalisierung des gameMode (Datenbank liefert oft unterschiedliche cases)
          if(this.room.gameMode) this.room.gameMode = this.room.gameMode.toLowerCase();

          this.trackGameStart()
        } else {
          this.error = "Raum konnte nicht geladen werden (Daten leer)."
        }
      } catch (e) {
        console.error(e)
        this.error = "Fehler beim Laden des Raumes."
      } finally {
        this.loading = false
      }
    },

    async trackGameStart() {
      if(!this.room) return;
      try {
        await axios.post('/api/trackGameStart.php', {
          roomID: this.room.id,
          userID: this.sessionStore.userID
        });
      } catch(e) { console.warn("Track start failed", e); }
    },

    selectAnswer(index) {
      this.selectedAnswer = index
    },

    async submitAnswer() {
      const q = this.currentQuestion
      if (!q) return

      let correct = false
      let playedOptionID = null

      if (q.type === 'text_input') {
        const userAnswer = this.textInputAnswer.trim().toLowerCase()
        let correctAnswerText = ''
        if (q.options && q.options.length > 0) {
          correctAnswerText = q.options[0].text.trim().toLowerCase()
          playedOptionID = q.options[0].id
        }
        correct = (userAnswer === correctAnswerText && userAnswer !== '')
      } else {
        correct = (this.selectedAnswer === q.correctAnswer)
        if (this.selectedAnswer !== null && q.options && q.options[this.selectedAnswer]) {
          playedOptionID = q.options[this.selectedAnswer].id
        }
      }

      if (correct) this.score++

      const qID = q.id || q.questionID;
      if (qID) {
        axios.post('/api/submitMultiplayerAnswer.php', {
          roomID: this.room.id,
          userID: this.sessionStore.userID,
          questionID: qID,
          optionID: playedOptionID,
          isCorrect: correct
        }).catch(e => console.error("Stats Error", e));
      }

      this.reveal = true
      this.lastAnswerCorrect = correct
      this.feedback = correct ? '✅ Richtig!' : `❌ Falsch. Richtige Antwort: ${this.getCorrectLabel()}`
    },

    getCorrectLabel() {
      const q = this.currentQuestion
      if (!q) return ''
      if (q.type === 'text_input') {
        return (q.options && q.options.length > 0) ? q.options[0].text : '???'
      }
      if (typeof q.correctAnswer === 'number' && q.options && q.options[q.correctAnswer]) {
        return q.options[q.correctAnswer].text
      }
      return 'Unbekannt'
    },

    nextQuestion() {
      if (this.currentQuestionIndex < this.room.questions.length - 1) {
        this.currentQuestionIndex++
        this.resetTurn()
      } else {
        this.currentQuestionIndex++
      }
    },

    resetTurn() {
      this.reveal = false
      this.selectedAnswer = null
      this.textInputAnswer = ''
      this.feedback = ''
    },

    async showStatistics() {
      // Toggle
      if (this.isShowingStatistics) {
        this.isShowingStatistics = false;
        return;
      }

      this.isShowingStatistics = true;
      try {
        const res = await axios.get('/api/getRoomDetailsStats.php', {
          params: { roomID: this.room.id, userID: this.sessionStore.userID }
        })
        this.roomStats = res.data
      } catch(e) { console.error(e) }
    },

    leaveRoom() {
      this.$router.push('/')
    }
  }
}
</script>

<style scoped>
/* CSS Styles bleiben wie gehabt */
.navbar { background: #fff; border-bottom: 1px solid #ddd; padding: 1rem 0; margin-bottom: 20px; }
.container { width: 90%; max-width: 1200px; margin: 0 auto; }
.navbar-content { display: flex; justify-content: space-between; align-items: center; }
.navbar-brand { font-size: 1.2rem; font-weight: bold; color: #007bff; }
.card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 24px; }
.form-input { width: 100%; padding: 12px; font-size: 1rem; border: 1px solid #ccc; border-radius: 4px; }
.answer-option { padding: 16px; margin-bottom: 12px; border: 2px solid #eee; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
.answer-option:hover { background: #f9f9f9; border-color: #ccc; }
.answer-option.selected { border-color: #007bff; background: #e7f1ff; }
.answer-option.correct { border-color: #28a745; background: #d4edda; color: #155724; }
.answer-option.incorrect { border-color: #dc3545; background: #f8d7da; color: #721c24; }
.btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
.btn-primary { background: #007bff; color: white; }
.btn-secondary { background: #6c757d; color: white; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.badge { background: #eee; padding: 4px 8px; border-radius: 4px; font-size: 0.9rem; }
</style>