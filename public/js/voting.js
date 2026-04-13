(function () {
    const authError = document.getElementById('authError');
    const votingSummary = document.getElementById('votingSummary');
    const votingBalance = document.getElementById('votingBalance');
    const closeRoundInfo = document.getElementById('closeRoundInfo');
    const closeRoundStatus = document.getElementById('closeRoundStatus');
    const closeRoundBtn = document.getElementById('closeRoundBtn');
    const voteStatus = document.getElementById('voteStatus');
    const wishesContainer = document.getElementById('wishesContainer');
    const winnersContainer = document.getElementById('winnersContainer');
    const showCreateWishBtn = document.getElementById('showCreateWishBtn');
    const createWishCard = document.getElementById('createWishCard');
    const createWishForm = document.getElementById('createWishForm');
    const createWishSubmitBtn = document.getElementById('createWishSubmitBtn');
    const wishStatus = document.getElementById('wishStatus');
    let currentMemberId = null;
    let hasOwnActiveWish = false;
    let currentRoundStatus = 'none';
    let currentAvailablePoints = 0;
    let currentRoundId = null;
    let currentCloseApprovals = 0;
    let currentRequiredApprovals = 1;

    function showAuthError(message) {
        authError.textContent = message;
        authError.classList.remove('d-none');
    }

    function showWishStatus(message, type) {
        wishStatus.textContent = message;
        wishStatus.className = 'alert mt-3 mb-0 alert-' + type;
    }

    function hideWishStatus() {
        wishStatus.className = 'alert d-none mt-3 mb-0';
        wishStatus.textContent = '';
    }

    function showVoteStatus(message, type) {
        voteStatus.textContent = message;
        voteStatus.className = 'alert mb-3 alert-' + type;
    }

    function hideVoteStatus() {
        voteStatus.className = 'alert d-none mb-3';
        voteStatus.textContent = '';
    }

    function showCloseRoundStatus(message, type) {
        closeRoundStatus.textContent = message;
        closeRoundStatus.className = 'alert mb-0 alert-' + type;
    }

    function hideCloseRoundStatus() {
        closeRoundStatus.className = 'alert d-none mb-0';
        closeRoundStatus.textContent = '';
    }

    function setCreateBusy(isBusy) {
        createWishSubmitBtn.disabled = isBusy;
        createWishSubmitBtn.textContent = isBusy ? 'Creating...' : 'Create Wish';
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderCreateWishVisibility() {
        const canCreateWish = currentRoundStatus === 'open' && currentMemberId !== null && !hasOwnActiveWish;
        showCreateWishBtn.classList.toggle('d-none', !canCreateWish);

        if (!canCreateWish) {
            createWishCard.classList.add('d-none');
        }
    }

    function renderWishes(wishes) {
        hasOwnActiveWish = Array.isArray(wishes) && wishes.some(function (wish) {
            return wish.created_by === currentMemberId;
        });

        renderCreateWishVisibility();

        if (!Array.isArray(wishes) || wishes.length === 0) {
            if (currentRoundStatus === 'open') {
                wishesContainer.innerHTML = '<p class="text-muted mb-0">No active wishes yet.</p>';
                return;
            }

            wishesContainer.innerHTML = '<p class="text-muted mb-0">No open voting round right now.</p>';
            return;
        }

        const rows = wishes.map(function (wish) {
            const ownerLabel = wish.created_by === currentMemberId ? ' <span class="badge text-bg-primary">Yours</span>' : '';
            const actionCell = currentAvailablePoints > 0
                ? '<div class="d-flex gap-2 align-items-center">'
                    + '<input class="form-control form-control-sm" type="number" min="1" max="' + currentAvailablePoints + '" value="1" data-vote-amount-for="' + wish.id + '">'
                    + '<button class="btn btn-sm btn-outline-primary" type="button" data-vote-wish-id="' + wish.id + '">Vote</button>'
                    + '</div>'
                : '<span class="text-muted small">No usable points</span>';

            return '<tr>'
                + '<td>#' + wish.id + '</td>'
                + '<td>' + escapeHtml(wish.name) + ownerLabel + '</td>'
                + '<td>' + wish.score + '</td>'
                + '<td>' + escapeHtml(wish.created_by_name) + '</td>'
                + '<td>' + actionCell + '</td>'
                + '</tr>';
        }).join('');

        wishesContainer.innerHTML = '<table class="table table-sm align-middle mb-0">'
            + '<thead><tr><th>ID</th><th>Wish</th><th>Score</th><th>Created By</th><th>Vote</th></tr></thead>'
            + '<tbody>' + rows + '</tbody>'
            + '</table>';
    }

    function renderWinners(winners) {
        if (!Array.isArray(winners) || winners.length === 0) {
            winnersContainer.innerHTML = '<p class="text-muted mb-0">No winning wishes yet.</p>';
            return;
        }

        const rows = winners.map(function (winner) {
            return '<tr>'
                + '<td>#' + winner.round_id + '</td>'
                + '<td>' + escapeHtml(winner.wish_name) + '</td>'
                + '<td>' + winner.wish_score + '</td>'
                + '<td>' + escapeHtml(winner.winner_name) + '</td>'
                + '</tr>';
        }).join('');

        winnersContainer.innerHTML = '<table class="table table-sm align-middle mb-0">'
            + '<thead><tr><th>Round</th><th>Wish</th><th>Score</th><th>Winner</th></tr></thead>'
            + '<tbody>' + rows + '</tbody>'
            + '</table>';
    }

    function renderCloseRoundState() {
        if (currentRoundStatus !== 'open' || currentRoundId === null) {
            closeRoundInfo.textContent = 'No open round to close right now.';
            closeRoundBtn.disabled = true;
            return;
        }

        closeRoundInfo.textContent = 'Approvals: ' + currentCloseApprovals + ' / ' + currentRequiredApprovals;
        closeRoundBtn.disabled = false;
    }

    async function ensureOpenRound() {
        let round = await window.FamilyLifeAuth.api('/voting/rounds/current');

        if (round.status !== 'none') {
            return round;
        }

        try {
            await window.FamilyLifeAuth.api('/voting/rounds', {
                method: 'POST'
            });
        } catch (error) {
            if (error.message !== 'An open voting round already exists') {
                throw error;
            }
        }

        round = await window.FamilyLifeAuth.api('/voting/rounds/current');
        return round;
    }

    async function refreshVotingPage() {
        const me = await window.FamilyLifeAuth.api('/me');
        currentMemberId = me.id;

        const round = await ensureOpenRound();
        currentRoundStatus = round.status;
        currentRoundId = round.id;
        currentCloseApprovals = round.closure_approvals_count || 0;
        currentRequiredApprovals = round.required_close_approvals || 1;

        const balance = await window.FamilyLifeAuth.api('/voting/balance');
        currentAvailablePoints = balance.available_points;
        votingBalance.textContent = 'Usable points: ' + balance.available_points;

        if (round.status === 'open') {
            votingSummary.textContent = 'Open voting round for ' + me.family_name;
        } else {
            votingSummary.textContent = 'No open voting round for ' + me.family_name;
        }

        const wishes = await window.FamilyLifeAuth.api('/voting/wishes');
        renderWishes(wishes);

        const winners = await window.FamilyLifeAuth.api('/voting/winners');
        renderWinners(winners);

        renderCloseRoundState();
    }

    function init() {
        const token = window.FamilyLifeAuth.getToken();
        if (!token) {
            showAuthError('Login required. Use a member token in the URL hash (#token=...) or create a family on the start page.');
            window.location.href = '/index.html';
            return;
        }

        document.getElementById('backBtn').href = '/dashboard.html#token=' + encodeURIComponent(token);

        refreshVotingPage().catch(function (error) {
            showAuthError(error.message);
            wishesContainer.innerHTML = '';
        });
    }

    document.getElementById('logoutBtn').addEventListener('click', function () {
        window.location.href = '/index.html';
    });

    document.getElementById('refreshVotingBtn').addEventListener('click', function () {
        hideVoteStatus();
        hideCloseRoundStatus();
        refreshVotingPage().catch(function (error) {
            showAuthError(error.message);
        });
    });

    closeRoundBtn.addEventListener('click', function () {
        if (currentRoundStatus !== 'open' || !currentRoundId) {
            return;
        }

        closeRoundBtn.disabled = true;
        closeRoundBtn.textContent = 'Submitting...';
        hideCloseRoundStatus();

        window.FamilyLifeAuth.api('/voting/rounds/' + currentRoundId + '/approve-close', {
            method: 'POST'
        }).then(function (result) {
            if (result.status === 'closed') {
                showCloseRoundStatus('Round closed. Winning wish: ' + result.closed_wish_name + '. A new round was created.', 'success');
            } else {
                showCloseRoundStatus(
                    'Close approval recorded (' + result.approvals_count + ' / ' + (result.required_approvals || currentRequiredApprovals) + ').',
                    'info'
                );
            }

            return refreshVotingPage();
        }).catch(function (error) {
            showCloseRoundStatus(error.message, 'danger');
        }).finally(function () {
            closeRoundBtn.disabled = false;
            closeRoundBtn.textContent = 'Approve Closing Round';
        });
    });

    wishesContainer.addEventListener('click', function (event) {
        const voteButton = event.target.closest('[data-vote-wish-id]');
        if (!voteButton) {
            return;
        }

        const wishId = parseInt(voteButton.getAttribute('data-vote-wish-id'), 10);
        const amountInput = wishesContainer.querySelector('[data-vote-amount-for="' + wishId + '"]');
        const amount = amountInput ? parseInt(amountInput.value, 10) : NaN;

        if (!Number.isInteger(wishId) || wishId <= 0) {
            return;
        }

        if (!Number.isInteger(amount) || amount <= 0) {
            showVoteStatus('Please enter a valid vote amount.', 'warning');
            return;
        }

        if (amount > currentAvailablePoints) {
            showVoteStatus('Vote amount exceeds your usable points.', 'warning');
            return;
        }

        voteButton.disabled = true;
        voteButton.textContent = 'Voting...';
        hideVoteStatus();

        window.FamilyLifeAuth.api('/voting/votes', {
            method: 'POST',
            body: JSON.stringify({
                wish_id: wishId,
                amount: amount
            })
        }).then(function () {
            showVoteStatus('Vote added successfully.', 'success');
            return refreshVotingPage();
        }).catch(function (error) {
            showVoteStatus(error.message, 'danger');
        }).finally(function () {
            if (document.body.contains(voteButton)) {
                voteButton.disabled = false;
                voteButton.textContent = 'Vote';
            }
        });
    });

    showCreateWishBtn.addEventListener('click', function () {
        hideWishStatus();
        createWishCard.classList.remove('d-none');
        document.getElementById('wishName').focus();
    });

    document.getElementById('cancelCreateWishBtn').addEventListener('click', function () {
        createWishCard.classList.add('d-none');
        createWishForm.reset();
        hideWishStatus();
    });

    createWishForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const wishName = document.getElementById('wishName').value.trim();
        if (!wishName) {
            showWishStatus('Please provide a wish name.', 'warning');
            return;
        }

        setCreateBusy(true);
        hideWishStatus();

        window.FamilyLifeAuth.api('/voting/wishes', {
            method: 'POST',
            body: JSON.stringify({ name: wishName })
        }).then(function () {
            showWishStatus('Wish created successfully.', 'success');
            return refreshVotingPage();
        }).then(function () {
            setCreateBusy(false);
            createWishForm.reset();
        }).catch(function (error) {
            showWishStatus(error.message, 'danger');
            setCreateBusy(false);
        });
    });

    init();
})();
