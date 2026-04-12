(function () {
    function getToken() {
        const hash = window.location.hash.replace(/^#/, '');
        if (!hash) {
            return '';
        }

        return new URLSearchParams(hash).get('token') || '';
    }

    function readTokenFromHash() {
        return getToken();
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

    window.FamilyLifeAuth = {
        getToken: getToken,
        readTokenFromHash: readTokenFromHash,
        api: api
    };
})();
