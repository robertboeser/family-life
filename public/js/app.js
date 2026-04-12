(function () {
    const tokenKey = 'family_life_token';

    // ==== PAGE NAVIGATION ====
    const navLinks = document.querySelectorAll('nav a[data-page]');
    const pageSections = document.querySelectorAll('.page-section');

    function showPage(pageName) {
        // Hide all pages
        pageSections.forEach(page => page.classList.add('d-none'));
        
        // Show selected page
        const targetPage = document.getElementById('page-' + pageName);
        if (targetPage) {
            targetPage.classList.remove('d-none');
        }

        // Update nav links
        navLinks.forEach(link => {
            if (link.getAttribute('data-page') === pageName) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        // Refresh data for the page
        if (pageName === 'setup') {
            refreshAll();
        } else if (pageName === 'tasks') {
            refreshTasks();
        } else if (pageName === 'claims') {
            refreshClaims();
        } else if (pageName === 'approvals') {
            refreshMyClaims();
        } else if (pageName === 'voting') {
            refreshVoting();
        } else if (pageName === 'scoreboard') {
            refreshScoreboard();
        }
    }

    // Navigation link handlers
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const pageName = link.getAttribute('data-page');
            showPage(pageName);
        });
    });

    // ==== DOM ELEMENTS ====
    const authStatus = document.getElementById('authStatus');
    const tokenUrlPreview = document.getElementById('tokenUrlPreview');
    const setupOutput = document.getElementById('setupOutput');
    const meOutput = document.getElementById('meOutput');
    const reviewOutput = document.getElementById('reviewOutput');

    // ==== API HELPERS ====
    function getToken() {
        return localStorage.getItem(tokenKey) || '';
    }

    function setToken(token) {
        if (!token) {
            localStorage.removeItem(tokenKey);
            return;
        }
        localStorage.setItem(tokenKey, token);
    }

    function getTokenFromHash() {
        const hash = window.location.hash.replace(/^#/, '');
        if (!hash) {
            return '';
        }

        return new URLSearchParams(hash).get('token') || '';
    }

    function clearTokenFromHash() {
        if (!window.location.hash) {
            return;
        }

        window.history.replaceState(null, '', window.location.pathname + window.location.search);
    }

    function buildApiUrl(path) {
        const [rawPath, rawQuery = ''] = path.split('?');
        const cleanPath = (rawPath || '/').replace(/\/+$/, '') || '/';
        const query = rawQuery ? '?' + rawQuery : '';

        return '/api.php' + cleanPath + query;
    }

    async function api(path, options) {
        const opts = options || {};
        const headers = opts.headers || {};
        headers['Content-Type'] = 'application/json';

        const token = getToken();
        if (token) {
            headers.Authorization = 'Bearer ' + token;
            headers['X-Auth-Token'] = token;
        }

        const response = await fetch(buildApiUrl(path), { ...opts, headers: headers });
        const text = await response.text();
        const data = text ? JSON.parse(text) : {};

        if (!response.ok) {
            throw new Error(data.error || ('HTTP ' + response.status));
        }

        return data;
    }

    // ==== RENDERING HELPERS ====
    function print(el, data) {
        el.textContent = JSON.stringify(data, null, 2);
    }

    function renderList(items, mapper) {
        const ul = document.createElement('ul');
        ul.className = 'list-group';
        items.forEach(item => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = mapper(item);
            ul.appendChild(li);
        });
        return ul;
    }

    function renderTasksList(tasks) {
        const container = document.getElementById('tasksListContainer');
        container.innerHTML = '';
        if (tasks.length === 0) {
            container.innerHTML = '<p class="text-muted">No tasks available.</p>';
            return;
        }
        const list = renderList(tasks, task => {
            return '#' + task.id + ' | ' + task.name + ' | ' + task.points + ' pts | by member #' + task.created_by;
        });
        container.appendChild(list);
    }

    function renderClaimsList(claims) {
        const container = document.getElementById('claimsListContainer');
        container.innerHTML = '';
        if (claims.length === 0) {
            container.innerHTML = '<p class="text-muted">No claims found.</p>';
            return;
        }
        const list = renderList(claims, claim => {
            return '#' + claim.id + ' | Task #' + claim.task_id + ' (' + claim.task_name + ') | ' + claim.claimed_by_name + ' | ' + claim.status;
        });
        container.appendChild(list);
    }

    function renderMyClaimsList(claims) {
        const container = document.getElementById('myClaimsListContainer');
        container.innerHTML = '';
        if (claims.length === 0) {
            container.innerHTML = '<p class="text-muted">No claims yet.</p>';
            return;
        }
        const list = renderList(claims, claim => {
            return '#' + claim.id + ' | Task #' + claim.task_id + ' (' + claim.task_name + ') | ' + claim.points + ' pts | ' + claim.status;
        });
        container.appendChild(list);
    }

    function renderWishesList(wishes) {
        const container = document.getElementById('wishesListContainer');
        container.innerHTML = '';
        if (wishes.length === 0) {
            container.innerHTML = '<p class="text-muted">No wishes yet.</p>';
            return;
        }
        const list = renderList(wishes, wish => {
            return '#' + wish.id + ' | ' + wish.name + ' | score: ' + wish.score + ' | by ' + wish.created_by_name;
        });
        container.appendChild(list);
    }

    function renderScoreboard(board) {
        const container = document.getElementById('scoreboardContainer');
        container.innerHTML = '';
        if (board.length === 0) {
            container.innerHTML = '<p class="text-muted">No members yet.</p>';
            return;
        }

        const table = document.createElement('table');
        table.className = 'table table-hover';
        table.innerHTML = '<thead><tr><th>Position</th><th>Name</th><th>Score</th><th>Rank</th></tr></thead>';
        
        const tbody = document.createElement('tbody');
        board.forEach(member => {
            const row = tbody.insertRow();
            row.innerHTML = '<td><strong>' + member.position + '</strong></td><td>' + member.name + '</td><td>' + member.score + ' pts</td><td>' + (member.rank || 'Unknown') + '</td>';
        });
        table.appendChild(tbody);
        container.appendChild(table);
    }

    // ==== DATA REFRESH FUNCTIONS ====
    async function refreshMe() {
        const token = getToken();
        if (!token) {
            authStatus.innerHTML = '<small class="text-muted">No member token selected</small>';
            meOutput.textContent = '';
            return;
        }

        try {
            const me = await api('/me');
            const rankName = me.rank_name || 'Unknown Rank';
            authStatus.innerHTML = '<strong>' + me.name + '</strong> | score: ' + me.score + ' | rank: ' + rankName;
            print(meOutput, me);
        } catch (error) {
            authStatus.innerHTML = '<small class="text-danger">Token invalid</small>';
            meOutput.textContent = error.message;
        }
    }

    async function refreshTasks() {
        try {
            const tasks = await api('/tasks');
            renderTasksList(tasks);
        } catch (error) {
            const container = document.getElementById('tasksListContainer');
            container.innerHTML = '<div class="alert alert-danger"><small>' + error.message + '</small></div>';
        }
    }

    async function refreshClaims() {
        const filter = document.getElementById('claimFilter').value;
        try {
            const claims = await api('/claims?status=' + encodeURIComponent(filter));
            renderClaimsList(claims);
        } catch (error) {
            const container = document.getElementById('claimsListContainer');
            container.innerHTML = '<div class="alert alert-danger"><small>' + error.message + '</small></div>';
        }
    }

    async function refreshMyClaims() {
        try {
            const claims = await api('/claims/mine');
            renderMyClaimsList(claims);
        } catch (error) {
            const container = document.getElementById('myClaimsListContainer');
            container.innerHTML = '<div class="alert alert-danger"><small>' + error.message + '</small></div>';
        }
    }

    async function refreshScoreboard() {
        try {
            const board = await api('/scoreboard');
            renderScoreboard(board);
        } catch (error) {
            const container = document.getElementById('scoreboardContainer');
            container.innerHTML = '<div class="alert alert-danger"><small>' + error.message + '</small></div>';
        }
    }

    async function refreshVotingRound() {
        try {
            const round = await api('/voting/rounds/current');
            print(document.getElementById('votingRoundOutput'), round);
            if (round && round.id) {
                document.getElementById('closeRoundId').value = round.id;
                document.getElementById('resultRoundId').value = round.id;
            }
        } catch (error) {
            document.getElementById('votingRoundOutput').textContent = error.message;
        }
    }

    async function refreshWishes() {
        try {
            const wishes = await api('/voting/wishes');
            renderWishesList(wishes);
        } catch (error) {
            const container = document.getElementById('wishesListContainer');
            container.innerHTML = '<div class="alert alert-danger"><small>' + error.message + '</small></div>';
        }
    }

    // ==== FORM HANDLERS ====
    document.getElementById('createFamilyForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        const name = document.getElementById('familyName').value;
        try {
            const data = await api('/families', {
                method: 'POST',
                body: JSON.stringify({ name: name })
            });
            print(setupOutput, data);
            document.getElementById('memberFamilyId').value = data.id;
        } catch (error) {
            setupOutput.textContent = error.message;
        }
    });

    document.getElementById('addMemberForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        const familyId = document.getElementById('memberFamilyId').value.trim();
        const name = document.getElementById('memberName').value;

        try {
            const data = await api('/families/' + familyId + '/members', {
                method: 'POST',
                body: JSON.stringify({ name: name })
            });
            const url = window.location.origin + '/#token=' + encodeURIComponent(data.auth_token);
            tokenUrlPreview.textContent = url;
            print(setupOutput, { member: data, auth_url: url });
        } catch (error) {
            setupOutput.textContent = error.message;
        }
    });

    document.getElementById('setTokenForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const token = document.getElementById('sessionToken').value.trim();
        setToken(token);
        refreshAll();
    });

    document.getElementById('clearTokenBtn').addEventListener('click', function () {
        setToken('');
        document.getElementById('sessionToken').value = '';
        refreshAll();
    });

    document.getElementById('createTaskForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        const name = document.getElementById('taskName').value;
        const points = Number(document.getElementById('taskPoints').value);

        try {
            await api('/tasks', {
                method: 'POST',
                body: JSON.stringify({ name: name, points: points })
            });
            document.getElementById('taskName').value = '';
            document.getElementById('taskPoints').value = '';
            await refreshTasks();
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });

    document.getElementById('deleteTaskForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        const taskId = Number(document.getElementById('deleteTaskId').value);

        try {
            await api('/tasks/' + taskId, { method: 'DELETE' });
            document.getElementById('deleteTaskId').value = '';
            await refreshTasks();
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });

    document.getElementById('claimTaskForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        const taskId = Number(document.getElementById('claimTaskId').value);

        try {
            await api('/claims', {
                method: 'POST',
                body: JSON.stringify({ task_id: taskId })
            });
            document.getElementById('claimTaskId').value = '';
            await refreshTasks();
            await refreshMyClaims();
        } catch (error) {
            alert('Error: ' + error.message);
        }
    });

    document.getElementById('reviewForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        const claimId = Number(document.getElementById('reviewClaimId').value);
        const action = document.getElementById('reviewAction').value;

        try {
            const result = await api('/claims/' + claimId + '/' + action, {
                method: 'PUT',
                body: JSON.stringify({})
            });
            print(reviewOutput, result);
            document.getElementById('reviewClaimId').value = '';
            await refreshAll();
        } catch (error) {
            reviewOutput.textContent = error.message;
        }
    });

    document.getElementById('createRoundBtn').addEventListener('click', async function () {
        try {
            const result = await api('/voting/rounds', { method: 'POST', body: JSON.stringify({}) });
            print(document.getElementById('votingActionOutput'), result);
            await refreshVoting();
        } catch (error) {
            document.getElementById('votingActionOutput').textContent = error.message;
        }
    });

    document.getElementById('createWishForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        const name = document.getElementById('wishName').value;

        try {
            const result = await api('/voting/wishes', {
                method: 'POST',
                body: JSON.stringify({ name: name })
            });
            print(document.getElementById('votingActionOutput'), result);
            document.getElementById('wishName').value = '';
            await refreshVoting();
        } catch (error) {
            document.getElementById('votingActionOutput').textContent = error.message;
        }
    });

    document.getElementById('voteWishForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        const wishId = Number(document.getElementById('voteWishId').value);
        const amount = Number(document.getElementById('voteAmount').value);

        try {
            const result = await api('/voting/votes', {
                method: 'POST',
                body: JSON.stringify({ wish_id: wishId, amount: amount })
            });
            print(document.getElementById('votingActionOutput'), result);
            document.getElementById('voteWishId').value = '';
            document.getElementById('voteAmount').value = '';
            await refreshVoting();
            await refreshMe();
        } catch (error) {
            document.getElementById('votingActionOutput').textContent = error.message;
        }
    });

    document.getElementById('closeRoundForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        const roundId = Number(document.getElementById('closeRoundId').value);

        try {
            const result = await api('/voting/rounds/' + roundId + '/approve-close', {
                method: 'POST',
                body: JSON.stringify({})
            });
            print(document.getElementById('votingActionOutput'), result);
            await refreshVoting();
            await refreshMe();
        } catch (error) {
            document.getElementById('votingActionOutput').textContent = error.message;
        }
    });

    document.getElementById('roundResultForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        const roundId = Number(document.getElementById('resultRoundId').value);

        try {
            const result = await api('/voting/rounds/' + roundId + '/result');
            print(document.getElementById('votingActionOutput'), result);
        } catch (error) {
            document.getElementById('votingActionOutput').textContent = error.message;
        }
    });

    // Refresh buttons
    document.getElementById('refreshTasksBtn').addEventListener('click', refreshTasks);
    document.getElementById('refreshClaimsBtn').addEventListener('click', refreshClaims);
    document.getElementById('claimFilter').addEventListener('change', refreshClaims);
    document.getElementById('refreshMyClaimsBtn').addEventListener('click', refreshMyClaims);
    document.getElementById('refreshScoreboardBtn').addEventListener('click', refreshScoreboard);
    document.getElementById('refreshVotingBtn').addEventListener('click', refreshVoting);

    async function refreshVoting() {
        await refreshVotingRound();
        await refreshWishes();
    }

    async function refreshAll() {
        const tokenFromHash = getTokenFromHash();
        if (tokenFromHash) {
            setToken(tokenFromHash);
            document.getElementById('sessionToken').value = tokenFromHash;
            clearTokenFromHash();
        } else {
            document.getElementById('sessionToken').value = getToken();
        }

        await refreshMe();
        await refreshTasks();
        await refreshClaims();
        await refreshMyClaims();
        await refreshScoreboard();
        await refreshVoting();
    }

    // Initial setup
    showPage('setup');
})();
