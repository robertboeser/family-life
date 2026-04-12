(function () {
    const authError = document.getElementById('authError');
    const memberSummary = document.getElementById('memberSummary');
    const claimTaskForm = document.getElementById('claimTaskForm');
    const claimTaskSelect = document.getElementById('claimTaskSelect');
    const claimTaskStatus = document.getElementById('claimTaskStatus');
    const submitClaimTaskBtn = document.getElementById('submitClaimTaskBtn');
    const claimsContainer = document.getElementById('claimsContainer');
    const scoreboardContainer = document.getElementById('scoreboardContainer');
    let currentMemberId = null;

    function showAuthError(message) {
        authError.textContent = message;
        authError.classList.remove('d-none');
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function showClaimTaskStatus(message, type) {
        claimTaskStatus.textContent = message;
        claimTaskStatus.className = 'alert mb-3 py-2 alert-' + type;
    }

    function hideClaimTaskStatus() {
        claimTaskStatus.className = 'alert d-none mb-3 py-2';
        claimTaskStatus.textContent = '';
    }

    function setClaimBusy(isBusy) {
        submitClaimTaskBtn.disabled = isBusy;
        submitClaimTaskBtn.textContent = isBusy ? 'Claiming...' : 'Claim';
    }

    function renderTaskOptions(tasks) {
        const options = ['<option value="">Select a task...</option>'];

        if (Array.isArray(tasks)) {
            tasks.forEach(function (task) {
                options.push(
                    '<option value="' + task.id + '">' + escapeHtml(task.name) + ' (' + task.points + ' pts)</option>'
                );
            });
        }

        claimTaskSelect.innerHTML = options.join('');
    }

    function renderClaims(claims) {
        if (!Array.isArray(claims) || claims.length === 0) {
            claimsContainer.innerHTML = '<p class="text-muted mb-0">No current claims.</p>';
            return;
        }

        const rows = claims.map(function (claim) {
            const canApprove = claim.status === 'pending' && currentMemberId !== null && claim.claimed_by !== currentMemberId;
            const actionCell = canApprove
                ? '<button class="btn btn-sm btn-outline-success" type="button" data-approve-claim-id="' + claim.id + '">Approve</button>'
                : '<span class="text-muted small">-</span>';

            return '<tr>'
                + '<td>#' + claim.id + '</td>'
                + '<td>' + escapeHtml(claim.claimed_by_name) + '</td>'
                + '<td>' + escapeHtml(claim.task_name) + '</td>'
                + '<td><span class="badge text-bg-secondary">' + escapeHtml(claim.status) + '</span></td>'
                + '<td>' + actionCell + '</td>'
                + '</tr>';
        }).join('');

        claimsContainer.innerHTML = '<table class="table table-sm align-middle mb-0">'
            + '<thead><tr><th>ID</th><th>Member</th><th>Task</th><th>Status</th><th>Action</th></tr></thead>'
            + '<tbody>' + rows + '</tbody>'
            + '</table>';
    }

    function renderScoreboard(members) {
        if (!Array.isArray(members) || members.length === 0) {
            scoreboardContainer.innerHTML = '<p class="text-muted mb-0">No members yet.</p>';
            return;
        }

        const rows = members.map(function (member) {
            return '<tr>'
                + '<td>' + member.position + '</td>'
                + '<td>' + escapeHtml(member.name) + '</td>'
                + '<td>' + member.score + '</td>'
                + '<td>' + escapeHtml(member.rank || 'Unknown') + '</td>'
                + '</tr>';
        }).join('');

        scoreboardContainer.innerHTML = '<table class="table table-sm align-middle mb-0">'
            + '<thead><tr><th>Pos</th><th>Member</th><th>Score</th><th>Rank</th></tr></thead>'
            + '<tbody>' + rows + '</tbody>'
            + '</table>';
    }

    async function refreshDashboard() {
        const me = await window.FamilyLifeAuth.api('/me');
        currentMemberId = me.id;
        memberSummary.textContent = me.name + ' in ' + me.family_name;

        const token = window.FamilyLifeAuth.getToken();
        document.getElementById('votingLink').href = '/voting.html#token=' + encodeURIComponent(token);
        document.getElementById('tasksLink').href = '/tasks.html#token=' + encodeURIComponent(token);
        document.getElementById('addMemberLink').href = '/add-member.html#token=' + encodeURIComponent(token);

        const claims = await window.FamilyLifeAuth.api('/claims?status=pending');
        renderClaims(claims);

        const tasks = await window.FamilyLifeAuth.api('/tasks');
        renderTaskOptions(tasks);

        const scoreboard = await window.FamilyLifeAuth.api('/scoreboard');
        renderScoreboard(scoreboard);
    }

    function init() {
        const token = window.FamilyLifeAuth.getToken();
        if (!token) {
            showAuthError('Login required. Use a member token in the URL hash (#token=...) or create a family on the start page.');
            window.location.href = '/index.html';
            return;
        }

        document.getElementById('votingLink').href = '/voting.html#token=' + encodeURIComponent(token);
        document.getElementById('tasksLink').href = '/tasks.html#token=' + encodeURIComponent(token);
        document.getElementById('addMemberLink').href = '/add-member.html#token=' + encodeURIComponent(token);

        refreshDashboard().catch(function (error) {
            showAuthError(error.message);
            claimsContainer.innerHTML = '';
            scoreboardContainer.innerHTML = '';
        });
    }

    document.getElementById('refreshClaimsBtn').addEventListener('click', function () {
        window.FamilyLifeAuth.api('/claims?status=pending')
            .then(renderClaims)
            .catch(function (error) {
                showAuthError(error.message);
            });
    });

    claimsContainer.addEventListener('click', function (event) {
        const approveButton = event.target.closest('[data-approve-claim-id]');
        if (!approveButton) {
            return;
        }

        const claimId = parseInt(approveButton.getAttribute('data-approve-claim-id'), 10);
        if (!Number.isInteger(claimId) || claimId <= 0) {
            return;
        }

        approveButton.disabled = true;
        approveButton.textContent = 'Approving...';

        window.FamilyLifeAuth.api('/claims/' + claimId + '/approve', {
            method: 'PUT'
        }).then(function () {
            return Promise.all([
                window.FamilyLifeAuth.api('/claims?status=pending').then(renderClaims),
                window.FamilyLifeAuth.api('/scoreboard').then(renderScoreboard),
                window.FamilyLifeAuth.api('/tasks').then(renderTaskOptions)
            ]);
        }).catch(function (error) {
            showAuthError(error.message);
        }).finally(function () {
            if (document.body.contains(approveButton)) {
                approveButton.disabled = false;
                approveButton.textContent = 'Approve';
            }
        });
    });

    document.getElementById('showClaimTaskBtn').addEventListener('click', function () {
        claimTaskForm.classList.remove('d-none');
        hideClaimTaskStatus();
        claimTaskSelect.focus();
    });

    document.getElementById('cancelClaimTaskBtn').addEventListener('click', function () {
        claimTaskForm.classList.add('d-none');
        claimTaskSelect.value = '';
        hideClaimTaskStatus();
    });

    claimTaskForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const taskId = parseInt(claimTaskSelect.value, 10);
        if (!Number.isInteger(taskId) || taskId <= 0) {
            showClaimTaskStatus('Please choose a task to claim.', 'warning');
            return;
        }

        setClaimBusy(true);
        hideClaimTaskStatus();

        window.FamilyLifeAuth.api('/claims', {
            method: 'POST',
            body: JSON.stringify({ task_id: taskId })
        }).then(function () {
            showClaimTaskStatus('Task claimed successfully.', 'success');
            claimTaskSelect.value = '';

            return Promise.all([
                window.FamilyLifeAuth.api('/claims?status=pending').then(renderClaims),
                window.FamilyLifeAuth.api('/tasks').then(renderTaskOptions)
            ]);
        }).catch(function (error) {
            showClaimTaskStatus(error.message, 'danger');
        }).finally(function () {
            setClaimBusy(false);
        });
    });

    document.getElementById('refreshScoreboardBtn').addEventListener('click', function () {
        window.FamilyLifeAuth.api('/scoreboard')
            .then(renderScoreboard)
            .catch(function (error) {
                showAuthError(error.message);
            });
    });

    document.getElementById('logoutBtn').addEventListener('click', function () {
        window.location.href = '/index.html';
    });

    document.getElementById('copyLinkBtn').addEventListener('click', function () {
        const token = window.FamilyLifeAuth.getToken();
        const dashboardUrl = window.location.origin + '/dashboard.html#token=' + encodeURIComponent(token);

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(dashboardUrl).catch(function () {
                const tempInput = document.createElement('input');
                tempInput.value = dashboardUrl;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
            });
            return;
        }

        const tempInput = document.createElement('input');
        tempInput.value = dashboardUrl;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
    });

    init();
})();
