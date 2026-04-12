(function () {
    const authError = document.getElementById('authError');
    const memberSummary = document.getElementById('memberSummary');
    const claimsContainer = document.getElementById('claimsContainer');
    const scoreboardContainer = document.getElementById('scoreboardContainer');

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

    function renderClaims(claims) {
        if (!Array.isArray(claims) || claims.length === 0) {
            claimsContainer.innerHTML = '<p class="text-muted mb-0">No current claims.</p>';
            return;
        }

        const rows = claims.map(function (claim) {
            return '<tr>'
                + '<td>#' + claim.id + '</td>'
                + '<td>' + escapeHtml(claim.claimed_by_name) + '</td>'
                + '<td>' + escapeHtml(claim.task_name) + '</td>'
                + '<td><span class="badge text-bg-secondary">' + escapeHtml(claim.status) + '</span></td>'
                + '</tr>';
        }).join('');

        claimsContainer.innerHTML = '<table class="table table-sm align-middle mb-0">'
            + '<thead><tr><th>ID</th><th>Member</th><th>Task</th><th>Status</th></tr></thead>'
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
        memberSummary.textContent = me.name + ' in ' + me.family_name;

        const token = window.FamilyLifeAuth.getToken();
        document.getElementById('addMemberLink').href = '/add-member.html#token=' + encodeURIComponent(token);

        const claims = await window.FamilyLifeAuth.api('/claims?status=pending');
        renderClaims(claims);

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
