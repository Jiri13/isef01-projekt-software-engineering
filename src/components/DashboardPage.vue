<template>
  <NavbarComponent></NavbarComponent>

  <!-- JK: Teleport wird genutzt, um das SinglePlayerDifficultyModal über die DashboardPage zu lagern. Der Boolean 
  showSingleplayerModal ist dafür verantwortlich und wird über die vue.js directive v-model für das SinglePlayerDifficultyModal 
  bearbeitbar gemacht -->
  <Teleport to="body">
    <div v-if="showSingleplayerModal" class="modal">
      <singleplayer-difficulty-modal v-model="showSingleplayerModal"></singleplayer-difficulty-modal>
    </div>
  </Teleport>
  <div class="container">
    <div
      style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
      <h1>📊 Wirtschaftsinformatik Quiz</h1>
      <div>
        <button class="btn btn-primary" @click.prevent="showQuestionPage()">📝 Fragen</button>
        <button class="btn btn-primary" @click.prevent="showSinglePlayerDifficultyModal()">🎮 Einzelspieler</button>
        <button class="btn btn-primary" onclick="showCreateModal()">➕ Neuen Raum erstellen</button>
      </div>
    </div>

    <div
      style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 32px;">
      <div class="card">
        <h3 style="margin-bottom: 16px;">🔗 Raum beitreten</h3>
        <div style="display: flex; gap: 12px;">
          <input type="text" id="joinCode" class="form-input" placeholder="Code eingeben" style="flex: 1;">
          <button class="btn btn-primary" onclick="joinRoom()">Beitreten</button>
        </div>
        <div id="joinError" style="display: none;" class="alert alert-error"></div>
      </div>

      <div class="card">
        <h3 style="margin-bottom: 16px;">📈 Deine Statistiken</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; text-align: center;">
          <div>
            <div style="font-size: 24px; font-weight: 700; color: #28a745;">
              ${globalState.userStats.correctAnswers}
            </div>
            <div style="color: #666;">Richtige Antworten</div>
          </div>
          <div>
            <div style="font-size: 24px; font-weight: 700; color: #dc3545;">
              ${globalState.userStats.wrongAnswers}
            </div>
            <div style="color: #666;">Falsche Antworten</div>
          </div>
        </div>
      </div>
    </div>

    <h2>🏠 Verfügbare Räume</h2>

    <div id="roomsList">
      ${globalState.rooms.length === 0 ? `
      <div class="card" style="text-align: center; padding: 48px;">
        <h3>🎮 Erstellen Sie Ihren ersten Quiz-Raum!</h3>
        <p style="color: #666; margin: 16px 0;">Laden Sie Kommilitonen zu einem Wirtschaftsinformatik-Quiz ein.</p>
        <button class="btn btn-primary" onclick="showCreateModal()">
          🚀 Ersten Raum erstellen
        </button>
      </div>
      ` : globalState.rooms.map(room => `
      <div class="card" style="border: 2px solid #007bff; margin-bottom: 16px; position: relative;">
        <div style="cursor: pointer;" onclick="enterRoom('${room.id}')">
          <div style="display: flex; justify-content: space-between; margin-bottom: 12px; align-items: flex-start;">
            <div>
              <h3>${room.name} <small style="font-weight: normal; color: #666;">(Ersteller:
                  ${findUserNameById(room.hostId)})</small></h3>
              <span class="badge ${getDifficultyClass(room.difficulty)}"
                style="padding: 4px 8px; border-radius: 12px; font-size: 11px; margin-right: 8px;">
                ${getDifficultyLabel(room.difficulty)}
              </span>
            </div>
            <span style="background: #007bff; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
              ${room.gameMode === 'cooperative' ? '🤝 Kooperativ' : '⚔️ Kompetitiv'}
            </span>
          </div>
          <p><strong>🔑 Code:</strong> ${room.code}</p>
          <p><strong>👥 Teilnehmer:</strong> ${room.participants.length}/${room.maxParticipants}</p>
          <p><strong>❓ Fragen:</strong> ${room.questions.length}</p>
        </div>
        ${room.hostId === globalState.user.id ? `
        <button onclick="event.stopPropagation(); deleteRoom('${room.id}')" class="btn btn-danger"
          style="position: absolute; bottom: 12px; right: 12px; padding: 8px 12px; font-size: 14px;">
          🗑️ Raum löschen
        </button>
        ` : ''}
      </div>
      `).join('')}
    </div>
  </div>

  <div id="modalContainer"></div>
</template>

<script>
import { useSessionStore } from '@/stores/session'
import router from '@/router/index'
import NavbarComponent from './NavbarComponent.vue';
import SingleplayerDifficultyModal from './SingleplayerDifficultyModal.vue'

export default {
  components: {
    NavbarComponent, SingleplayerDifficultyModal
  },
  data() {
    const sessionStore = useSessionStore()

    return {
      sessionStore,
      showSingleplayerModal: false
    }
  },
  methods: {
    showSinglePlayerDifficultyModal() {
      this.showSingleplayerModal = true
    },
    logout() {
      this.sessionStore.loggedIn = false;
      router.push('/')
    },
    showQuestionPage() {
      router.push('/questions')
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
</style>