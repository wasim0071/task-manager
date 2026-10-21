<script setup>
import { reactive } from 'vue';

const props = defineProps({
    task: { type: Object, default: null },
    saving: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    formError: { type: String, default: '' },
});

const emit = defineEmits(['submit', 'cancel']);

const form = reactive({
    title: props.task?.title ?? '',
    description: props.task?.description ?? '',
    priority: props.task?.priority ?? 'medium',
    due_date: props.task?.due_date ?? '',
});

function fieldError(name) {
    const value = props.errors?.[name];

    return Array.isArray(value) ? value[0] : '';
}

function submit() {
    emit('submit', {
        title: form.title.trim(),
        description: form.description.trim(),
        priority: form.priority,
        due_date: form.due_date || null,
    });
}
</script>

<template>
    <section class="card" aria-labelledby="task-form-heading">
        <div class="card-head">
            <h2 id="task-form-heading">{{ task ? 'Edit task' : 'Add a task' }}</h2>
            <p>{{ task ? 'Update the details, then save.' : 'Title and priority are required.' }}</p>
        </div>

        <form id="task-form" class="stack" autocomplete="off" @submit.prevent="submit">
            <p v-if="formError" class="banner" role="alert">{{ formError }}</p>

            <div class="field">
                <div class="field-label">
                    <label for="task-title">Title</label>
                    <span>{{ form.title.length }}/160</span>
                </div>
                <input
                    id="task-title"
                    v-model="form.title"
                    type="text"
                    maxlength="160"
                    required
                    :aria-invalid="Boolean(fieldError('title'))"
                    :aria-describedby="fieldError('title') ? 'task-title-error' : undefined"
                >
                <p v-if="fieldError('title')" id="task-title-error" class="field-error">{{ fieldError('title') }}</p>
            </div>

            <div class="field">
                <label for="task-description">Description</label>
                <textarea
                    id="task-description"
                    v-model="form.description"
                    rows="4"
                    maxlength="2000"
                    :aria-invalid="Boolean(fieldError('description'))"
                />
                <p v-if="fieldError('description')" class="field-error">{{ fieldError('description') }}</p>
            </div>

            <div class="split">
                <div class="field">
                    <label for="task-priority">Priority</label>
                    <select id="task-priority" v-model="form.priority" :aria-invalid="Boolean(fieldError('priority'))">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                    <p v-if="fieldError('priority')" class="field-error">{{ fieldError('priority') }}</p>
                </div>

                <div class="field">
                    <label for="task-due">Due date</label>
                    <input id="task-due" v-model="form.due_date" type="date" :aria-invalid="Boolean(fieldError('due_date'))">
                    <p v-if="fieldError('due_date')" class="field-error">{{ fieldError('due_date') }}</p>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit" :disabled="saving || !form.title.trim()">
                    {{ saving ? 'Saving…' : (task ? 'Save changes' : 'Add task') }}
                </button>
                <button v-if="task" class="btn btn-ghost" type="button" :disabled="saving" @click="emit('cancel')">
                    Cancel
                </button>
            </div>
        </form>
    </section>
</template>
