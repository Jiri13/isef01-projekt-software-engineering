<template>
  <DashboardNavbar />

  <Teleport to="body">
    <div v-if="isShowingSinglePlayerModal" class="modal">
      <singleplayer-difficulty-modal v-model="isShowingSinglePlayerModal" />
    </div>
    <div v-if="isShowingCreateQuizRoomModal" class="modal">
      <create-quiz-room-modal
          v-model="isShowingCreateQuizRoomModal"
          @created="onRoomCreated"/>
    </div>
  </Teleport>

  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;flex-wrap:wrap;gap:16px;">
      <h1>📊 Wirtschaftsinformatik Quiz</h1>
      <div>
        <button class="btn btn-primary" @click.prevent="showQuestionPage()">📝 Fragen</button>
        <button class="btn btn-primary" @click.prevent="showQuizManagementPage()">🗂️ Quiz verwalten</button>
        <button class="btn btn-primary" @click.prevent="showSinglePlayerDifficultyModal()">🎮 Einzelspieler</button>
        <button class="btn btn-primary" @click.prevent="showCreateQuizRoomModal()">➕ Neuen Raum erstellen</button>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-bottom:32px;">
      <div class="card">
        <h3 style="margin-bottom:16px;">🔗 Raum beitreten</h3>
        <div style="display:flex;gap:12px;">
          <input type="text" id="joinCode" v-model="joinCode" class="form-input" placeholder="Code eingeben" style="flex:1;">
          <button class="btn btn-primary" @click.prevent="joinRoomByCode()">Beitreten</button>
        </div>
        <div id="joinError" style="display:none;" class="alert alert-error"></div>
      </div>

      <div class="card">
        <h3 style="margin-bottom:16px;">📈 Deine Statistiken</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;text-align:center;">
          <div>
            <div style="font-size:24px;font-weight:700;color:#28a745;">
              {{ getUserStatsFromID(sessionStore.userID).correctAnswers }}
            </div>
            <div style="color:#666;">Richtige Antworten</div>
          </div>
          <div>
            <div style="font-size:24px;font-weight:700;color:#dc3545;">
              {{ getUserStatsFromID(sessionStore.userID).wrongAnswers }}
            </div>
            <div style="color:#666;">Falsche Antworten</div>
          </div>
        </div>
      </div>
    </div>

    <h2>🏠 Deine Räume ({{ myRooms.length }})</h2>
    <div v-if="loadingRooms" class="card" style="padding:16px;">⏳ Räume werden geladen…</div>
    <div v-if="roomsError" class="card" style="padding:16px;color:#dc3545;">{{ roomsError }}</div>

    <div id="roomsList">
      <div v-if="myRooms.length === 0" class="card" style="text-align:center;padding:48px;">
        <h3>🎮 Erstellen Sie Ihren ersten Quiz-Raum!</h3>
        <p style="color:#666;margin:16px 0;">Laden Sie Kommilitonen zu einem Wirtschaftsinformatik-Quiz ein.</p>
        <button class="btn btn-primary" @click.prevent="showCreateQuizRoomModal()">🚀 Ersten Raum erstellen</button>
      </div>

      <!-- NUR diese Liste behalten -->
      <div v-for="room in myRooms" :key="room.id" class="card" style="border:2px solid #007bff;margin-bottom:16px;position:relative;">
        <div style="cursor:pointer;" @click.prevent="enterRoom(room.id)">
          <div style="display:flex;justify-content:space-between;margin-bottom:12px;align-items:flex-start;">
            <div>
              <h3>
                {{ room.name }}
                <small style="font-weight:normal;color:#666;">Ersteller: {{ getUserNameFromHostID(room.hostID) }}</small>
              </h3>
              <span :class="`difficulty-${room.difficulty}`" style="padding:4px 8px;border-radius:12px;font-size:11px;margin-right:8px;">
                {{ getDifficultyText(room.difficulty) }}
              </span>
            </div>
            <span style="background:#007bff;color:white;padding:4px 12px;border-radius:20px;font-size:12px;">
              {{ room.gameMode === 'cooperative' ? '🤝 Kooperativ' : '⚔️ Kompetitiv' }}
            </span>
          </div>
          <p><strong>🔑 Code:</strong> {{ room.code }}</p>
          <p><strong>👥 Teilnehmer:</strong> {{ room.participants.length }}/{{ room.maxParticipants }}</p>
          <p><strong>❓ Fragen:</strong> {{ room.questions.length }}</p>
        </div>

        <button
            v-if="room.hostID === sessionStore.userID"
            @click.prevent="deleteRoom(room.id)"
            class="btn btn-danger"
            style="position:absolute;bottom:12px;right:12px;padding:8px 12px;font-size:14px;"
        >
          🗑️ Raum löschen
        </button>
        <button
            v-if="room.hostID != sessionStore.userID"
            @click.prevent="leaveRoom(room.id)"
            class="btn btn-danger"
            style="position:absolute;bottom:12px;right:12px;padding:8px 12px;font-size:14px;"
        >
          🚪Raum verlassen
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { useSessionStore } from '@/stores/session'
import router from '@/router/index'
import DashboardNavbar from './DashboardNavbar.vue'
import SingleplayerDifficultyModal from './SingleplayerDifficultyModal.vue'
import CreateQuizRoomModal from './CreateQuizRoomModal.vue'
import users from '../files/users.json'   // <DATENBANK>
import axios from 'axios'

export default {
  components: {
    DashboardNavbar,
    SingleplayerDifficultyModal,
    CreateQuizRoomModal
  },

  data() {
    const sessionStore = useSessionStore()
    return {
      sessionStore,
      users,
      rooms: '',
      isShowingSinglePlayerModal: false,
      isShowingCreateQuizRoomModal: false,
      joinCode: '',
      loadingRooms: false,
      roomsError: null
    }
  },

  computed: {
    myRooms() {
      const me = this.sessionStore?.userID
      // console.log("SessionID: " + this.sessionStore?.userID)
      if (!me) return []

      return (this.rooms || []).filter(r => {
        const participantIds = Array.isArray(r?.participants)
            ? r.participants.map(p => (typeof p === 'object' ? p.userID : p))
            : []

        const isHost = r?.hostID === me
        const isMember = participantIds.includes(me)

        return isHost || isMember
      })
    }
  },

  async mounted() {
    // Load persisted rooms from localStorage (if any) so created rooms survive reloads
    const saved = localStorage.getItem('quiz_rooms')
    if (saved) {
      try {
        this.rooms = JSON.parse(saved)
      } catch (e) {
        console.warn('Failed to parse saved quiz_rooms from localStorage', e)
      }
    }

    await this.fetchMyRooms()
  },
  watch: {
    // persist rooms to localStorage whenever they change
    rooms: {
      handler(newRooms) {
        try {
          localStorage.setItem('quiz_rooms', JSON.stringify(newRooms || []))
        } catch (e) {
          console.warn('Failed to persist rooms to localStorage', e)
        }
      },
      deep: true
    }
  },
  methods: {
    async fetchMyRooms() {
      try {
        this.loadingRooms = true
        this.roomsError = null
        const uid = this.sessionStore.userID
        const { data } = await axios.get('/api/listRooms.php', { params: { userID: uid } })

        // In deine UI-Form bringen
        this.rooms = (data || []).map(r => ({
          ...r,
          participants: Array.isArray(r.participants) ? r.participants : [],
          // Dein Template nutzt room.questions.length → wir bauen ein Dummy-Array mit der Anzahl
          questions: new Array(r.questionsCount ?? 0).fill(null),
          maxParticipants: r.maxParticipants ?? 10
        }))
      } catch (e) {
        console.error(e)
        if (!this.rooms || this.rooms.length === 0) {
          this.roomsError = 'Konnte Räume nicht laden.'
        } else {
          this.roomsError = null
        }
      } finally {
        this.loadingRooms = false
      }
    },
    async onRoomCreated(room) {
      // If backend exists, we refresh; otherwise, insert room locally so it appears immediately
      if (room) {
        // normalize participants and questions array
        room.participants = Array.isArray(room.participants) ? room.participants : []
        room.questions = Array.isArray(room.questions) ? room.questions : []
        // prepend to rooms list so the host sees it immediately
        this.rooms = [room, ...(this.rooms || [])]
      }
      try { await this.fetchMyRooms() } catch (e) { /* ignore fetch errors */ }
    },
    showSinglePlayerDifficultyModal() {
      this.isShowingSinglePlayerModal = true
    },
    showCreateQuizRoomModal() {
      this.isShowingCreateQuizRoomModal = true
      console.log('showCreateQuizRoomModal Called')
    },
    showQuestionPage() {
      router.push('/questions')
    },
    showQuizManagementPage() {
      router.push('/quizmanagement')
    },
    getUserNameFromHostID(hostID) {
      const foundUser = (this.users || []).find(user => user.userID === hostID)
      return foundUser ? foundUser.first_name : `User #${hostID}`
    },
    getUserStatsFromID(ID) {
      const foundUser = (this.users || []).find(user => user.userID === ID)
      return foundUser?.stats || { correctAnswers: 0, wrongAnswers: 0 }
    },
    getDifficultyText(difficulty) {
      switch (difficulty) {
        case 'easy': return 'Leicht'
        case 'medium': return 'Mittel'
        case 'hard': return 'Schwer'
        default: return difficulty
      }
    },
    enterRoom(roomID) {
      const room = (this.rooms || []).find(room => room.id === roomID)
      if (room) {
        // Persist the room into session-local storage so the multiplayer page can load it
        try {
          localStorage.setItem(`room_${roomID}`, JSON.stringify(room))
        } catch (e) {
          console.warn('Could not cache room to localStorage', e)
        }

        // Navigate to multiplayer page; pass room in history state as well (best-effort)
        this.$router.push({ name: 'Multiplayer', params: { id: roomID }, state: { room } }).catch(() => {})
      }
    },
    async joinRoomByCode() {
      const code = (this.joinCode || '').trim()
      if (!code) {
        alert('Bitte einen Raumcode eingeben.')
        return
      }
      try {
        const { data } = await axios.post('/api/joinRoom.php', {
          code,
          userID: this.sessionStore.userID
        })

        if (data.alreadyParticipant) {
          alert('✅ Du bist bereits Teilnehmer dieses Raums.')
        } else {
          alert('🎉 Erfolgreich beigetreten!')
        }

        // Liste neu laden, damit der Raum in "meine Räume" erscheint
        await this.fetchMyRooms()
        this.joinCode = ''
      } catch (e) {
        const msg = e?.response?.data?.error || 'Beitreten fehlgeschlagen.'
        if (msg === 'room not found') {
          alert('❌ Raum nicht gefunden. Bitte Code prüfen.')
        } else if (msg === 'room is full') {
          alert('🚫 Raum ist voll.')
        } else {
          alert('❌ ' + msg)
        }
        console.error('JOIN ERROR', e?.response?.status, e?.response?.data)
      }
    },
    async deleteRoom(roomID) {
      if (!confirm('Möchten Sie diesen Raum wirklich löschen?')) return;
      try {
        const res = await axios.post('/api/deleteRoom.php', {
          roomID,
          userID: this.sessionStore.userID
        });

        // If backend confirms deletion, remove locally and refresh list
        if (res && res.data && (res.data.ok || res.data.deleted)) {
          this.rooms = (this.rooms || []).filter(r => String(r.id) !== String(roomID))
          try { localStorage.setItem('quiz_rooms', JSON.stringify(this.rooms)) } catch (e) { /* ignore */ }
          console.log('DELETE OK', res.data);
          alert('Raum wurde gelöscht');
          // try to refresh from server for consistency
          try { await this.fetchMyRooms() } catch (e) { /* ignore */ }
          return
        }

        // Backend returned unexpected payload — fallback to local deletion
        console.warn('Backend delete returned unexpected response, falling back to local remove', res?.data)
        this.rooms = (this.rooms || []).filter(r => String(r.id) !== String(roomID))
        try { localStorage.setItem('quiz_rooms', JSON.stringify(this.rooms)) } catch (e) { /* ignore */ }
        alert('Raum lokal entfernt (Backend-Antwort fehlerhaft).')

      } catch (e) {
        // Backend call failed (server down / network). Remove room locally so host can manage rooms offline.
        console.warn('DELETE ERROR - falling back to local remove', e)
        this.rooms = (this.rooms || []).filter(r => String(r.id) !== String(roomID))
        try { localStorage.setItem('quiz_rooms', JSON.stringify(this.rooms)) } catch (err) { console.warn('Failed to persist rooms after local delete', err) }
        alert('Raum lokal entfernt (kein Server).');
      }
    },
    async leaveRoom(roomID){
      if (!confirm('Möchten Sie diesen Raum wirklich verlassen?')) return;
      try {
        const res = await axios.post('/api/leaveRoom.php', {
          roomID,
          userID: this.sessionStore.userID
        });
        console.log('LEAVE OK', res.data);
        alert('Raum wurde verlassen!');
        await this.fetchMyRooms();
      } catch (e) {
        // ausführliches Debug
        const status = e.response?.status;
        const data = e.response?.data;
        console.error('LEAVE ERROR', status, data, e);

        alert(
            '❌ Fehler beim verlassen\n'
            + 'Status: ' + (status ?? 'unbekannt') + '\n'
            + 'Antwort: ' + (data ? JSON.stringify(data) : 'keine')
        );
      }
    }
  }
}
</script>

<style scoped>
.modal{
  position:fixed;top:0;left:0;width:100%;height:100%;
  background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;
}
</style>
