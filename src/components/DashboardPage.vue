<template>
  <DashboardNavbar></DashboardNavbar>
  <button @click.prevent="debug()">Debug</button>
  <!-- JK: Teleport wird genutzt, um das SinglePlayerDifficultyModal über die DashboardPage zu lagern. Der Boolean 
  isShowingSinglePlayerModal ist dafür verantwortlich und wird über die vue.js directive v-model für das SinglePlayerDifficultyModal 
  bearbeitbar gemacht -->
  <Teleport to="body">
    <div v-if="isShowingSinglePlayerModal" class="modal">
      <singleplayer-difficulty-modal v-model="isShowingSinglePlayerModal"></singleplayer-difficulty-modal>
    </div>
    <div v-if="isShowingCreateQuizRoomModal" class="modal">
      <create-quiz-room-modal v-model="isShowingCreateQuizRoomModal"></create-quiz-room-modal>
    </div>
  </Teleport>
  <div class="container">
    <div
      style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
      <h1>📊 Wirtschaftsinformatik Quiz</h1>
      <div>
        <button class="btn btn-primary" @click.prevent="showQuestionPage()">📝 Fragen</button>
        <button class="btn btn-primary" @click.prevent="showSinglePlayerDifficultyModal()">🎮 Einzelspieler</button>
        <button class="btn btn-primary" @click.prevent="showCreateQuizRoomModal()">➕ Neuen Raum erstellen</button>
      </div>
    </div>

    <div
      style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 32px;">
      <div class="card">
        <h3 style="margin-bottom: 16px;">🔗 Raum beitreten</h3>
        <div style="display: flex; gap: 12px;">
          <input type="text" id="joinCode" v-model="joinCode" class="form-input" placeholder="Code eingeben"
            style="flex: 1;">
          <button class="btn btn-primary" @click.prevent="joinRoomByCode()">Beitreten</button>
        </div>
        <div id="joinError" style="display: none;" class="alert alert-error"></div>
      </div>

      <div class="card">
        <h3 style="margin-bottom: 16px;">📈 Deine Statistiken</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; text-align: center;">
          <div>
            <div style="font-size: 24px; font-weight: 700; color: #28a745;">
              {{ getUserStatsFromID(sessionStore.userID).correctAnswers }}
            </div>
            <div style="color: #666;">Richtige Antworten</div>
          </div>
          <div>
            <div style="font-size: 24px; font-weight: 700; color: #dc3545;">
              {{ getUserStatsFromID(sessionStore.userID).wrongAnswers }}
            </div>
            <div style="color: #666;">Falsche Antworten</div>
          </div>
        </div>
      </div>
    </div>

    <h2>🏠 Verfügbare Räume</h2>

    <div id="roomsList">
      <div v-if="rooms.length === 0" class="card" style="text-align: center; padding: 48px;">
        <h3>🎮 Erstellen Sie Ihren ersten Quiz-Raum!</h3>
        <p style="color: #666; margin: 16px 0;">Laden Sie Kommilitonen zu einem Wirtschaftsinformatik-Quiz ein.</p>
        <button class="btn btn-primary" @click.prevent="showCreateQuizRoomModal()">
          🚀 Ersten Raum erstellen
        </button>
      </div>
      <div v-for="room in rooms" class="card"
        style="border: 2px solid #007bff; margin-bottom: 16px; position: relative;">
        <div style="cursor: pointer;" @click.prevent="enterRoom(room.id)">
          <div style="display: flex; justify-content: space-between; margin-bottom: 12px; align-items: flex-start;">
            <div>
              <h3>{{ room.name }} <small style="font-weight: normal; color: #666;">Ersteller:
                  {{ getUserNameFromHostID(room.hostID) }}</small></h3>
              <span :class="`difficulty-${room.difficulty}`"
                style="padding: 4px 8px; border-radius: 12px; font-size: 11px; margin-right: 8px;">
                {{ getDifficultyText(room.difficulty) }}
              </span>
            </div>
            <span style="background: #007bff; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
              {{ room.gameMode === 'cooperative' ? '🤝 Kooperativ' : '⚔️ Kompetitiv' }}
            </span>
          </div>
          <p><strong>🔑 Code:</strong> {{ room.code }}</p>
          <p><strong>👥 Teilnehmer:</strong> {{ room.participants.length }}/{{ room.maxParticipants }}</p>
          <p><strong>❓ Fragen:</strong> {{ room.questions.length }}</p>
        </div>
        <button v-if="room.hostID === this.sessionStore.userID" @click.prevent="deleteRoom(room.id)"
          class="btn btn-danger"
          style="position: absolute; bottom: 12px; right: 12px; padding: 8px 12px; font-size: 14px;">
          🗑️ Raum löschen
        </button>
        <button v-if="room.hostID != sessionStore.userID" @click.prevent="leaveRoom(room.id)"
          class="btn btn-danger"
          style="position: absolute; bottom: 12px; right: 12px; padding: 8px 12px; font-size: 14px;">
          🚪Raum verlassen
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { useSessionStore } from '@/stores/session'
import router from '@/router/index'
import DashboardNavbar from './DashboardNavbar.vue';
import SingleplayerDifficultyModal from './SingleplayerDifficultyModal.vue'
import CreateQuizRoomModal from './CreateQuizRoomModal.vue'
import users from '../files/users.json' // <DATENBANK>
import rooms from '../files/rooms.json' // <DATENBANK>

export default {
  components: {
    DashboardNavbar, SingleplayerDifficultyModal, CreateQuizRoomModal
  },
  data() {
    const sessionStore = useSessionStore()

    return {
      sessionStore,
      users,
      rooms,
      isShowingSinglePlayerModal: false,
      isShowingCreateQuizRoomModal: false,
      joinCode: ''
    }
  },
  methods: {
    debug() {
      console.log(this.getUserStatsFromID(this.sessionStore.userID))
    },
    showSinglePlayerDifficultyModal() {
      this.isShowingSinglePlayerModal = true;
    },
    showCreateQuizRoomModal() {
      this.isShowingCreateQuizRoomModal = true;
      console.log("showCreateQuizRoomModal Called")
    },
    showQuestionPage() {
      router.push('/questions')
    },
    getUserNameFromHostID(hostID) {
      const foundUser = this.users.find(user => user.userID === hostID);
      return foundUser.first_name;
    },
    getUserStatsFromID(ID) {
      const foundUser = this.users.find(user => user.userID === ID);
      return foundUser.stats;
    },
    getDifficultyText(difficulty) {
      switch (difficulty) {
        case 'easy': return 'Leicht';
        case 'medium': return 'Mittel';
        case 'hard': return 'Schwer';
        default: return difficulty;
      }
    },
    enterRoom(roomID) {
      const room = this.rooms.find(room => room.id === roomID);
      if (room) {
        alert("🎯 Quiz-Raum " + room.name + " betreten!\n\n📊 Schwierigkeit: " + this.getDifficultyText(room.difficulty) + "\n🎮 Modus: " + (room.gameMode === 'cooperative' ? 'Kooperativ - gemeinsam lernen' : 'Kompetitiv - gegeneinander antreten') + "\n❓" + room.questions.length + " Fragen verfügbar");
        //JK: To-Do: Enter Multiplayermode
      }
    },
    joinRoomByCode() {
      const room = this.rooms.find(room => room.code == this.joinCode)
      if (room) {
        alert("🎉 Raum " + room.name + " beigetreten!\n📊 Schwierigkeit: " + this.getDifficultyText(room.difficulty));
        //JK: To-Do: Enter Multiplayermode
      }
      else {
        alert("❌ Raum nicht gefunden");
      }
    },
    deleteRoom(roomID) { // <DATENBANK>
      if (confirm('Möchten Sie diesen Raum wirklich löschen?')) {
        // Entferne Raum aus Datenbank
        alert('Raum wurde gelöscht');
      }
    },
    leaveRoom(roomID){

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