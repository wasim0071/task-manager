-- IQT-FSD-2026 task board
-- Engine: MySQL 8+ / MariaDB. Laravel also creates users, sessions,
-- cache, jobs, and password_reset_tokens from the framework migrations.
-- The application data lives in tasks.

CREATE TABLE tasks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(160) NOT NULL,
    description TEXT NULL,
    priority VARCHAR(20) NOT NULL DEFAULT 'medium',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    due_date DATE NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX tasks_priority_index (priority),
    INDEX tasks_status_index (status),
    INDEX tasks_due_date_index (due_date),
    INDEX tasks_status_due_date_index (status, due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
