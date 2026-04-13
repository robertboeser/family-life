<?php

declare(strict_types=1);

require __DIR__ . '/includes/i18n.php';

$lang = uiLang($_SERVER);
?>
<!doctype html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= tp($lang, 'claims', 'title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/member.css">
</head>
<body>
    <nav class="navbar navbar-expand bg-white border-bottom sticky-top">
        <div class="container">
            <span class="navbar-brand fw-semibold">Family Life</span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="/index.php" class="btn btn-sm btn-outline-secondary"><?= tp($lang, 'claims', 'start_page') ?></a>
                <button id="logoutBtn" class="btn btn-sm btn-outline-danger" type="button"><?= tp($lang, 'claims', 'logout') ?></button>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div id="authError" class="alert alert-warning d-none" role="alert"></div>

        <section class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <a id="backBtn" href="/dashboard.php" class="btn btn-sm btn-outline-secondary mb-3"><?= tp($lang, 'claims', 'back_to_dashboard') ?></a>
                    <h1 class="h4 mb-2"><?= tp($lang, 'claims', 'heading') ?></h1>
                    <p id="familyInfo" class="mb-0 text-muted"><?= tp($lang, 'claims', 'loading_family_info') ?></p>
                </div>
            </div>
        </section>

        <section class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0"><?= tp($lang, 'claims', 'filters') ?></h2>
            </div>
            <div class="card-body">
                <form id="filtersForm" class="row g-3" novalidate>
                    <div class="col-md-6">
                        <label for="memberFilter" class="form-label"><?= tp($lang, 'claims', 'member') ?></label>
                        <select id="memberFilter" class="form-select">
                            <option value=""><?= tp($lang, 'claims', 'all_members') ?></option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="statusFilter" class="form-label"><?= tp($lang, 'claims', 'status') ?></label>
                        <select id="statusFilter" class="form-select">
                            <option value="all"><?= tp($lang, 'claims', 'status_all') ?></option>
                            <option value="pending"><?= tp($lang, 'claims', 'status_pending') ?></option>
                            <option value="approved"><?= tp($lang, 'claims', 'status_approved') ?></option>
                            <option value="rejected"><?= tp($lang, 'claims', 'status_rejected') ?></option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button id="refreshClaimsBtn" class="btn btn-sm btn-outline-primary" type="submit"><?= tp($lang, 'claims', 'apply_filters') ?></button>
                    </div>
                </form>
            </div>
        </section>

        <section class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><?= tp($lang, 'claims', 'all_claims') ?></h2>
                <button id="refreshOnlyBtn" class="btn btn-sm btn-outline-primary" type="button"><?= tp($lang, 'claims', 'refresh') ?></button>
            </div>
            <div class="card-body">
                <div id="claimsContainer" class="table-responsive"></div>
            </div>
        </section>
    </main>

    <script src="/js/auth.js" defer></script>
    <script>window.FamilyLifeTranslations = <?= json_encode(tj($lang, 'claims'), JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="/js/claims.js" defer></script>
</body>
</html>
