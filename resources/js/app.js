import './bootstrap';
import * as bootstrap from 'bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.bootstrap = bootstrap;

Alpine.start();

const themeStorageKey = 'scalyn-theme-preference';

function getSystemTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function isTheme(theme) {
    return theme === 'light' || theme === 'dark';
}

function getResolvedTheme() {
    const root = document.documentElement;
    const source = root.dataset.scalynThemeSource || root.dataset.themeSource || 'guest';
    const preference = root.dataset.scalynThemePreference || 'system';
    let storedTheme = null;

    try {
        storedTheme = window.localStorage.getItem(themeStorageKey);
    } catch (error) {
        storedTheme = null;
    }

    if (source === 'account') {
        if (isTheme(preference)) {
            return preference;
        }
    }

    if (isTheme(storedTheme)) {
        return storedTheme;
    }

    return getSystemTheme();
}

function applyTheme(theme, persist = false) {
    if (!isTheme(theme)) {
        return;
    }

    document.documentElement.dataset.bsTheme = theme;
    document.documentElement.dataset.scalynThemeResolved = theme;

    if (persist) {
        try {
            window.localStorage.setItem(themeStorageKey, theme);
        } catch (error) {
            // Ignore storage failures and keep the current session theme.
        }
    }
}

function clearStoredTheme() {
    try {
        window.localStorage.removeItem(themeStorageKey);
    } catch (error) {
        // Ignore storage failures.
    }
}

function syncStoredThemeFromForm(form) {
    const selectedTheme = form.querySelector('input[name="theme_preference"]:checked')?.value
        || form.querySelector('[data-theme-preference-input]')?.value
        || null;

    if (selectedTheme === 'system') {
        clearStoredTheme();
        return;
    }

    if (isTheme(selectedTheme)) {
        try {
            window.localStorage.setItem(themeStorageKey, selectedTheme);
        } catch (error) {
            // Ignore storage failures.
        }
    }
}

function setLoadingState(form) {
    form.querySelectorAll('[data-rich-editor-editor]').forEach((editor) => {
        syncRichEditor(editor);
    });

    const submit = form.querySelector('button[type="submit"], input[type="submit"]');

    if (!submit || submit.disabled) {
        return;
    }

    submit.disabled = true;
    submit.dataset.loadingApplied = 'true';

    const loadingText = submit.getAttribute('data-loading-text') || 'Loading...';

    if (submit.tagName === 'BUTTON') {
        submit.dataset.originalHtml = submit.innerHTML;
        submit.innerHTML = `
            <span class="spinner-border spinner-border-sm loading-spinner me-2" aria-hidden="true"></span>
            <span>${loadingText}</span>
        `;
    } else {
        submit.dataset.originalValue = submit.value;
        submit.value = loadingText;
    }
}

function syncRichEditor(editor) {
    const targetId = editor.getAttribute('data-rich-editor-target');
    const target = targetId ? document.getElementById(targetId) : null;

    if (!target) {
        return;
    }

    target.value = editor.innerHTML.trim();
}

function initializeRichEditor(container) {
    const editor = container.querySelector('[data-rich-editor-editor]');
    const target = container.querySelector('[data-rich-editor-target]');

    if (!editor || !target) {
        return;
    }

    editor.addEventListener('input', () => {
        syncRichEditor(editor);
    });

    editor.addEventListener('blur', () => {
        syncRichEditor(editor);
    });

    container.querySelectorAll('[data-rich-editor-command]').forEach((button) => {
        button.addEventListener('click', () => {
            const command = button.getAttribute('data-rich-editor-command');
            const value = button.getAttribute('data-rich-editor-value') || null;

            editor.focus();

            if (command === 'createLink') {
                const url = window.prompt('Enter a link URL');
                if (!url) {
                    return;
                }

                document.execCommand(command, false, url);
            } else if (value) {
                document.execCommand(command, false, value);
            } else {
                document.execCommand(command, false);
            }

            syncRichEditor(editor);
        });
    });

    editor.addEventListener('paste', () => {
        window.setTimeout(() => syncRichEditor(editor), 0);
    });

    syncRichEditor(editor);
}

function getLocalDateInputValue(date = new Date()) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

window.addEventListener('DOMContentLoaded', () => {
    applyTheme(getResolvedTheme());

    document.querySelectorAll('[data-auto-open="true"]').forEach((element) => {
        bootstrap.Modal.getOrCreateInstance(element).show();
    });

    if (window.location.hash) {
        const tabTrigger = document.querySelector(`[data-bs-toggle="tab"][data-bs-target="${window.location.hash}"]`);

        if (tabTrigger) {
            bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
        }
    }

    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');

    document.querySelectorAll('[data-sidebar-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-open');
            if (sidebar && document.body.classList.contains('sidebar-open')) {
                sidebar.scrollTop = 0;
            }
        });
    });

    backdrop?.addEventListener('click', () => {
        document.body.classList.remove('sidebar-open');
    });

    sidebar?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 991.98px)').matches) {
                document.body.classList.remove('sidebar-open');
            }
        });
    });

    document.querySelectorAll('[data-rich-editor]').forEach((container) => {
        initializeRichEditor(container);
    });

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const form = button.closest('form');
            const currentTheme = document.documentElement.dataset.bsTheme || getResolvedTheme();
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
            const preferenceInput = form?.querySelector('[data-theme-preference-input]');

            if (preferenceInput) {
                preferenceInput.value = nextTheme;
            }

            applyTheme(nextTheme, true);
        });
    });

    const taskStatusModalElement = document.getElementById('task-status-modal');
    const taskStatusModal = taskStatusModalElement ? bootstrap.Modal.getOrCreateInstance(taskStatusModalElement) : null;
    const taskStatusForm = taskStatusModalElement?.querySelector('form');
    const taskStatusTaskId = taskStatusModalElement?.querySelector('[data-task-status-task-id]');
    const taskStatusTitle = taskStatusModalElement?.querySelector('[data-task-status-task-title]');
    const taskStatusClient = taskStatusModalElement?.querySelector('[data-task-status-task-client]');
    const taskStatusBadge = taskStatusModalElement?.querySelector('[data-task-status-task-badge]');
    const taskStatusSelect = taskStatusModalElement?.querySelector('[data-task-status-select]');
    const taskStatusDefaultTitle = taskStatusTitle?.textContent || 'Choose a task status from the table.';
    const taskStatusDefaultClient = taskStatusClient?.textContent || 'The modal will populate from the row you choose.';
    const taskStatusDefaultBadgeText = taskStatusBadge?.textContent || 'Not selected';
    const taskStatusDefaultBadgeClass = taskStatusBadge?.className || 'badge badge-soft';
    const taskStatusDefaultValue = taskStatusSelect?.value || 'open';

    const clearTaskStatusValidationState = () => {
        taskStatusForm?.querySelectorAll('.is-invalid').forEach((element) => {
            element.classList.remove('is-invalid');
        });

        taskStatusForm?.querySelectorAll('.invalid-feedback').forEach((element) => {
            element.style.display = 'none';
        });
    };

    document.querySelectorAll('[data-task-status-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            if (!taskStatusModal) {
                return;
            }

            clearTaskStatusValidationState();

            const title = trigger.getAttribute('data-task-title') || taskStatusDefaultTitle;
            const client = trigger.getAttribute('data-task-client') || taskStatusDefaultClient;
            const status = trigger.getAttribute('data-task-status') || taskStatusDefaultValue;
            const statusLabel = trigger.getAttribute('data-task-status-label') || taskStatusDefaultBadgeText;
            const statusClass = trigger.getAttribute('data-task-status-class') || 'badge badge-soft';
            const action = trigger.getAttribute('data-task-status-action') || '';

            if (taskStatusForm) {
                taskStatusForm.setAttribute('action', action);
            }

            if (taskStatusTaskId) {
                taskStatusTaskId.value = trigger.getAttribute('data-task-id') || '';
            }

            if (taskStatusTitle) {
                taskStatusTitle.textContent = title;
            }

            if (taskStatusClient) {
                taskStatusClient.textContent = client;
            }

            if (taskStatusBadge) {
                taskStatusBadge.className = `badge ${statusClass}`.trim();
                taskStatusBadge.textContent = statusLabel;
            }

            if (taskStatusSelect) {
                taskStatusSelect.value = status;
            }

            taskStatusModal.show();
        });
    });

    taskStatusModalElement?.addEventListener('hidden.bs.modal', () => {
        if (taskStatusForm) {
            taskStatusForm.setAttribute('action', '');
        }

        if (taskStatusTaskId) {
            taskStatusTaskId.value = '';
        }

        if (taskStatusTitle) {
            taskStatusTitle.textContent = taskStatusDefaultTitle;
        }

        if (taskStatusClient) {
            taskStatusClient.textContent = taskStatusDefaultClient;
        }

        if (taskStatusBadge) {
            taskStatusBadge.className = taskStatusDefaultBadgeClass;
            taskStatusBadge.textContent = taskStatusDefaultBadgeText;
        }

        if (taskStatusSelect) {
            taskStatusSelect.value = taskStatusDefaultValue;
        }

        clearTaskStatusValidationState();
    });

    const taskLogModalElement = document.getElementById('task-log-time-modal');
    const taskLogModal = taskLogModalElement ? bootstrap.Modal.getOrCreateInstance(taskLogModalElement) : null;
    const taskLogForm = taskLogModalElement?.querySelector('form');
    const taskLogTaskId = taskLogModalElement?.querySelector('[data-task-log-time-task-id]');
    const taskLogTitle = taskLogModalElement?.querySelector('[data-task-log-time-task-title]');
    const taskLogClient = taskLogModalElement?.querySelector('[data-task-log-time-task-client]');
    const taskLogStatusBadge = taskLogModalElement?.querySelector('[data-task-log-time-task-status-badge]');
    const taskLogStatusSelect = taskLogModalElement?.querySelector('[data-task-log-time-status]');
    const taskLogDateInput = taskLogModalElement?.querySelector('[data-task-log-time-date]');
    const taskLogHoursInput = taskLogModalElement?.querySelector('[data-task-log-time-hours]');
    const taskLogUserInput = taskLogModalElement?.querySelector('[data-task-log-time-user]');
    const taskLogNotesEditor = taskLogModalElement?.querySelector('[data-rich-editor-editor]');
    const taskLogDefaultTitle = taskLogTitle?.textContent || 'Choose a task from the table to log time.';
    const taskLogDefaultClient = taskLogClient?.textContent || 'The modal will populate from the row you choose.';
    const taskLogDefaultStatusText = taskLogStatusBadge?.textContent || 'Not selected';
    const taskLogDefaultStatusClass = taskLogStatusBadge?.className || 'badge badge-soft';
    const taskLogDefaultStatusValue = taskLogStatusSelect?.value || 'open';
    const taskLogDefaultDateValue = getLocalDateInputValue();
    const taskLogDefaultUserValue = taskLogUserInput?.value || '';
    const clearTaskLogNotes = () => {
        if (!taskLogNotesEditor) {
            return;
        }

        taskLogNotesEditor.innerHTML = '';
        syncRichEditor(taskLogNotesEditor);
    };
    const clearTaskLogValidationState = () => {
        taskLogForm?.querySelectorAll('.is-invalid').forEach((element) => {
            element.classList.remove('is-invalid');
        });

        taskLogForm?.querySelectorAll('.invalid-feedback').forEach((element) => {
            element.style.display = 'none';
        });
    };

    document.querySelectorAll('[data-task-log-time-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            if (!taskLogModal) {
                return;
            }

            clearTaskLogValidationState();

            const title = trigger.getAttribute('data-task-title') || taskLogDefaultTitle;
            const client = trigger.getAttribute('data-task-client') || taskLogDefaultClient;
            const status = trigger.getAttribute('data-task-status') || taskLogDefaultStatusValue;
            const statusLabel = trigger.getAttribute('data-task-status-label') || taskLogDefaultStatusText;
            const statusClass = trigger.getAttribute('data-task-status-class') || 'badge badge-soft';

            if (taskLogTaskId) {
                taskLogTaskId.value = trigger.getAttribute('data-task-id') || '';
            }

            if (taskLogTitle) {
                taskLogTitle.textContent = title;
            }

            if (taskLogClient) {
                taskLogClient.textContent = client;
            }

            if (taskLogStatusBadge) {
                taskLogStatusBadge.className = `badge ${statusClass}`.trim();
                taskLogStatusBadge.textContent = statusLabel;
            }

            if (taskLogStatusSelect) {
                taskLogStatusSelect.value = status;
            }

            if (taskLogDateInput) {
                taskLogDateInput.value = taskLogDefaultDateValue;
            }

            if (taskLogHoursInput) {
                taskLogHoursInput.value = '';
            }

            if (taskLogUserInput) {
                taskLogUserInput.value = taskLogDefaultUserValue;
            }

            clearTaskLogNotes();

            taskLogModal.show();
        });
    });

    taskLogModalElement?.addEventListener('hidden.bs.modal', () => {
        if (taskLogTaskId) {
            taskLogTaskId.value = '';
        }

        if (taskLogTitle) {
            taskLogTitle.textContent = taskLogDefaultTitle;
        }

        if (taskLogClient) {
            taskLogClient.textContent = taskLogDefaultClient;
        }

        if (taskLogStatusBadge) {
            taskLogStatusBadge.className = taskLogDefaultStatusClass;
            taskLogStatusBadge.textContent = taskLogDefaultStatusText;
        }

        if (taskLogStatusSelect) {
            taskLogStatusSelect.value = taskLogDefaultStatusValue;
        }

        if (taskLogDateInput) {
            taskLogDateInput.value = taskLogDefaultDateValue;
        }

        if (taskLogHoursInput) {
            taskLogHoursInput.value = '';
        }

        if (taskLogUserInput) {
            taskLogUserInput.value = taskLogDefaultUserValue;
        }

        clearTaskLogNotes();
        clearTaskLogValidationState();
    });

    const deleteModalElement = document.getElementById('delete-confirmation-modal');
    const deleteForm = document.getElementById('delete-confirmation-form');
    const deleteTitle = document.getElementById('delete-confirmation-title');
    const deleteMessage = document.getElementById('delete-confirmation-message');
    const deleteSubmit = document.getElementById('delete-confirmation-submit');
    const deleteMethod = document.getElementById('delete-confirmation-method');
    const deleteModal = deleteModalElement ? bootstrap.Modal.getOrCreateInstance(deleteModalElement) : null;

    document.querySelectorAll('[data-delete-confirm]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            if (!deleteModal || !deleteForm) {
                return;
            }

            const action = trigger.getAttribute('data-delete-action') || '';
            const title = trigger.getAttribute('data-delete-title') || 'Delete item';
            const message = trigger.getAttribute('data-delete-message') || 'Are you sure you want to delete this item? This action cannot be undone.';
            const submitLabel = trigger.getAttribute('data-delete-submit') || 'Delete';
            const method = (trigger.getAttribute('data-delete-method') || 'DELETE').toUpperCase();

            deleteForm.setAttribute('action', action);
            if (deleteMethod) {
                deleteMethod.value = method;
            }
            deleteTitle && (deleteTitle.textContent = title);
            deleteMessage && (deleteMessage.textContent = message);

            if (deleteSubmit) {
                deleteSubmit.innerHTML = `<i class="bi bi-trash me-1"></i>${submitLabel}`;
                deleteSubmit.setAttribute('data-loading-text', `${submitLabel}...`);
            }

            deleteModal.show();
        });
    });

    deleteModalElement?.addEventListener('hidden.bs.modal', () => {
        deleteForm?.setAttribute('action', '');
        if (deleteMethod) {
            deleteMethod.value = 'DELETE';
        }
    });

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => {
            syncStoredThemeFromForm(form);
            setLoadingState(form);
        });
    });
});
