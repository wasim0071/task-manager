function parts(isoDate) {
    const [year, month, day] = isoDate.split('-').map(Number);

    return new Date(year, month - 1, day);
}

export function weekday(isoDate) {
    if (!isoDate) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, { weekday: 'short' }).format(parts(isoDate));
}

export function dueLabel(isoDate) {
    if (!isoDate) {
        return '';
    }

    const date = parts(isoDate);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const diff = Math.round((date - today) / 86400000);
    const formatted = new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: 'numeric',
    }).format(date);

    if (diff === 0) {
        return 'Due today';
    }

    if (diff === 1) {
        return 'Due tomorrow';
    }

    if (diff === -1) {
        return 'Due yesterday';
    }

    return `Due ${formatted}`;
}

export function isOverdue(task) {
    if (!task?.due_date || task.status === 'completed') {
        return false;
    }

    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

    return parts(task.due_date) < today;
}

export function shortStamp(iso) {
    if (!iso) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: 'numeric',
    }).format(new Date(iso));
}
