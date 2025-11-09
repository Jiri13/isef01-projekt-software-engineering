<template>
  <div class="modal-inner" style="max-width:640px;width:100%;background:white;border-radius:8px;padding:20px;">
    <h2>🏠 Neuen Quiz-Raum erstellen</h2>
    <form @submit.prevent="createRoom">
      <div class="form-group">
        <label class="form-label">Raumname</label>
        <input class="form-input" v-model="form.name" required />
      </div>

      <div class="form-group">
        <label class="form-label">Spielmodus</label>
        <select class="form-input" v-model="form.gameMode">
          <option value="cooperative">Kooperativ</option>
          <option value="competitive">Kompetitiv</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Schwierigkeitsgrad</label>
        <select class="form-input" v-model="form.difficulty">
          <option value="easy">Leicht</option>
          <option value="medium">Mittel</option>
          <option value="hard">Schwer</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Anzahl Fragen</label>
        <input type="number" min="1" max="50" class="form-input" v-model.number="form.questionCount" />
      </div>

      <div style="display:flex;gap:12px;margin-top:16px;">
        <button type="button" class="btn btn-secondary" @click="close">Abbrechen</button>
        <button type="submit" class="btn btn-primary">Raum erstellen</button>
      </div>
    </form>
  </div>
</template>

<script>
import questionsFile from '@/files/questions.json'
import { useSessionStore } from '@/stores/session'

export default {
  emits: ['update:modelValue','created'],
  props: {
    modelValue: { type: Boolean, default: false }
  },
  data() {
    return {
      form: {
        name: '',
        gameMode: 'cooperative',
        difficulty: 'easy',
        questionCount: 6,
        maxParticipants: 8
      }
    }
  },
  methods: {
    close() {
      this.$emit('update:modelValue', false)
    },
    createRoom() {
      // basic validation
      if (!this.form.name.trim()) {
        alert('Bitte einen Raumnamen angeben.');
        return;
      }

      const session = useSessionStore();

      // select questions from local questions.json matching difficulty
      const candidates = (questionsFile || []).filter(q => (q.difficulty || '').toLowerCase() === (this.form.difficulty || 'easy'))
      const shuffled = [...candidates].sort(() => Math.random() - 0.5)
      const selected = shuffled.slice(0, this.form.questionCount)

      const newRoom = {
        id: Date.now(),
        name: this.form.name.trim(),
        code: Math.random().toString(36).substr(2,6).toUpperCase(),
        hostID: session.userID || 0,
        participants: [session.userID || 0],
        gameMode: this.form.gameMode,
        maxParticipants: this.form.maxParticipants,
        questions: selected.map(q => ({
          id: q.questionID ?? q.id ?? Date.now(),
          text: q.question_text ?? q.text,
          type: q.type,
          options: q.options || [],
          correctAnswer: q.correctAnswer,
          explanation: q.explanation,
          timeLimit: q.timeLimit,
          difficulty: q.difficulty
        })),
        difficulty: this.form.difficulty,
        createdAt: new Date().toISOString()
      }

      // emit created — DashboardPage listens and will refresh rooms
      this.$emit('created', newRoom)
      this.$emit('update:modelValue', false)
    }
  }
}
</script>

<style scoped>
.modal-inner { }
</style>

