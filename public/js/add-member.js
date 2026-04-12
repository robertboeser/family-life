(function () {
    const form = document.getElementById('addMemberForm');
    const submitBtn = document.getElementById('submitBtn');
    const status = document.getElementById('status');
    const familyInfo = document.getElementById('familyInfo');

    function showStatus(message, type) {
        status.textContent = message;
        status.className = 'alert mt-3 mb-0 alert-' + type;
    }

    function setBusy(isBusy) {
        submitBtn.disabled = isBusy;
        submitBtn.textContent = isBusy ? 'Adding...' : 'Add Member';
    }

    async function initPage() {
        const token = window.FamilyLifeAuth.getToken();
        if (!token) {
            showStatus('Login required. Use a member token in the URL hash (#token=...).', 'warning');
            window.location.href = '/index.html';
            return;
        }

        // Set up back/cancel button to maintain token
        const cancelBtn = document.getElementById('cancelBtn');
        if (cancelBtn) {
            cancelBtn.href = '/dashboard.html#token=' + encodeURIComponent(token);
        }

        document.getElementById('backBtn').href = '/dashboard.html#token=' + encodeURIComponent(token);

        try {
            const me = await window.FamilyLifeAuth.api('/me');
            familyInfo.textContent = 'Adding member to ' + me.family_name;
        } catch (error) {
            showStatus('Failed to load family info: ' + error.message, 'danger');
            familyInfo.textContent = 'Error loading family information';
        }
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const memberName = document.getElementById('memberName').value.trim();

        if (!memberName) {
            showStatus('Please provide a member name.', 'warning');
            return;
        }

        setBusy(true);

        try {
            const me = await window.FamilyLifeAuth.api('/me');
            const familyId = me.family_id;

            const newMember = await window.FamilyLifeAuth.api('/families/' + familyId + '/members', {
                method: 'POST',
                body: JSON.stringify({ name: memberName })
            });

            showStatus('Member added successfully! Redirecting to dashboard...', 'success');
            setTimeout(function () {
                window.location.href = '/dashboard.html#token=' + encodeURIComponent(newMember.auth_token);
            }, 1000);
        } catch (error) {
            showStatus(error.message, 'danger');
            setBusy(false);
        }
    });

    document.getElementById('logoutBtn').addEventListener('click', function () {
        window.location.href = '/index.html';
    });

    initPage();
})();
