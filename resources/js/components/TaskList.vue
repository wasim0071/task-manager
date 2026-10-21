<script setup>
import TaskItem from './TaskItem.vue';

defineProps({
    tasks: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    locked: { type: Boolean, default: false },
    filtered: { type: Boolean, default: false },
});

defineEmits(['toggle', 'edit', 'remove']);
</script>

<template>
    <div class="list-wrap" :class="{ 'is-dim': loading && tasks.length }" :aria-busy="loading">
        <div v-if="loading && !tasks.length" class="skeleton" aria-hidden="true">
            <span /><span /><span />
        </div>

        <ul v-else-if="tasks.length" class="task-list">
            <li v-for="task in tasks" :key="task.id">
                <TaskItem
                    :task="task"
                    :locked="locked"
                    @toggle="$emit('toggle', $event)"
                    @edit="$emit('edit', $event)"
                    @remove="$emit('remove', $event)"
                />
            </li>
        </ul>

        <div v-else class="empty">
            <h3>{{ filtered ? 'No tasks match these filters.' : 'Nothing on the board yet.' }}</h3>
            <p>{{ filtered ? 'Clear the search or choose another status.' : 'Add the first task and it will show up here.' }}</p>
        </div>
    </div>
</template>
