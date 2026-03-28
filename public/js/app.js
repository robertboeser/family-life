(function () {
    const tokenKey = 'family_life_token';

    const authStatus = document.getElementById('authStatus');
    const tokenUrlPreview = document.getElementById('tokenUrlPreview');
    const setupOutput = document.getElementById('setupOutput');
    const meOutput = document.getElementById('meOutput');
    const reviewOutput = document.getElementById('reviewOutput');

    const tasksList = document.getElementById('tasksList');
    const claimsList = document.getElementById('claimsList');
    const myClaimsList = document.getElementById('myClaimsList');
    const scoreboardList = document.getElementById('scoreboardList');

    const votingRoundOutput = document.getElementById('votingRoundOutput');
    const votingActionOutput = document.getElementById('votingActionOutput');
    const wishesList = document.getElementById('wishesList');

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

    async function api(path, options) {
        const opts = options || {};
        const headers = opts.headers || {};
        headers['Content-Type'] = 'application/json';

        const token = getToken();
        if (token) {
            headers.Authorization = 'Bearer ' + token;
        }

        const response = await fetch('/api' + path, { ...opts, headers: headers });
        const text = await response.text();
        const data = text ? JSON.parse(text) : {};

        if (!response.ok) {
            throw new Error(data.error || ('HTTP ' + response.status));
        }

        return data;
    }

    function print(el, data) {
        el.textContent = JSON.stringify(data, null, 2);
    }

    function list(el, rows, map) {
        el.innerHTML = '';
        rows.forEach(function (row) {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = map(row);
            el.appendChild(li);
        });
    }

    async function fetchProceduralRankName(score, fallbackName) {
        const safeScore = Number.isFinite(score) ? Math.max(0, Math.floor(score)) : 0;
        const rankIndex = Math.floor(safeScore / 20);

        try {
            const data = await api('/rank-name?index=' + rankIndex);
            return data.name || fallbackName || 'Unknown Rank';
        } catch (error) {
            return fallbackName || 'Unknown Rank';
        }
    }

    async function refreshMe() {
        const token = getToken();
        if (!token) {
            authStatus.textContent = 'No member token selected';
            meOutput.textContent = '';
            return;
        }

        try {
            const me = await api('/me');
            const proceduralRankName = await fetchProceduralRankName(me.score, me.rank_name);
            authStatus.textContent = me.name + ' | score: ' + me.score + ' | rank: ' + proceduralRankName;
            print(meOutput, {
                ...me,
                procedural_rank_name: proceduralRankName
            });
        } catch (error) {
            authStatus.textContent = 'Token invalid';
            meOutput.textContent = error.message;
        }
    }

    async function refreshTasks() {
        try {
            const tasks = await api('/tasks');
            list(tasksList, tasks, function (task) {
                return '#' + task.id + ' | ' + task.name + ' | ' + task.points + ' pts | creator #' + task.created_by;
            });
        } catch (error) {
            tasksList.innerHTML = '<li class="list-group-item text-danger"><small>' + error.message + '</small></li>';
        }
    }

    async function refreshClaims() {
        const filter = document.getElementById('claimFilter').value;
        try {
            const claims = await api('/claims?status=' + encodeURIComponent(filter));
            list(claimsList, claims, function (claim) {
                return '#' + claim.id + ' | Task #' + claim.task_id + ' (' + claim.task_name + ') | ' + claim.claimed_by_name + ' | ' + claim.status;
            });
        } catch (error) {
            claimsList.innerHTML = '<li class="list-group-item text-danger"><small>' + error.message + '</small></li>';
        }
    }

    async function refreshMyClaims() {
        try {
            const claims = await api('/claims/mine');
            list(myClaimsList, claims, function (claim) {
                return '#' + claim.id + ' | Task #' + claim.task_id + ' (' + claim.task_name + ') | ' + claim.points + ' pts | ' + claim.status;
            });
        } catch (error) {
            myClaimsList.innerHTML = '<li class="list-group-item text-danger"><small>' + error.message + '</small></li>';
        }
    }

    async function refreshScoreboard() {
        try {
            const board = await api('/scoreboard');
            const items = await Promise.all(board.map(async function (member) {
                const proceduralRankName = await fetchProceduralRankName(member.score, member.rank);
                return member.position + '. ' + member.name + ' | ' + member.score + ' pts | ' + proceduralRankName;
            }));

            list(scoreboardList, items, function (item) {
                return item;
            });
        } catch (error) {
            scoreboardList.innerHTML = '<li class="list-group-item text-danger"><small>' + error.message + '</small></li>';
        }
    }

    async function refreshVotingRound() {
        try {
            const round = await api('/voting/rounds/current');
            print(votingRoundOutput, round);
            if (round && round.id) {
                document.getElementById('closeRoundId').value = round.id;
                document.getElementById('resultRoundId').value = round.id;
            }
        } catch (error) {
            votingRoundOutput.textContent = error.message;
        }
    }

    async function refreshWishes() {
        try {
            const wishes = await api('/voting/wishes');
            list(wishesList, wishes, function (wish) {
                return '#' + wish.id + ' | ' + wish.name + ' | score: ' + wish.score + ' | by ' + wish.created_by_name;
            });
        } catch (error) {
            wishesList.innerHTML = '<li class="list-group-item text-danger"><small>' + error.message + '</small></li>';
        }
    }

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
        const familyId = Number(document.getElementById('memberFamilyId').value);
        const name = document.getElementById('memberName').value;

        try {
            const data = await api('/families/' + familyId + '/members', {
                method: 'POST',
                body: JSON.stringify({ name: name })
            });
            const url = window.location.origin + '/?token=' + data.auth_token;
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
            await refreshTasks();
        } catch (error) {
            tasksList.innerHTML = '<li class="list-group-item text-danger"><small>' + error.message + '</small></li>';
        }
    });

    document.getElementById('deleteTaskForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        const taskId = Number(document.getElementById('deleteTaskId').value);

        try {
            await api('/tasks/' + taskId, { method: 'DELETE' });
            await refreshTasks();
        } catch (error) {
            tasksList.innerHTML = '<li class="list-group-item text-danger"><small>' + error.message + '</small></li>';
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
            await refreshClaims();
            await refreshMyClaims();
        } catch (error) {
            claimsList.innerHTML = '<li class="list-group-item text-danger"><small>' + error.message + '</small></li>';
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
            await refreshAll();
        } catch (error) {
            reviewOutput.textContent = error.message;
        }
    });

    document.getElementById('createRoundBtn').addEventListener('click', async function () {
        try {
            const result = await api('/voting/rounds', { method: 'POST', body: JSON.stringify({}) });
            print(votingActionOutput, result);
            await refreshVoting();
        } catch (error) {
            votingActionOutput.textContent = error.message;
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
            print(votingActionOutput, result);
            await refreshVoting();
        } catch (error) {
            votingActionOutput.textContent = error.message;
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
            print(votingActionOutput, result);
            await refreshVoting();
            await refreshMe();
        } catch (error) {
            votingActionOutput.textContent = error.message;
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
            print(votingActionOutput, result);
            await refreshVoting();
        } catch (error) {
            votingActionOutput.textContent = error.message;
        }
    });

    document.getElementById('roundResultForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        const roundId = Number(document.getElementById('resultRoundId').value);

        try {
            const result = await api('/voting/rounds/' + roundId + '/result');
            print(votingActionOutput, result);
        } catch (error) {
            votingActionOutput.textContent = error.message;
        }
    });

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
        const tokenFromUrl = new URLSearchParams(window.location.search).get('token');
        if (tokenFromUrl) {
            setToken(tokenFromUrl);
            document.getElementById('sessionToken').value = tokenFromUrl;
        }

        await refreshMe();
        await refreshTasks();
        await refreshClaims();
        await refreshMyClaims();
        await refreshScoreboard();
        await refreshVoting();
    }

    refreshAll();
})();
