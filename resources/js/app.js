import './bootstrap';
import * as bootstrap from 'bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.bootstrap = bootstrap;

Alpine.start();

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

function getRelativeDateRangeFromView(view, anchorDate = new Date()) {
    const normalizedDate = new Date(anchorDate.getFullYear(), anchorDate.getMonth(), anchorDate.getDate());

    if (view === 'daily') {
        return {
            from: getLocalDateInputValue(normalizedDate),
            to: getLocalDateInputValue(normalizedDate),
        };
    }

    if (view === 'weekly') {
        const weekStart = new Date(normalizedDate);
        const dayOffset = (weekStart.getDay() + 6) % 7;
        weekStart.setDate(weekStart.getDate() - dayOffset);

        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekEnd.getDate() + 6);

        return {
            from: getLocalDateInputValue(weekStart),
            to: getLocalDateInputValue(weekEnd),
        };
    }

    const monthStart = new Date(normalizedDate.getFullYear(), normalizedDate.getMonth(), 1);
    const monthEnd = new Date(normalizedDate.getFullYear(), normalizedDate.getMonth() + 1, 0);

    return {
        from: getLocalDateInputValue(monthStart),
        to: getLocalDateInputValue(monthEnd),
    };
}

function initializeRelativeDateRangeFilter(form) {
    const viewSelect = form.querySelector('[data-relative-date-range-view-select]') || form.querySelector('select[name="view"]');
    const fromInput = form.querySelector('[data-relative-date-range-from-input]') || form.querySelector('input[name="from"]');
    const toInput = form.querySelector('[data-relative-date-range-to-input]') || form.querySelector('input[name="to"]');

    if (!viewSelect || !fromInput || !toInput) {
        return;
    }

    const syncDateRange = () => {
        const range = getRelativeDateRangeFromView(viewSelect.value);
        fromInput.value = range.from;
        toInput.value = range.to;
    };

    viewSelect.addEventListener('change', syncDateRange);
}

window.addEventListener('DOMContentLoaded', () => {
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

    document.querySelectorAll('[data-timesheet-filter-form], [data-relative-date-range-form]').forEach((form) => {
        initializeRelativeDateRangeFilter(form);
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

    const taskBulkStatusForm = document.querySelector('[data-task-bulk-status-form]');
    const taskBulkStatusCheckboxes = Array.from(document.querySelectorAll('[data-task-bulk-status-checkbox]'));
    const taskBulkStatusSelectAll = document.querySelector('[data-task-bulk-status-select-all]');
    const taskBulkStatusSelect = taskBulkStatusForm?.querySelector('[data-task-bulk-status-select]');
    const taskBulkStatusCount = taskBulkStatusForm?.querySelector('[data-task-bulk-status-count]');
    const taskBulkStatusApply = taskBulkStatusForm?.querySelector('[data-task-bulk-status-apply]');
    const taskBulkStatusPanel = taskBulkStatusForm?.closest('[data-task-bulk-status-panel]');
    const taskBulkStatusDefaultSelectValue = taskBulkStatusSelect?.value || '';

    const syncTaskBulkStatusState = () => {
        const selectedCount = taskBulkStatusCheckboxes.filter((checkbox) => checkbox.checked).length;
        const hasVisibleTasks = taskBulkStatusCheckboxes.length > 0;

        if (taskBulkStatusCount) {
            taskBulkStatusCount.textContent = `${selectedCount} selected`;
        }

        if (taskBulkStatusSelectAll) {
            const allSelected = hasVisibleTasks && selectedCount === taskBulkStatusCheckboxes.length;
            taskBulkStatusSelectAll.checked = allSelected;
            taskBulkStatusSelectAll.indeterminate = selectedCount > 0 && !allSelected;
            taskBulkStatusSelectAll.disabled = !hasVisibleTasks;
        }

        if (taskBulkStatusApply) {
            const hasStatus = Boolean(taskBulkStatusSelect?.value);
            taskBulkStatusApply.disabled = selectedCount === 0 || !hasStatus;
        }

        if (taskBulkStatusPanel) {
            taskBulkStatusPanel.dataset.bulkStatusState = selectedCount > 0 ? 'active' : 'idle';
            taskBulkStatusPanel.classList.toggle('opacity-75', selectedCount === 0);
        }
    };

    taskBulkStatusSelectAll?.addEventListener('change', () => {
        const checked = taskBulkStatusSelectAll.checked;
        taskBulkStatusCheckboxes.forEach((checkbox) => {
            checkbox.checked = checked;
        });

        syncTaskBulkStatusState();
    });

    taskBulkStatusCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', syncTaskBulkStatusState);
    });

    taskBulkStatusSelect?.addEventListener('change', syncTaskBulkStatusState);

    taskBulkStatusForm?.addEventListener('submit', () => {
        if (taskBulkStatusApply) {
            taskBulkStatusApply.disabled = true;
        }
    });

    syncTaskBulkStatusState();

    const taskPriorityModalElement = document.getElementById('task-priority-modal');
    const taskPriorityModal = taskPriorityModalElement ? bootstrap.Modal.getOrCreateInstance(taskPriorityModalElement) : null;
    const taskPriorityForm = taskPriorityModalElement?.querySelector('form');
    const taskPriorityTaskId = taskPriorityModalElement?.querySelector('[data-task-priority-task-id]');
    const taskPriorityReturnTo = taskPriorityModalElement?.querySelector('[data-task-priority-return-to]');
    const taskPriorityTitle = taskPriorityModalElement?.querySelector('[data-task-priority-task-title]');
    const taskPriorityClient = taskPriorityModalElement?.querySelector('[data-task-priority-task-client]');
    const taskPriorityBadge = taskPriorityModalElement?.querySelector('[data-task-priority-task-badge]');
    const taskPrioritySelect = taskPriorityModalElement?.querySelector('[data-task-priority-select]');
    const taskPriorityDefaultTitle = taskPriorityTitle?.textContent || 'Choose a task priority from the table.';
    const taskPriorityDefaultClient = taskPriorityClient?.textContent || 'The modal will populate from the row you choose.';
    const taskPriorityDefaultBadgeText = taskPriorityBadge?.textContent || 'Not selected';
    const taskPriorityDefaultBadgeClass = taskPriorityBadge?.className || 'badge badge-soft';
    const taskPriorityDefaultValue = taskPrioritySelect?.value || 'medium';
    const taskPriorityDefaultReturnTo = taskPriorityReturnTo?.value || window.location.href;

    const clearTaskPriorityValidationState = () => {
        taskPriorityForm?.querySelectorAll('.is-invalid').forEach((element) => {
            element.classList.remove('is-invalid');
        });

        taskPriorityForm?.querySelectorAll('.invalid-feedback').forEach((element) => {
            element.style.display = 'none';
        });
    };

    document.querySelectorAll('[data-task-priority-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            if (!taskPriorityModal) {
                return;
            }

            clearTaskPriorityValidationState();

            const title = trigger.getAttribute('data-task-title') || taskPriorityDefaultTitle;
            const client = trigger.getAttribute('data-task-client') || taskPriorityDefaultClient;
            const priority = trigger.getAttribute('data-task-priority') || taskPriorityDefaultValue;
            const priorityLabel = trigger.getAttribute('data-task-priority-label') || taskPriorityDefaultBadgeText;
            const priorityClass = trigger.getAttribute('data-task-priority-class') || 'badge badge-soft';
            const action = trigger.getAttribute('data-task-priority-action') || '';
            const returnTo = trigger.getAttribute('data-task-return-to') || taskPriorityDefaultReturnTo;

            if (taskPriorityForm) {
                taskPriorityForm.setAttribute('action', action);
            }

            if (taskPriorityTaskId) {
                taskPriorityTaskId.value = trigger.getAttribute('data-task-id') || '';
            }

            if (taskPriorityReturnTo) {
                taskPriorityReturnTo.value = returnTo;
            }

            if (taskPriorityTitle) {
                taskPriorityTitle.textContent = title;
            }

            if (taskPriorityClient) {
                taskPriorityClient.textContent = client;
            }

            if (taskPriorityBadge) {
                taskPriorityBadge.className = `badge ${priorityClass}`.trim();
                taskPriorityBadge.textContent = priorityLabel;
            }

            if (taskPrioritySelect) {
                taskPrioritySelect.value = priority;
            }

            taskPriorityModal.show();
        });
    });

    taskPriorityModalElement?.addEventListener('hidden.bs.modal', () => {
        if (taskPriorityForm) {
            taskPriorityForm.setAttribute('action', '');
        }

        if (taskPriorityTaskId) {
            taskPriorityTaskId.value = '';
        }

        if (taskPriorityReturnTo) {
            taskPriorityReturnTo.value = taskPriorityDefaultReturnTo;
        }

        if (taskPriorityTitle) {
            taskPriorityTitle.textContent = taskPriorityDefaultTitle;
        }

        if (taskPriorityClient) {
            taskPriorityClient.textContent = taskPriorityDefaultClient;
        }

        if (taskPriorityBadge) {
            taskPriorityBadge.className = taskPriorityDefaultBadgeClass;
            taskPriorityBadge.textContent = taskPriorityDefaultBadgeText;
        }

        if (taskPrioritySelect) {
            taskPrioritySelect.value = taskPriorityDefaultValue;
        }

        clearTaskPriorityValidationState();
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
    const taskLogMinutesInput = taskLogModalElement?.querySelector('[data-task-log-time-minutes]');
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

            if (taskLogMinutesInput) {
                taskLogMinutesInput.value = '';
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

        if (taskLogMinutesInput) {
            taskLogMinutesInput.value = '';
        }

        if (taskLogUserInput) {
            taskLogUserInput.value = taskLogDefaultUserValue;
        }

        clearTaskLogNotes();
        clearTaskLogValidationState();
    });

    const timeEntryEditModalElement = document.getElementById('time-entry-edit-modal');
    const timeEntryEditModal = timeEntryEditModalElement ? bootstrap.Modal.getOrCreateInstance(timeEntryEditModalElement) : null;
    const timeEntryEditForm = timeEntryEditModalElement?.querySelector('form');
    const timeEntryEditUserInput = timeEntryEditModalElement?.querySelector('[name="user_id"]');
    const timeEntryEditTaskInput = timeEntryEditModalElement?.querySelector('[name="task_id"]');
    const timeEntryEditDateInput = timeEntryEditModalElement?.querySelector('[name="date"]');
    const timeEntryEditMinutesInput = timeEntryEditModalElement?.querySelector('[name="minutes"]');
    const timeEntryEditEditingInput = timeEntryEditModalElement?.querySelector('[name="editing_entry"]');
    const timeEntryEditReturnToInput = timeEntryEditModalElement?.querySelector('[name="return_to"]');
    const timeEntryEditNotesEditor = timeEntryEditModalElement?.querySelector('[data-rich-editor-editor]');
    const timeEntryEditDefaultAction = timeEntryEditForm?.getAttribute('action') || '';
    const timeEntryEditDefaultReturnTo = timeEntryEditReturnToInput?.value || '';
    const clearTimeEntryEditValidationState = () => {
        timeEntryEditForm?.querySelectorAll('.is-invalid').forEach((element) => {
            element.classList.remove('is-invalid');
        });

        timeEntryEditForm?.querySelectorAll('.invalid-feedback').forEach((element) => {
            element.style.display = 'none';
        });
    };

    document.querySelectorAll('[data-time-entry-edit-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();

            if (!timeEntryEditModal) {
                return;
            }

            clearTimeEntryEditValidationState();

            if (timeEntryEditForm) {
                timeEntryEditForm.setAttribute('action', trigger.getAttribute('data-time-entry-edit-action') || timeEntryEditDefaultAction);
            }

            if (timeEntryEditReturnToInput) {
                timeEntryEditReturnToInput.value = trigger.getAttribute('data-time-entry-edit-return-to') || timeEntryEditDefaultReturnTo;
            }

            if (timeEntryEditEditingInput) {
                timeEntryEditEditingInput.value = trigger.getAttribute('data-time-entry-edit-id') || '';
            }

            if (timeEntryEditUserInput) {
                timeEntryEditUserInput.value = trigger.getAttribute('data-time-entry-edit-user-id') || '';
            }

            if (timeEntryEditTaskInput) {
                timeEntryEditTaskInput.value = trigger.getAttribute('data-time-entry-edit-task-id') || '';
            }

            if (timeEntryEditDateInput) {
                timeEntryEditDateInput.value = trigger.getAttribute('data-time-entry-edit-date') || '';
            }

            if (timeEntryEditMinutesInput) {
                timeEntryEditMinutesInput.value = trigger.getAttribute('data-time-entry-edit-minutes') || '';
            }

            if (timeEntryEditNotesEditor) {
                timeEntryEditNotesEditor.innerHTML = trigger.getAttribute('data-time-entry-edit-notes') || '';
                syncRichEditor(timeEntryEditNotesEditor);
            }

            timeEntryEditModal.show();
        });
    });

    timeEntryEditModalElement?.addEventListener('hidden.bs.modal', () => {
        if (timeEntryEditForm) {
            timeEntryEditForm.setAttribute('action', '');
        }

        if (timeEntryEditReturnToInput) {
            timeEntryEditReturnToInput.value = timeEntryEditDefaultReturnTo;
        }

        if (timeEntryEditEditingInput) {
            timeEntryEditEditingInput.value = '';
        }

        if (timeEntryEditUserInput) {
            timeEntryEditUserInput.value = '';
        }

        if (timeEntryEditTaskInput) {
            timeEntryEditTaskInput.value = '';
        }

        if (timeEntryEditDateInput) {
            timeEntryEditDateInput.value = '';
        }

        if (timeEntryEditMinutesInput) {
            timeEntryEditMinutesInput.value = '';
        }

        if (timeEntryEditNotesEditor) {
            timeEntryEditNotesEditor.innerHTML = '';
            syncRichEditor(timeEntryEditNotesEditor);
        }

        clearTimeEntryEditValidationState();
    });

    const deleteModalElement = document.getElementById('delete-confirmation-modal');
    const deleteForm = document.getElementById('delete-confirmation-form');
    const deleteTitle = document.getElementById('delete-confirmation-title');
    const deleteMessage = document.getElementById('delete-confirmation-message');
    const deleteSubmit = document.getElementById('delete-confirmation-submit');
    const deleteMethod = document.getElementById('delete-confirmation-method');
    const deleteReturnToInput = document.getElementById('delete-confirmation-return-to');
    const deleteModal = deleteModalElement ? bootstrap.Modal.getOrCreateInstance(deleteModalElement) : null;
    const deleteDefaultReturnTo = deleteReturnToInput?.value || '';

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
            if (deleteReturnToInput) {
                deleteReturnToInput.value = trigger.getAttribute('data-delete-return-to') || deleteDefaultReturnTo;
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
        if (deleteReturnToInput) {
            deleteReturnToInput.value = deleteDefaultReturnTo;
        }
    });

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => {
            setLoadingState(form);
        });
    });
});
