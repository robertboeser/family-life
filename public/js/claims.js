(function () {
    const i18n = window.FamilyLifeTranslations || {};
    const locale = i18n.locale || 'en-US';
    const authError = document.getElementById('authError');
    const familyInfo = document.getElementById('familyInfo');
    const filtersForm = document.getElementById('filtersForm');
    const memberFilter = document.getElementById('memberFilter');
    const statusFilter = document.getElementById('statusFilter');
    const claimsContainer = document.getElementById('claimsContainer');

    function t(key, fallback) {
        return Object.prototype.hasOwnProperty.call(i18n, key) ? i18n[key] : fallback;
    }

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

    function localizeStatus(status) {
        if (status === 'pending') {
            return t('status_pending', 'Pending');
        }

        if (status === 'approved') {
            return t('status_approved', 'Approved');
        }

        if (status === 'rejected') {
            return t('status_rejected', 'Rejected');
        }

        return status;
    }

    function renderMemberOptions(members) {
        const options = ['<option value="">' + escapeHtml(t('all_members', 'All members')) + '</option>'];

        if (Array.isArray(members)) {
            members.forEach(function (member) {
                options.push('<option value="' + member.id + '">' + escapeHtml(member.name) + '</option>');
            });
        }

        memberFilter.innerHTML = options.join('');
    }

    function renderClaims(claims) {
        if (!Array.isArray(claims) || claims.length === 0) {
            claimsContainer.innerHTML = '<p class="text-muted mb-0">' + t('no_claims', 'No claims found for this filter.') + '</p>';
            return;
        }

        const rows = claims.map(function (claim) {
            return '<tr>'
                + '<td>#' + claim.id + '</td>'
                + '<td>' + escapeHtml(claim.claimed_by_name) + '</td>'
                + '<td>' + escapeHtml(claim.task_name) + '</td>'
                + '<td>' + claim.points + '</td>'
                + '<td><span class="badge text-bg-secondary">' + escapeHtml(localizeStatus(claim.status)) + '</span></td>'
                + '<td>' + escapeHtml(formatDate(claim.created_at)) + '</td>'
                + '</tr>';
        }).join('');

        claimsContainer.innerHTML = '<table class="table table-sm align-middle mb-0">'
            + '<thead><tr><th>ID</th><th>' + t('member', 'Member') + '</th><th>' + t('task', 'Task') + '</th><th>' + t('points', 'Points') + '</th><th>' + t('status', 'Status') + '</th><th>' + t('created', 'Created') + '</th></tr></thead>'
            + '<tbody>' + rows + '</tbody>'
            + '</table>';
    }

    function buildClaimsPath() {
        const params = new URLSearchParams();
        const status = statusFilter.value || 'all';

        params.set('status', status);

        if (memberFilter.value) {
            params.set('member_id', memberFilter.value);
        }

        return '/claims?' + params.toString();
    }

    async function refreshClaims() {
        const claims = await window.FamilyLifeAuth.api(buildClaimsPath());
        renderClaims(claims);
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
            familyInfo.textContent = t('claims_for', 'Claims for ') + me.family_name;

            const members = await window.FamilyLifeAuth.api('/scoreboard');
            renderMemberOptions(members);

            await refreshClaims();
        } catch (error) {
            showAuthError(error.message);
            claimsContainer.innerHTML = '';
        }
    }

    filtersForm.addEventListener('submit', function (event) {
        event.preventDefault();
        refreshClaims().catch(function (error) {
            showAuthError(error.message);
        });
    });

    document.getElementById('refreshOnlyBtn').addEventListener('click', function () {
        refreshClaims().catch(function (error) {
            showAuthError(error.message);
        });
    });

    document.getElementById('logoutBtn').addEventListener('click', function () {
        window.location.href = 'index.php';
    });

    init();
})();
