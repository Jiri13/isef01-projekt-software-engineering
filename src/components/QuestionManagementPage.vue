<template>
    <DashboardNavbar></DashboardNavbar>
    <div class="card" style="cursor: pointer; margin-bottom: 12px; border: 1px solid #ddd;"
        onclick="editQuestion(${q.id})">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="flex: 1;">
                <strong style="font-size: 16px; color: #007bff;">${q.text}</strong>
                <span class="badge ${getDifficultyClass(q.difficulty)}"
                    style="padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 8px;">
                    ${getDifficultyLabel(q.difficulty)}
                </span><br>
                <small style="color: #666;">
                    Typ: ${q.type === 'multiple_choice' ? 'Multiple Choice' : q.type === 'true_false' ? 'Wahr/Falsch' :
                    'Texteingabe'} |
                    Zeitlimit: ${q.timeLimit}s
                </small>
                ${q.explanation ? `<br><small style="color: #28a745;"><em>Mit Erklärung</em></small>` : ''}
            </div>
            <button class="btn btn-danger" onclick="event.stopPropagation(); deleteQuestion(${q.id})"
                style="padding: 6px 12px; font-size: 12px;">
                🗑️ Löschen
            </button>
        </div>
    </div>
</template>

<script>
import DashboardNavbar from './DashboardNavbar.vue'; //rename

export default {
    components: {
        DashboardNavbar
    }
}
</script>