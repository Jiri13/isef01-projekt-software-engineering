<template>
    <div class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 24px;">🏠 Neuen Quiz-Raum erstellen</h2>
            <form onsubmit="createRoom(event)">
                <div class="form-group">
                    <label class="form-label">Raum Name</label>
                    <input type="text" id="roomName" class="form-input" required
                        placeholder="Wirtschaftsinformatik Grundlagen">
                </div>

                <div class="form-group">
                    <label class="form-label">Schwierigkeitsgrad</label>
                    <select id="roomDifficulty" class="form-input">
                        <option value="easy">🟢 Leicht</option>
                        <option value="medium">🟡 Mittel</option>
                        <option value="hard">🔴 Schwer</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Spielmodus</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <label
                            style="cursor: pointer; padding: 16px; border: 2px solid #007bff; border-radius: 8px; text-align: center;">
                            <input type="radio" name="gameMode" value="cooperative" checked style="display: none;">
                            <div style="font-weight: 600; color: #007bff;">🤝 Kooperativ</div>
                        </label>
                        <label
                            style="cursor: pointer; padding: 16px; border: 2px solid #ddd; border-radius: 8px; text-align: center;">
                            <input type="radio" name="gameMode" value="competitive" style="display: none;">
                            <div style="font-weight: 600; color: #dc3545;">⚔️ Kompetitiv</div>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Max. Teilnehmer</label>
                    <input type="number" id="maxParticipants" class="form-input" value="8" min="2" max="20">
                </div>

                <div style="display: flex; gap: 12px;">
                    <button type="button" class="btn btn-secondary" @click.prevent="$emit('update:modelValue', false)"
                        style="flex: 1;">Abbrechen</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">✅ Raum erstellen</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
// JK: Für Prop-Manipulation mittels V-model https://vuejs.org/guide/components/v-model.html
// Notwendig, da das Modal als Child von DashboardPage.vue aufgerufen wird und somit über dessen Anzeige entscheidet. Somit muss
// das Modal den Wert in der Dashboardkomponenten ändern
export default {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    methods: {
        DebugFunction() {
            console.log("Hello");
            console.log(this.showSingleplayerModal)
        }
    }
}
</script>