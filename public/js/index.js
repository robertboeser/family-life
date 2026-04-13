(function () {
    const i18n = window.FamilyLifeTranslations || {};
    const form = document.getElementById('onboardForm');
    const submitBtn = document.getElementById('submitBtn');
    const status = document.getElementById('status');

    function t(key, fallback) {
        return Object.prototype.hasOwnProperty.call(i18n, key) ? i18n[key] : fallback;
    }

    function showStatus(message, type) {
        status.textContent = message;
        status.className = 'alert mt-3 mb-0 alert-' + type;
    }

    function setBusy(isBusy) {
        submitBtn.disabled = isBusy;
        submitBtn.textContent = isBusy ? t('creating', 'Creating...') : t('create_family_member_btn', 'Create Family + Member');
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const familyName = document.getElementById('familyName').value.trim();
        const memberName = document.getElementById('memberName').value.trim();

        if (!familyName || !memberName) {
            showStatus(t('both_names_required', 'Please provide both names.'), 'warning');
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

            window.location.href = '/dashboard.php#token=' + encodeURIComponent(member.auth_token);
        } catch (error) {
            showStatus(error.message, 'danger');
            setBusy(false);
        }
    });
})();
