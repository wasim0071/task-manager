<script setup>
import { computed, ref, watch } from 'vue';
import TaskForm from './components/TaskForm.vue';
import TaskList from './components/TaskList.vue';
import WeatherPanel from './components/WeatherPanel.vue';
import { useTasks } from './composables/useTasks';
import { useToast } from './composables/useToast';
import { limits } from './utils/limits';

const {
    tasks,
    stats,
    meta,
    loading,
    saving,
    formSaving,
    pendingId,
    listError,
    formError,
    fieldErrors,
    filters,
    fetchTasks,
    createTask,
    updateTask,
    toggleTask,
    removeTask,
    clearFormFeedback,
} = useTasks();

const { toasts, push } = useToast();

function shortTitle(title) {
    const text = String(title || '').trim();

    return text.length > 42 ? `${text.slice(0, 42)}…` : text;
}

const editing = ref(null);
const formKey = ref(0);
const searchInput = ref('');
let searchTimer = 0;

const filtered = computed(() => Boolean(filters.search || filters.status || filters.priority));
const locked = computed(() => saving.value || pendingId.value !== null);
const sortValue = computed({
    get: () => `${filters.sort}:${filters.direction}`,
    set: (value) => {
        const [sort, direction] = value.split(':');
        filters.sort = sort;
        filters.direction = direction;
        filters.page = 1;
    },
});

watch(searchInput, (value) => {
    if (value.length > limits.search) {
        searchInput.value = value.slice(0, limits.search);

        return;
    }

    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
        filters.search = value.trim().slice(0, limits.search);
        filters.page = 1;
    }, 250);
});

function setStatus(status) {
    filters.status = status;
    filters.page = 1;
}

function setPriority(event) {
    filters.priority = event.target.value;
    filters.page = 1;
}

function setPage(page) {
    filters.page = page;
}

async function onSubmit(payload) {
    const ok = editing.value
        ? await updateTask(editing.value.id, payload)
        : await createTask(payload);

    if (ok) {
        editing.value = null;
        formKey.value += 1;
    }
}

function onEdit(task) {
    clearFormFeedback();
    editing.value = task;
    formKey.value += 1;
    requestAnimationFrame(() => {
        document.getElementById('task-form')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
    push(`Editing ${shortTitle(task.title)}.`);
}

function cancelEdit() {
    editing.value = null;
    formKey.value += 1;
    clearFormFeedback();
    push('Edit cancelled.');
}
</script>

<template>
    <div class="page">
        <header class="topbar">
            <div class="wrap topbar-inner">
                <div class="brand">
                    <h1>IQT-FSD-2026</h1>
                    <p class="lede">Task board for work you intend to finish. Stored in MySQL and served over a versioned REST API.</p>
                </div>
                <dl class="stats">
                    <div>
                        <dt>Open</dt>
                        <dd>{{ stats.open }}</dd>
                    </div>
                    <div>
                        <dt>Done</dt>
                        <dd>{{ stats.completed }}</dd>
                    </div>
                    <div>
                        <dt>Total</dt>
                        <dd>{{ stats.total }}</dd>
                    </div>
                </dl>
            </div>
        </header>

        <main class="wrap layout">
            <section class="board" aria-labelledby="board-heading">
                <div class="board-head">
                    <div>
                        <h2 id="board-heading">Tasks</h2>
                        <p v-if="meta.total" class="count" aria-live="polite">
                            Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }}
                        </p>
                    </div>
                    <a class="jump" href="#task-form">Add a task</a>
                </div>

                <div class="toolbar">
                    <div class="field search-field">
                        <label class="sr-only" for="task-search">Search tasks</label>
                        <input
                            id="task-search"
                            v-model="searchInput"
                            type="search"
                            :maxlength="limits.search"
                            placeholder="Search title or description"
                            autocomplete="off"
                            :aria-describedby="'task-search-limit'"
                        >
                        <span id="task-search-limit" class="sr-only">{{ limits.search }} characters at most</span>
                    </div>

                    <div class="segment" role="group" aria-label="Filter by status">
                        <button type="button" :aria-pressed="filters.status === ''" @click="setStatus('')">All</button>
                        <button type="button" :aria-pressed="filters.status === 'pending'" @click="setStatus('pending')">Open</button>
                        <button type="button" :aria-pressed="filters.status === 'completed'" @click="setStatus('completed')">Done</button>
                    </div>

                    <div class="field">
                        <label class="sr-only" for="priority-filter">Priority</label>
                        <select id="priority-filter" :value="filters.priority" @change="setPriority">
                            <option value="">Any priority</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>

                    <div class="field">
                        <label class="sr-only" for="sort-tasks">Sort</label>
                        <select id="sort-tasks" v-model="sortValue">
                            <option value="created_at:desc">Newest</option>
                            <option value="created_at:asc">Oldest</option>
                            <option value="due_date:asc">Due soon</option>
                            <option value="priority:asc">Priority</option>
                            <option value="title:asc">Title</option>
                        </select>
                    </div>
                </div>

                <p v-if="listError" class="banner" role="alert">
                    {{ listError }}
                    <button type="button" class="btn btn-ghost" @click="fetchTasks">Retry</button>
                </p>

                <TaskList
                    :tasks="tasks"
                    :loading="loading"
                    :locked="locked"
                    :filtered="filtered"
                    @toggle="toggleTask"
                    @edit="onEdit"
                    @remove="removeTask"
                />

                <nav v-if="meta.last_page > 1" class="pager" aria-label="Task pages">
                    <button type="button" class="btn btn-ghost" :disabled="meta.current_page <= 1 || loading" @click="setPage(meta.current_page - 1)">Previous</button>
                    <span>Page {{ meta.current_page }} of {{ meta.last_page }}</span>
                    <button type="button" class="btn btn-ghost" :disabled="meta.current_page >= meta.last_page || loading" @click="setPage(meta.current_page + 1)">Next</button>
                </nav>
            </section>

            <aside class="panel">
                <TaskForm
                    :key="formKey"
                    :task="editing"
                    :saving="formSaving"
                    :errors="fieldErrors"
                    :form-error="formError"
                    @submit="onSubmit"
                    @cancel="cancelEdit"
                />
                <WeatherPanel />
            </aside>
        </main>

        <footer class="wrap site-footer">
            <p>IQT-FSD-2026</p>
            <p>Laravel · Vue 3 · MySQL · Open-Meteo</p>
        </footer>

        <div class="toaster" aria-live="polite">
            <p
                v-for="item in toasts"
                :key="item.id"
                class="toast"
                :class="item.tone === 'error' ? 'toast-error' : 'toast-success'"
                role="status"
            >{{ item.message }}</p>
        </div>
    </div>
</template>
