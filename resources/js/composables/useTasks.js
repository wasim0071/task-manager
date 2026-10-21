import { reactive, ref, watch } from 'vue';
import client from '../api/client';

function readError(error) {
    const data = error.response?.data;

    if (data?.errors) {
        return { fields: data.errors, message: '' };
    }

    return {
        fields: {},
        message: data?.message || 'The request failed. Check your connection and try again.',
    };
}

export function useTasks() {
    const tasks = ref([]);
    const stats = ref({ total: 0, open: 0, completed: 0 });
    const meta = ref({ current_page: 1, last_page: 1, total: 0, from: null, to: null });
    const loading = ref(false);
    const saving = ref(false);
    const formSaving = ref(false);
    const pendingId = ref(null);
    const listError = ref('');
    const formError = ref('');
    const fieldErrors = ref({});
    const toast = ref('');
    const filters = reactive({
        search: '',
        status: '',
        priority: '',
        sort: 'created_at',
        direction: 'desc',
        page: 1,
    });

    let toastTimer = 0;
    let requestId = 0;

    function notify(message) {
        toast.value = message;
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => {
            if (toast.value === message) {
                toast.value = '';
            }
        }, 2800);
    }

    function clearFormFeedback() {
        formError.value = '';
        fieldErrors.value = {};
    }

    function params() {
        const query = {
            page: filters.page,
            per_page: 6,
            sort: filters.sort,
            direction: filters.direction,
        };

        if (filters.search.trim()) {
            query.search = filters.search.trim();
        }

        if (filters.status) {
            query.status = filters.status;
        }

        if (filters.priority) {
            query.priority = filters.priority;
        }

        return query;
    }

    async function fetchTasks() {
        const id = ++requestId;
        loading.value = true;
        listError.value = '';

        try {
            const { data } = await client.get('/tasks', { params: params() });

            if (id !== requestId) {
                return;
            }

            const last = data.meta?.last_page || 1;

            if (filters.page > last) {
                filters.page = last;

                return;
            }

            tasks.value = data.data ?? [];
            stats.value = data.stats ?? stats.value;
            meta.value = data.meta ?? meta.value;
        } catch (error) {
            if (id !== requestId) {
                return;
            }

            listError.value = readError(error).message || 'The task list could not be loaded.';
        } finally {
            if (id === requestId) {
                loading.value = false;
            }
        }
    }

    async function send(request, success, target) {
        saving.value = true;
        formSaving.value = target === 'form';

        if (target === 'form') {
            clearFormFeedback();
        } else {
            listError.value = '';
        }

        try {
            await request();
            notify(success);
            await fetchTasks();

            return true;
        } catch (error) {
            const parsed = readError(error);

            if (target === 'form') {
                fieldErrors.value = parsed.fields;
                formError.value = parsed.message
                    || (Object.keys(parsed.fields).length ? 'Check the highlighted fields and try again.' : 'The task could not be saved.');
            } else {
                listError.value = parsed.message || 'The task could not be updated.';
            }

            return false;
        } finally {
            saving.value = false;
            formSaving.value = false;
            pendingId.value = null;
        }
    }

    function createTask(payload) {
        return send(async () => {
            await client.post('/tasks', payload);

            if (filters.page !== 1) {
                filters.page = 1;
            }
        }, 'Task added.', 'form');
    }

    function updateTask(id, payload) {
        return send(() => client.put(`/tasks/${id}`, payload), 'Task updated.', 'form');
    }

    function toggleTask(task) {
        pendingId.value = task.id;
        const status = task.status === 'completed' ? 'pending' : 'completed';
        const message = status === 'completed' ? 'Task completed.' : 'Task reopened.';

        return send(() => client.patch(`/tasks/${task.id}`, { status }), message, 'list');
    }

    function removeTask(task) {
        pendingId.value = task.id;

        return send(() => client.delete(`/tasks/${task.id}`), 'Task deleted.', 'list');
    }

    watch(filters, () => {
        fetchTasks();
    }, { deep: true, immediate: true });

    return {
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
        toast,
        filters,
        fetchTasks,
        createTask,
        updateTask,
        toggleTask,
        removeTask,
        clearFormFeedback,
    };
}
