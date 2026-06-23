import './bootstrap';
import * as bootstrap from 'bootstrap';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.bootstrap = bootstrap;
window.Swal = Swal;

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
    const container = editor.closest('[data-rich-editor]');
    const target = targetId
        ? container?.querySelector(`#${CSS.escape(targetId)}`) || document.getElementById(targetId)
        : null;

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

const swalWidthMap = {
    sm: '26rem',
    md: '32rem',
    lg: '42rem',
    xl: '52rem',
    '2xl': '60rem',
};

function getSwalWidth(source) {
    const width = source?.dataset.swalWidth;

    if (!width) {
        return '42rem';
    }

    return swalWidthMap[width] || width;
}

function cloneSwalSource(source) {
    const clone = source.cloneNode(true);

    clone.classList.remove('d-none');
    clone.removeAttribute('hidden');
    clone.removeAttribute('aria-hidden');
    clone.style.display = 'block';
    clone.querySelectorAll('.swal-source-shell').forEach((shell) => {
        shell.style.display = 'block';
    });

    return clone;
}

function bindPopupDismissButtons(root) {
    root.querySelectorAll('[data-bs-dismiss="modal"], [data-swal-close]').forEach((button) => {
        if (button.dataset.swalDismissBound === 'true') {
            return;
        }

        button.dataset.swalDismissBound = 'true';
        button.addEventListener('click', () => {
            Swal.close();
        });
    });
}

function bindLoadingStateForRoot(root) {
    root.querySelectorAll('form').forEach((form) => {
        if (form.dataset.loadingBound === 'true') {
            return;
        }

        form.dataset.loadingBound = 'true';
        form.addEventListener('submit', () => {
            setLoadingState(form);
        });
    });
}

function initializeRichEditorsForRoot(root) {
    root.querySelectorAll('[data-rich-editor]').forEach((container) => {
        if (container.dataset.richEditorBound === 'true') {
            return;
        }

        container.dataset.richEditorBound = 'true';
        initializeRichEditor(container);
    });
}

function clearValidationState(root) {
    root?.querySelectorAll('.is-invalid').forEach((element) => {
        element.classList.remove('is-invalid');
    });

    root?.querySelectorAll('.invalid-feedback').forEach((element) => {
        element.style.display = 'none';
    });
}

function openSwalFromSource(source, { beforeOpen, didOpen, didClose } = {}) {
    if (!source) {
        return;
    }

    const content = cloneSwalSource(source);

    if (typeof beforeOpen === 'function') {
        beforeOpen(content);
    }

    Swal.fire({
        html: content,
        width: getSwalWidth(source),
        backdrop: true,
        allowOutsideClick: false,
        allowEscapeKey: true,
        showConfirmButton: false,
        showCancelButton: false,
        scrollbarPadding: false,
        customClass: {
            popup: 'app-swal-popup',
            htmlContainer: 'app-swal-html',
        },
        didOpen: () => {
            const popup = Swal.getPopup();

            if (!popup) {
                return;
            }

            bindPopupDismissButtons(popup);
            bindLoadingStateForRoot(popup);
            initializeRichEditorsForRoot(popup);
            popup.querySelector('[autofocus], input, select, textarea, button')?.focus?.();

            if (typeof didOpen === 'function') {
                didOpen(popup);
            }
        },
        didClose: () => {
            if (typeof didClose === 'function') {
                didClose();
            }
        },
    });
}

window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-swal-auto-open="true"]').forEach((source) => {
        openSwalFromSource(source);
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

    const taskStatusSource = document.getElementById('task-status-modal');
    const taskStatusTitle = taskStatusSource?.querySelector('[data-task-status-task-title]');
    const taskStatusClient = taskStatusSource?.querySelector('[data-task-status-task-client]');
    const taskStatusBadge = taskStatusSource?.querySelector('[data-task-status-task-badge]');
    const taskStatusSelect = taskStatusSource?.querySelector('[data-task-status-select]');
    const taskStatusTaskId = taskStatusSource?.querySelector('[data-task-status-task-id]');
    const taskStatusDefaultTitle = taskStatusTitle?.textContent || 'Choose a task status from the table.';
    const taskStatusDefaultClient = taskStatusClient?.textContent || 'The popup will populate from the row you choose.';
    const taskStatusDefaultBadgeText = taskStatusBadge?.textContent || 'Not selected';
    const taskStatusDefaultValue = taskStatusSelect?.value || 'open';

    const openTaskStatusPopup = (trigger) => {
        openSwalFromSource(taskStatusSource, {
            beforeOpen: (content) => {
                clearValidationState(content);

                const form = content.querySelector('form');
                const title = content.querySelector('[data-task-status-task-title]');
                const client = content.querySelector('[data-task-status-task-client]');
                const badge = content.querySelector('[data-task-status-task-badge]');
                const select = content.querySelector('[data-task-status-select]');
                const taskId = content.querySelector('[data-task-status-task-id]');

                if (form) {
                    form.setAttribute('action', trigger.getAttribute('data-task-status-action') || '');
                }

                if (title) {
                    title.textContent = trigger.getAttribute('data-task-title') || taskStatusDefaultTitle;
                }

                if (client) {
                    client.textContent = trigger.getAttribute('data-task-client') || taskStatusDefaultClient;
                }

                if (badge) {
                    const statusClass = trigger.getAttribute('data-task-status-class') || 'badge badge-soft';
                    badge.className = `badge ${statusClass}`.trim();
                    badge.textContent = trigger.getAttribute('data-task-status-label') || taskStatusDefaultBadgeText;
                }

                if (select) {
                    select.value = trigger.getAttribute('data-task-status') || taskStatusDefaultValue;
                }

                if (taskId) {
                    taskId.value = trigger.getAttribute('data-task-id') || '';
                }
            },
        });
    };

    document.querySelectorAll('[data-task-status-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            if (!taskStatusSource) {
                return;
            }

            openTaskStatusPopup(trigger);
        });
    });

    const taskBulkStatusForm = document.querySelector('[data-task-bulk-status-form]');
    const taskBulkStatusCheckboxes = Array.from(document.querySelectorAll('[data-task-bulk-status-checkbox]'));
    const taskBulkStatusSelectAll = document.querySelector('[data-task-bulk-status-select-all]');
    const taskBulkStatusSelect = taskBulkStatusForm?.querySelector('[data-task-bulk-status-select]');
    const taskBulkStatusCount = taskBulkStatusForm?.querySelector('[data-task-bulk-status-count]');
    const taskBulkStatusApply = taskBulkStatusForm?.querySelector('[data-task-bulk-status-apply]');
    const taskBulkStatusPanel = taskBulkStatusForm?.closest('[data-task-bulk-status-panel]');

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

    const taskPrioritySource = document.getElementById('task-priority-modal');
    const taskPriorityTitle = taskPrioritySource?.querySelector('[data-task-priority-task-title]');
    const taskPriorityClient = taskPrioritySource?.querySelector('[data-task-priority-task-client]');
    const taskPriorityBadge = taskPrioritySource?.querySelector('[data-task-priority-task-badge]');
    const taskPrioritySelect = taskPrioritySource?.querySelector('[data-task-priority-select]');
    const taskPriorityReturnTo = taskPrioritySource?.querySelector('[data-task-priority-return-to]');
    const taskPriorityDefaultTitle = taskPriorityTitle?.textContent || 'Choose a task priority from the table.';
    const taskPriorityDefaultClient = taskPriorityClient?.textContent || 'The popup will populate from the row you choose.';
    const taskPriorityDefaultBadgeText = taskPriorityBadge?.textContent || 'Not selected';
    const taskPriorityDefaultValue = taskPrioritySelect?.value || 'medium';
    const taskPriorityDefaultReturnTo = taskPriorityReturnTo?.value || window.location.href;

    const openTaskPriorityPopup = (trigger) => {
        openSwalFromSource(taskPrioritySource, {
            beforeOpen: (content) => {
                clearValidationState(content);

                const form = content.querySelector('form');
                const title = content.querySelector('[data-task-priority-task-title]');
                const client = content.querySelector('[data-task-priority-task-client]');
                const badge = content.querySelector('[data-task-priority-task-badge]');
                const select = content.querySelector('[data-task-priority-select]');
                const taskId = content.querySelector('[data-task-priority-task-id]');
                const returnTo = content.querySelector('[data-task-priority-return-to]');

                if (form) {
                    form.setAttribute('action', trigger.getAttribute('data-task-priority-action') || '');
                }

                if (title) {
                    title.textContent = trigger.getAttribute('data-task-title') || taskPriorityDefaultTitle;
                }

                if (client) {
                    client.textContent = trigger.getAttribute('data-task-client') || taskPriorityDefaultClient;
                }

                if (badge) {
                    const priorityClass = trigger.getAttribute('data-task-priority-class') || 'badge badge-soft';
                    badge.className = `badge ${priorityClass}`.trim();
                    badge.textContent = trigger.getAttribute('data-task-priority-label') || taskPriorityDefaultBadgeText;
                }

                if (select) {
                    select.value = trigger.getAttribute('data-task-priority') || taskPriorityDefaultValue;
                }

                if (taskId) {
                    taskId.value = trigger.getAttribute('data-task-id') || '';
                }

                if (returnTo) {
                    returnTo.value = trigger.getAttribute('data-task-return-to') || taskPriorityDefaultReturnTo;
                }
            },
        });
    };

    document.querySelectorAll('[data-task-priority-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            if (!taskPrioritySource) {
                return;
            }

            openTaskPriorityPopup(trigger);
        });
    });

    const taskLogSource = document.getElementById('task-log-time-modal');
    const taskLogTitle = taskLogSource?.querySelector('[data-task-log-time-task-title]');
    const taskLogClient = taskLogSource?.querySelector('[data-task-log-time-task-client]');
    const taskLogStatusBadge = taskLogSource?.querySelector('[data-task-log-time-task-status-badge]');
    const taskLogStatusSelect = taskLogSource?.querySelector('[data-task-log-time-status]');
    const taskLogDateInput = taskLogSource?.querySelector('[data-task-log-time-date]');
    const taskLogMinutesInput = taskLogSource?.querySelector('[data-task-log-time-minutes]');
    const taskLogUserInput = taskLogSource?.querySelector('[data-task-log-time-user]');
    const taskLogNotesEditor = taskLogSource?.querySelector('[data-rich-editor-editor]');
    const taskLogTaskId = taskLogSource?.querySelector('[data-task-log-time-task-id]');
    const taskLogDefaultTitle = taskLogTitle?.textContent || 'Choose a task from the table to log time.';
    const taskLogDefaultClient = taskLogClient?.textContent || 'The popup will populate from the row you choose.';
    const taskLogDefaultStatusText = taskLogStatusBadge?.textContent || 'Not selected';
    const taskLogDefaultStatusValue = taskLogStatusSelect?.value || 'open';
    const taskLogDefaultDateValue = getLocalDateInputValue();
    const taskLogDefaultUserValue = taskLogUserInput?.value || '';

    const clearTaskLogNotes = (root) => {
        const editor = root.querySelector('[data-rich-editor-editor]');

        if (!editor) {
            return;
        }

        editor.innerHTML = '';
        syncRichEditor(editor);
    };

    const openTaskLogPopup = (trigger) => {
        openSwalFromSource(taskLogSource, {
            beforeOpen: (content) => {
                clearValidationState(content);

                const form = content.querySelector('form');
                const title = content.querySelector('[data-task-log-time-task-title]');
                const client = content.querySelector('[data-task-log-time-task-client]');
                const badge = content.querySelector('[data-task-log-time-task-status-badge]');
                const status = content.querySelector('[data-task-log-time-status]');
                const date = content.querySelector('[data-task-log-time-date]');
                const minutes = content.querySelector('[data-task-log-time-minutes]');
                const user = content.querySelector('[data-task-log-time-user]');
                const taskId = content.querySelector('[data-task-log-time-task-id]');

                if (form) {
                    form.setAttribute('action', taskLogSource?.querySelector('form')?.getAttribute('action') || '');
                }

                if (title) {
                    title.textContent = trigger.getAttribute('data-task-title') || taskLogDefaultTitle;
                }

                if (client) {
                    client.textContent = trigger.getAttribute('data-task-client') || taskLogDefaultClient;
                }

                if (badge) {
                    const statusClass = trigger.getAttribute('data-task-status-class') || 'badge badge-soft';
                    badge.className = `badge ${statusClass}`.trim();
                    badge.textContent = trigger.getAttribute('data-task-status-label') || taskLogDefaultStatusText;
                }

                if (status) {
                    status.value = trigger.getAttribute('data-task-status') || taskLogDefaultStatusValue;
                }

                if (date) {
                    date.value = taskLogDefaultDateValue;
                }

                if (minutes) {
                    minutes.value = '';
                }

                if (user) {
                    user.value = taskLogDefaultUserValue;
                }

                if (taskId) {
                    taskId.value = trigger.getAttribute('data-task-id') || '';
                }

                clearTaskLogNotes(content);
            },
        });
    };

    document.querySelectorAll('[data-task-log-time-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            if (!taskLogSource) {
                return;
            }

            openTaskLogPopup(trigger);
        });
    });

    const timeEntryEditSource = document.getElementById('time-entry-edit-modal');
    const timeEntryEditForm = timeEntryEditSource?.querySelector('form');
    const timeEntryEditUserInput = timeEntryEditSource?.querySelector('[name="user_id"]');
    const timeEntryEditTaskInput = timeEntryEditSource?.querySelector('[name="task_id"]');
    const timeEntryEditDateInput = timeEntryEditSource?.querySelector('[name="date"]');
    const timeEntryEditMinutesInput = timeEntryEditSource?.querySelector('[name="minutes"]');
    const timeEntryEditEditingInput = timeEntryEditSource?.querySelector('[name="editing_entry"]');
    const timeEntryEditReturnToInput = timeEntryEditSource?.querySelector('[name="return_to"]');
    const timeEntryEditNotesEditor = timeEntryEditSource?.querySelector('[data-rich-editor-editor]');
    const timeEntryEditDefaultAction = timeEntryEditForm?.getAttribute('action') || '';
    const timeEntryEditDefaultReturnTo = timeEntryEditReturnToInput?.value || '';

    const openTimeEntryEditPopup = (trigger) => {
        openSwalFromSource(timeEntryEditSource, {
            beforeOpen: (content) => {
                clearValidationState(content);

                const form = content.querySelector('form');
                const userInput = content.querySelector('[name="user_id"]');
                const taskInput = content.querySelector('[name="task_id"]');
                const dateInput = content.querySelector('[name="date"]');
                const minutesInput = content.querySelector('[name="minutes"]');
                const editingInput = content.querySelector('[name="editing_entry"]');
                const returnToInput = content.querySelector('[name="return_to"]');
                const notesEditor = content.querySelector('[data-rich-editor-editor]');

                if (form) {
                    form.setAttribute('action', trigger.getAttribute('data-time-entry-edit-action') || timeEntryEditDefaultAction);
                }

                if (returnToInput) {
                    returnToInput.value = trigger.getAttribute('data-time-entry-edit-return-to') || timeEntryEditDefaultReturnTo;
                }

                if (editingInput) {
                    editingInput.value = trigger.getAttribute('data-time-entry-edit-id') || '';
                }

                if (userInput) {
                    userInput.value = trigger.getAttribute('data-time-entry-edit-user-id') || '';
                }

                if (taskInput) {
                    taskInput.value = trigger.getAttribute('data-time-entry-edit-task-id') || '';
                }

                if (dateInput) {
                    dateInput.value = trigger.getAttribute('data-time-entry-edit-date') || '';
                }

                if (minutesInput) {
                    minutesInput.value = trigger.getAttribute('data-time-entry-edit-minutes') || '';
                }

                if (notesEditor) {
                    notesEditor.innerHTML = trigger.getAttribute('data-time-entry-edit-notes') || '';
                    syncRichEditor(notesEditor);
                }
            },
        });
    };

    document.querySelectorAll('[data-time-entry-edit-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();

            if (!timeEntryEditSource) {
                return;
            }

            openTimeEntryEditPopup(trigger);
        });
    });

    const deleteSource = document.getElementById('delete-confirmation-modal');
    const deleteForm = deleteSource?.querySelector('#delete-confirmation-form');
    const deleteTitle = deleteSource?.querySelector('#delete-confirmation-title');
    const deleteMessage = deleteSource?.querySelector('#delete-confirmation-message');
    const deleteSubmit = deleteSource?.querySelector('#delete-confirmation-submit');
    const deleteMethod = deleteSource?.querySelector('#delete-confirmation-method');
    const deleteReturnToInput = deleteSource?.querySelector('#delete-confirmation-return-to');
    const deleteDefaultReturnTo = deleteReturnToInput?.value || '';

    document.querySelectorAll('[data-delete-confirm]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            if (!deleteSource) {
                return;
            }

            openSwalFromSource(deleteSource, {
                beforeOpen: (content) => {
                    clearValidationState(content);

                    const form = content.querySelector('#delete-confirmation-form');
                    const title = content.querySelector('#delete-confirmation-title');
                    const message = content.querySelector('#delete-confirmation-message');
                    const submit = content.querySelector('#delete-confirmation-submit');
                    const method = content.querySelector('#delete-confirmation-method');
                    const returnTo = content.querySelector('#delete-confirmation-return-to');

                    if (form) {
                        form.setAttribute('action', trigger.getAttribute('data-delete-action') || '');
                    }

                    if (method) {
                        method.value = (trigger.getAttribute('data-delete-method') || 'DELETE').toUpperCase();
                    }

                    if (returnTo) {
                        returnTo.value = trigger.getAttribute('data-delete-return-to') || deleteDefaultReturnTo;
                    }

                    if (title) {
                        title.textContent = trigger.getAttribute('data-delete-title') || 'Delete item';
                    }

                    if (message) {
                        message.textContent = trigger.getAttribute('data-delete-message') || 'Are you sure you want to delete this item? This action cannot be undone.';
                    }

                    if (submit) {
                        const submitLabel = trigger.getAttribute('data-delete-submit') || 'Delete';
                        submit.innerHTML = `<i class="bi bi-trash me-1"></i>${submitLabel}`;
                        submit.setAttribute('data-loading-text', `${submitLabel}...`);
                    }
                },
            });
        });
    });

    document.querySelectorAll('[data-swal-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const source = document.getElementById(trigger.getAttribute('data-swal-open') || '');

            if (!source) {
                return;
            }

            openSwalFromSource(source);
        });
    });

    bindLoadingStateForRoot(document);
});
