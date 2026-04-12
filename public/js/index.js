(function () {
    const form = document.getElementById('onboardForm');
    const submitBtn = document.getElementById('submitBtn');
    const status = document.getElementById('status');

    function showStatus(message, type) {
        status.textContent = message;
        status.className = 'alert mt-3 mb-0 alert-' + type;
    }

    function setBusy(isBusy) {
        submitBtn.disabled = isBusy;
        submitBtn.textContent = isBusy ? 'Creating...' : 'Create Family + Member';
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const familyName = document.getElementById('familyName').value.trim();
        const memberName = document.getElementById('memberName').value.trim();

        if (!familyName || !memberName) {
            showStatus('Please provide both names.', 'warning');
            return;
        }

        setBusy(true);

        try {
            const family = await window.FamilyLifeAuth.api('/families', {
                method: 'POST',
                body: JSON.stringify({ name: familyName })
            });

            const member = await window.FamilyLifeAuth.api('/families/' + family.id + '/members', {
                method: 'POST',
                body: JSON.stringify({ name: memberName })
            });

            window.location.href = '/dashboard.html#token=' + encodeURIComponent(member.auth_token);
        } catch (error) {
            showStatus(error.message, 'danger');
            setBusy(false);
        }
    });
})();
