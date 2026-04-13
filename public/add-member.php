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
    <title><?= tp($lang, 'add_member', 'title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/member.css">
</head>
<body>
    <nav class="navbar navbar-expand bg-white border-bottom sticky-top">
        <div class="container">
            <span class="navbar-brand fw-semibold">Family Life</span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="/index.php" class="btn btn-sm btn-outline-secondary"><?= tp($lang, 'add_member', 'start_page') ?></a>
                <button id="logoutBtn" class="btn btn-sm btn-outline-danger" type="button"><?= tp($lang, 'add_member', 'logout') ?></button>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div id="authError" class="alert alert-warning d-none" role="alert"></div>

        <section class="card shadow-sm mb-4">
            <div class="card-body">
                <a id="backBtn" href="/dashboard.php" class="btn btn-sm btn-outline-secondary mb-3"><?= tp($lang, 'add_member', 'back_to_dashboard') ?></a>
                <h1 class="h4 mb-2"><?= tp($lang, 'add_member', 'heading') ?></h1>
                <p id="familyInfo" class="mb-0 text-muted"><?= tp($lang, 'add_member', 'loading_family_info') ?></p>
            </div>
        </section>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <section class="card shadow-sm">
                    <div class="card-body p-4">
                        <form id="addMemberForm" class="g-3" novalidate>
                            <div class="mb-3">
                                <label for="memberName" class="form-label"><?= tp($lang, 'add_member', 'member_name') ?></label>
                                <input id="memberName" type="text" class="form-control" placeholder="Jordan" required>
                            </div>
                            <div class="d-flex flex-column flex-md-row gap-2">
                                <button id="submitBtn" class="btn btn-primary" type="submit"><?= tp($lang, 'add_member', 'add_member') ?></button>
                                <a id="cancelBtn" href="/dashboard.php" class="btn btn-outline-secondary"><?= tp($lang, 'add_member', 'cancel') ?></a>
                            </div>
                        </form>

                        <div id="status" class="alert d-none mt-3 mb-0" role="alert"></div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script src="/js/auth.js" defer></script>
    <script>window.FamilyLifeTranslations = <?= json_encode(tj($lang, 'add_member'), JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="/js/add-member.js" defer></script>
</body>
</html>
