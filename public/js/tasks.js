(function () {
    const i18n = window.FamilyLifeTranslations || {};
    const locale = i18n.locale || 'en-US';
    const authError = document.getElementById('authError');
    const familyInfo = document.getElementById('familyInfo');
    const tasksContainer = document.getElementById('tasksContainer');
    const createTaskCard = document.getElementById('createTaskCard');
    const createTaskForm = document.getElementById('createTaskForm');
    const createTaskSubmitBtn = document.getElementById('createTaskSubmitBtn');
    const status = document.getElementById('status');
    const taskActionStatus = document.getElementById('taskActionStatus');
    let currentMemberId = null;

    function t(key, fallback) {
        return Object.prototype.hasOwnProperty.call(i18n, key) ? i18n[key] : fallback;
    }

    function showAuthError(message) {
        authError.textContent = message;
        authError.classList.remove('d-none');
    }

    function showStatus(message, type) {
        status.textContent = message;
        status.className = 'alert mt-3 mb-0 alert-' + type;
    }

    function hideStatus() {
        status.className = 'alert d-none mt-3 mb-0';
        status.textContent = '';
    }

    function showTaskActionStatus(message, type) {
        taskActionStatus.textContent = message;
        taskActionStatus.className = 'alert mb-3 alert-' + type;
    }

    function setCreateBusy(isBusy) {
        createTaskSubmitBtn.disabled = isBusy;
        createTaskSubmitBtn.textContent = isBusy ? t('creating', 'Creating...') : t('create_task', 'Create Task');
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDate(value) {
        if (!value) {
            return t('unknown', 'Unknown');
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleString(locale);
    }

    function buildTaskRow(task, isDisabled) {
        const isCreator = currentMemberId !== null && task.created_by === currentMemberId;
        let actionCell;

        if (isCreator) {
            if (isDisabled) {
                actionCell = '<button class="btn btn-sm btn-outline-success" type="button" data-enable-task-id="' + task.id + '">'
                    + t('enable', 'Enable')
                    + '</button>';
            } else {
                actionCell = '<button class="btn btn-sm btn-outline-secondary" type="button" data-disable-task-id="' + task.id + '">'
                    + t('disable', 'Disable')
                    + '</button>';
            }
        } else {
            actionCell = '';
        }

        return '<tr>'
            + '<td>#' + task.id + '</td>'
            + '<td>' + escapeHtml(task.name) + '</td>'
            + '<td>' + task.points + '</td>'
            + '<td>' + escapeHtml(formatDate(task.created_at)) + '</td>'
            + '<td>' + actionCell + '</td>'
            + '</tr>';
    }

    function renderTasks(tasks) {
        if (!Array.isArray(tasks) || tasks.length === 0) {
            tasksContainer.innerHTML = '<p class="text-muted mb-0">' + t('no_tasks', 'No tasks yet.') + '</p>';
            return;
        }

        const activeTasks = tasks.filter(function (task) { return !task.disabled; });
        const disabledTasks = tasks.filter(function (task) { return task.disabled; });

        const headerRow = '<thead><tr>'
            + '<th>ID</th>'
            + '<th>' + t('task', 'Task') + '</th>'
            + '<th>' + t('points', 'Points') + '</th>'
            + '<th>' + t('created', 'Created') + '</th>'
            + '<th>' + t('actions', 'Actions') + '</th>'
            + '</tr></thead>';

        let html = '';

        if (activeTasks.length === 0) {
            html += '<p class="text-muted mb-0">' + t('no_tasks', 'No tasks yet.') + '</p>';
        } else {
            const activeRows = activeTasks.map(function (task) { return buildTaskRow(task, false); }).join('');
            html += '<table class="table table-sm align-middle mb-0">' + headerRow + '<tbody>' + activeRows + '</tbody></table>';
        }

        if (disabledTasks.length > 0) {
            const disabledRows = disabledTasks.map(function (task) { return buildTaskRow(task, true); }).join('');
            html += '<h6 class="mt-4 mb-2 text-muted">' + t('disabled_tasks', 'Disabled Tasks') + '</h6>'
                + '<table class="table table-sm align-middle mb-0 text-muted">' + headerRow + '<tbody>' + disabledRows + '</tbody></table>';
        }

        tasksContainer.innerHTML = html;
    }

    async function loadTasks() {
        const tasks = await window.FamilyLifeAuth.api('/tasks');
        renderTasks(tasks);
    }

    function openCreateTask() {
        hideStatus();
        createTaskCard.classList.remove('d-none');
        document.getElementById('taskName').focus();
    }

    function closeCreateTask() {
        createTaskCard.classList.add('d-none');
        createTaskForm.reset();
        document.getElementById('taskPoints').value = '10';
        hideStatus();
    }

    async function init() {
        const token = window.FamilyLifeAuth.getToken();
        if (!token) {
            showAuthError(t('login_required_long', 'Login required. Use a member token in the URL hash (#token=...) or create a family on the start page.'));
            window.location.href = 'index.php';
            return;
        }

        document.getElementById('backBtn').href = 'dashboard.php#token=' + encodeURIComponent(token);

        try {
            const me = await window.FamilyLifeAuth.api('/me');
            currentMemberId = me.id;
            familyInfo.textContent = t('tasks_for', 'Tasks for ') + me.family_name;
            await loadTasks();
        } catch (error) {
            showAuthError(error.message);
            tasksContainer.innerHTML = '';
        }
    }

    document.getElementById('logoutBtn').addEventListener('click', function () {
        window.location.href = 'index.php';
    });

    document.getElementById('refreshTasksBtn').addEventListener('click', function () {
        loadTasks().catch(function (error) {
            showAuthError(error.message);
        });
    });

    document.getElementById('showCreateTaskBtn').addEventListener('click', function () {
        openCreateTask();
    });

    document.getElementById('cancelCreateTaskBtn').addEventListener('click', function () {
        closeCreateTask();
    });

    tasksContainer.addEventListener('click', function (event) {
        const disableBtn = event.target.closest('[data-disable-task-id]');
        if (disableBtn) {
            const taskId = disableBtn.getAttribute('data-disable-task-id');
            disableBtn.disabled = true;
            window.FamilyLifeAuth.api('/tasks/' + taskId + '/disable', { method: 'PUT' })
                .then(function () {
                    showTaskActionStatus(t('task_disabled_success', 'Task disabled successfully.'), 'success');
                    return loadTasks();
                })
                .catch(function (error) {
                    showTaskActionStatus(error.message, 'danger');
                    disableBtn.disabled = false;
                });
            return;
        }

        const enableBtn = event.target.closest('[data-enable-task-id]');
        if (enableBtn) {
            const taskId = enableBtn.getAttribute('data-enable-task-id');
            enableBtn.disabled = true;
            window.FamilyLifeAuth.api('/tasks/' + taskId + '/enable', { method: 'PUT' })
                .then(function () {
                    showTaskActionStatus(t('task_enabled_success', 'Task enabled successfully.'), 'success');
                    return loadTasks();
                })
                .catch(function (error) {
                    showTaskActionStatus(error.message, 'danger');
                    enableBtn.disabled = false;
                });
        }
    });

    createTaskForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const name = document.getElementById('taskName').value.trim();
        const points = parseInt(document.getElementById('taskPoints').value, 10);

        if (!name) {
            showStatus(t('task_name_required', 'Please provide a task name.'), 'warning');
            return;
        }

        if (!Number.isInteger(points) || points <= 0) {
            showStatus(t('points_positive', 'Points must be a positive number.'), 'warning');
            return;
        }

        setCreateBusy(true);

        window.FamilyLifeAuth.api('/tasks', {
            method: 'POST',
            body: JSON.stringify({
                name: name,
                points: points
            })
        }).then(function () {
            showStatus(t('task_created_success', 'Task created successfully.'), 'success');
            return loadTasks();
        }).then(function () {
            setCreateBusy(false);
            closeCreateTask();
        }).catch(function (error) {
            showStatus(error.message, 'danger');
            setCreateBusy(false);
        });
    });

    init();
})();
