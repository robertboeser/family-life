(function () {
    const authError = document.getElementById('authError');
    const familyInfo = document.getElementById('familyInfo');
    const tasksContainer = document.getElementById('tasksContainer');
    const createTaskCard = document.getElementById('createTaskCard');
    const createTaskForm = document.getElementById('createTaskForm');
    const createTaskSubmitBtn = document.getElementById('createTaskSubmitBtn');
    const status = document.getElementById('status');

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

    function setCreateBusy(isBusy) {
        createTaskSubmitBtn.disabled = isBusy;
        createTaskSubmitBtn.textContent = isBusy ? 'Creating...' : 'Create Task';
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
            return 'Unknown';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleString();
    }

    function renderTasks(tasks) {
        if (!Array.isArray(tasks) || tasks.length === 0) {
            tasksContainer.innerHTML = '<p class="text-muted mb-0">No tasks yet.</p>';
            return;
        }

        const rows = tasks.map(function (task) {
            return '<tr>'
                + '<td>#' + task.id + '</td>'
                + '<td>' + escapeHtml(task.name) + '</td>'
                + '<td>' + task.points + '</td>'
                + '<td>' + escapeHtml(formatDate(task.created_at)) + '</td>'
                + '</tr>';
        }).join('');

        tasksContainer.innerHTML = '<table class="table table-sm align-middle mb-0">'
            + '<thead><tr><th>ID</th><th>Task</th><th>Points</th><th>Created</th></tr></thead>'
            + '<tbody>' + rows + '</tbody>'
            + '</table>';
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
            showAuthError('Login required. Use a member token in the URL hash (#token=...) or create a family on the start page.');
            window.location.href = '/index.html';
            return;
        }

        document.getElementById('backBtn').href = '/dashboard.html#token=' + encodeURIComponent(token);

        try {
            const me = await window.FamilyLifeAuth.api('/me');
            familyInfo.textContent = 'Tasks for ' + me.family_name;
            await loadTasks();
        } catch (error) {
            showAuthError(error.message);
            tasksContainer.innerHTML = '';
        }
    }

    document.getElementById('logoutBtn').addEventListener('click', function () {
        window.location.href = '/index.html';
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

    createTaskForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const name = document.getElementById('taskName').value.trim();
        const points = parseInt(document.getElementById('taskPoints').value, 10);

        if (!name) {
            showStatus('Please provide a task name.', 'warning');
            return;
        }

        if (!Number.isInteger(points) || points <= 0) {
            showStatus('Points must be a positive number.', 'warning');
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
            showStatus('Task created successfully.', 'success');
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
