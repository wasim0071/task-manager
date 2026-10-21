import { ref } from 'vue';

const toasts = ref([]);
let nextId = 0;

export function useToast() {
    function dismiss(id) {
        toasts.value = toasts.value.filter((item) => item.id !== id);
    }

    function push(message, tone = 'success') {
        const id = ++nextId;
        toasts.value = [...toasts.value, { id, message, tone }].slice(-3);

        window.setTimeout(() => dismiss(id), 2800);

        return id;
    }

    return { toasts, push, dismiss };
}
