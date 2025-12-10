<template>
  <div class="chat-widget-container">
    <div v-if="isOpen" class="chat-window card">
      <div class="chat-header">
        <span>💬 Raum Chat</span>
        <button class="close-btn" @click="toggleChat">✕</button>
      </div>

      <div class="chat-messages" ref="msgContainer">
        <div v-if="loading" style="text-align:center; padding:10px; color:#666;">Lade...</div>
        <div v-else-if="messages.length === 0" style="text-align:center; padding:10px; color:#999;">
          Noch keine Nachrichten. Schreib was!
        </div>

        <div
            v-for="msg in messages"
            :key="msg.messageID"
            class="message-item"
            :class="{ 'own-message': Number(msg.userID) === Number(sessionStore.userID) }"
        >
          <div class="message-sender">{{ msg.first_name }}</div>
          <div class="message-bubble">
            {{ msg.message }}
          </div>
          <div class="message-time">{{ formatTime(msg.created_at) }}</div>
        </div>
      </div>

      <div class="chat-input-area">
        <input
            v-model="newMessage"
            @keyup.enter="sendMessage"
            type="text"
            placeholder="Nachricht..."
            class="form-input chat-input"
        />
        <button class="btn btn-primary send-btn" @click="sendMessage">➤</button>
      </div>
    </div>

    <div class="floating-button" @click="toggleChat">
      <img src="/Designer.png" alt="Chat öffnen" />
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { useSessionStore } from '@/stores/session';

export default {
  props: {
    roomID: {
      type: [Number, String],
      required: true
    }
  },
  data() {
    return {
      sessionStore: useSessionStore(),
      isOpen: false,
      messages: [],
      newMessage: '',
      loading: false,
      pollingInterval: null
    };
  },
  mounted() {
    // Starte Polling, wenn Komponente geladen wird
    this.fetchMessages();
    this.pollingInterval = setInterval(() => {
      if (this.isOpen) {
        this.fetchMessages(true); // true = silent update (kein Lade-Spinner)
      }
    }, 3000); // Alle 3 Sekunden aktualisieren
  },
  beforeUnmount() {
    if (this.pollingInterval) clearInterval(this.pollingInterval);
  },
  methods: {
    toggleChat() {
      this.isOpen = !this.isOpen;
      if (this.isOpen) {
        this.fetchMessages();
        this.$nextTick(() => this.scrollToBottom());
      }
    },
    async fetchMessages(silent = false) {
      if (!this.roomID) return;
      if (!silent) this.loading = true;

      try {
        const res = await axios.get('/api/getChatMessages.php', {
          params: { roomID: this.roomID }
        });

        // Prüfen ob neue Nachrichten da sind, um zu scrollen
        const oldLength = this.messages.length;
        this.messages = res.data || [];

        if (this.messages.length > oldLength && this.isOpen) {
          this.$nextTick(() => this.scrollToBottom());
        }

      } catch (e) {
        console.error("Chat Error:", e);
      } finally {
        if (!silent) this.loading = false;
      }
    },
    async sendMessage() {
      if (!this.newMessage.trim()) return;

      const msgToSend = this.newMessage;
      this.newMessage = ''; // Sofort leeren für UX

      try {
        await axios.post('/api/sendChatMessage.php', {
          roomID: this.roomID,
          userID: this.sessionStore.userID,
          message: msgToSend
        });
        // Sofort neu laden
        await this.fetchMessages(true);
        this.scrollToBottom();
      } catch (e) {
        console.error("Send Error:", e);
        alert("Nachricht konnte nicht gesendet werden.");
        this.newMessage = msgToSend; // Text wiederherstellen
      }
    },
    scrollToBottom() {
      const container = this.$refs.msgContainer;
      if (container) {
        container.scrollTop = container.scrollHeight;
      }
    },
    formatTime(sqlDate) {
      if (!sqlDate) return '';
      const date = new Date(sqlDate);
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
  }
};
</script>

<style scoped>
.chat-widget-container {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

/* Floating Button Styles */
.floating-button {
  width: 60px;
  height: 60px;
  cursor: pointer;
  transition: transform 0.2s;
  background: white;
  border-radius: 50%;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  overflow: hidden;
  border: 2px solid #007bff;
}

.floating-button:hover {
  transform: scale(1.1);
}

.floating-button img {
  width: 100%;
  height: 100%;
  object-fit: cover; /* Passt das Bild in den Kreis ein */
}

/* Chat Window Styles */
.chat-window {
  width: 320px;
  height: 450px;
  display: flex;
  flex-direction: column;
  margin-bottom: 15px; /* Abstand zum Button */
  padding: 0 !important; /* Card Padding überschreiben */
  overflow: hidden;
  border: 1px solid #ccc;
  box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

.chat-header {
  background: #007bff;
  color: white;
  padding: 10px 15px;
  font-weight: bold;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.close-btn {
  background: none;
  border: none;
  color: white;
  font-size: 1.2rem;
  cursor: pointer;
}

.chat-messages {
  flex: 1;
  background: #f1f1f1;
  padding: 10px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.message-item {
  max-width: 80%;
  display: flex;
  flex-direction: column;
  align-self: flex-start; /* Standard: Links */
}

.message-item.own-message {
  align-self: flex-end; /* Eigene: Rechts */
  align-items: flex-end;
}

.message-sender {
  font-size: 0.75rem;
  color: #666;
  margin-bottom: 2px;
  margin-left: 4px;
}

.own-message .message-sender {
  margin-right: 4px;
}

.message-bubble {
  background: white;
  padding: 8px 12px;
  border-radius: 12px;
  border-bottom-left-radius: 2px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.1);
  font-size: 0.95rem;
}

.own-message .message-bubble {
  background: #dcf8c6; /* WhatsApp Grün Style */
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 2px;
}

.message-time {
  font-size: 0.65rem;
  color: #999;
  margin-top: 2px;
  text-align: right;
}

.chat-input-area {
  padding: 10px;
  background: white;
  border-top: 1px solid #ddd;
  display: flex;
  gap: 8px;
}

.chat-input {
  flex: 1;
  padding: 8px;
  font-size: 0.9rem;
}

.send-btn {
  padding: 0 12px;
  font-size: 1.2rem;
  margin: 0;
}
</style>