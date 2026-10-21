<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { dueLabel, isOverdue, shortStamp } from '../utils/dates';

const props = defineProps({
    task: { type: Object, required: true },
    locked: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle', 'edit', 'remove']);

const confirming = ref(false);
const expanded = ref(false);
const canToggle = ref(false);
const copyEl = ref(null);
const overdue = computed(() => isOverdue(props.task));
const description = computed(() => props.task.description?.trim() ?? '');

async function measure() {
    canToggle.value = false;
    await nextTick();

    const el = copyEl.value;

    if (!el) {
        return;
    }

    const lineHeight = parseFloat(getComputedStyle(el).lineHeight) || 24;
    const overflows = el.scrollHeight > lineHeight * 3 + 1;
    canToggle.value = overflows;

    if (!overflows) {
        expanded.value = false;
    }
}

function toggleDescription() {
    expanded.value = !expanded.value;
}

function remove() {
    confirming.value = false;
    emit('remove', props.task);
}

onMounted(measure);
watch(description, () => {
    expanded.value = false;
    measure();
});
</script>

<template>
    <article
        class="task"
        :data-priority="task.priority"
        :class="{ 'is-done': task.status === 'completed', 'is-overdue': overdue }"
    >
        <button
            class="check"
            type="button"
            :aria-pressed="task.status === 'completed'"
            :aria-label="task.status === 'completed' ? `Mark ${task.title} as open` : `Mark ${task.title} as completed`"
            :disabled="locked"
            @click="emit('toggle', task)"
        />

        <div class="task-body">
            <h3>{{ task.title }}</h3>
            <p v-if="description" ref="copyEl" :class="{ 'is-clamped': canToggle && !expanded }">{{ task.description }}</p>
            <button
                v-if="canToggle"
                type="button"
                class="read-more"
                :aria-expanded="expanded"
                @click="toggleDescription"
            >
                {{ expanded ? 'Show less' : 'Read more' }}
            </button>
            <div class="meta">
                <span class="pill" :data-priority="task.priority">{{ task.priority }}</span>
                <span v-if="task.due_date" :class="{ overdue: overdue }">{{ dueLabel(task.due_date) }}</span>
                <span v-if="overdue" class="overdue">Overdue</span>
                <span v-if="task.status === 'completed' && task.completed_at">Completed {{ shortStamp(task.completed_at) }}</span>
            </div>
        </div>

        <div v-if="confirming" class="confirm">
            <p>Delete this task?</p>
            <button type="button" class="btn btn-ghost" :disabled="locked" @click="confirming = false">Keep</button>
            <button type="button" class="btn btn-danger" :disabled="locked" @click="remove">Delete</button>
        </div>
        <div v-else class="task-actions">
            <button type="button" class="btn btn-ghost" :disabled="locked" @click="emit('edit', task)">Edit</button>
            <button type="button" class="btn btn-danger" :disabled="locked" @click="confirming = true">Delete</button>
        </div>
    </article>
</template>
