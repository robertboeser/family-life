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
    <title><?= tp($lang, 'dashboard', 'title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/member.css">
</head>
<body>
    <nav class="navbar navbar-expand bg-white border-bottom sticky-top">
        <div class="container">
            <span class="navbar-brand fw-semibold">Family Life</span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="/index.php" class="btn btn-sm btn-outline-secondary"><?= tp($lang, 'dashboard', 'start_page') ?></a>
                <button id="logoutBtn" class="btn btn-sm btn-outline-danger" type="button"><?= tp($lang, 'dashboard', 'logout') ?></button>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div id="authError" class="alert alert-warning d-none" role="alert"></div>

        <section class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <h1 class="h4 mb-2"><?= tp($lang, 'dashboard', 'member_dashboard') ?></h1>
                    <p id="memberSummary" class="mb-0 text-muted"><?= tp($lang, 'dashboard', 'loading_profile') ?></p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a id="votingLink" href="/voting.php" class="btn btn-sm btn-outline-secondary"><?= tp($lang, 'dashboard', 'voting') ?></a>
                    <a id="tasksLink" href="/tasks.php" class="btn btn-sm btn-outline-secondary"><?= tp($lang, 'dashboard', 'tasks') ?></a>
                    <button id="copyLinkBtn" class="btn btn-sm btn-outline-secondary" type="button"><?= tp($lang, 'dashboard', 'copy_access_link') ?></button>
                    <a id="addMemberLink" href="/add-member.php" class="btn btn-sm btn-outline-primary"><?= tp($lang, 'dashboard', 'add_member') ?></a>
                </div>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-lg-6">
                <section class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h2 class="h6 mb-0"><?= tp($lang, 'dashboard', 'current_claims') ?></h2>
                        <div class="d-flex align-items-center gap-2">
                            <button id="showClaimTaskBtn" class="btn btn-sm btn-outline-secondary" type="button"><?= tp($lang, 'dashboard', 'claim_task') ?></button>
                            <button id="refreshClaimsBtn" class="btn btn-sm btn-outline-primary" type="button"><?= tp($lang, 'dashboard', 'refresh') ?></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="claimTaskForm" class="row g-2 mb-3 d-none" novalidate>
                            <div class="col-12 col-md-8">
                                <label for="claimTaskSelect" class="form-label mb-1"><?= tp($lang, 'dashboard', 'choose_family_task') ?></label>
                                <select id="claimTaskSelect" class="form-select form-select-sm" required>
                                    <option value=""><?= tp($lang, 'dashboard', 'select_task') ?></option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                                <button id="submitClaimTaskBtn" class="btn btn-sm btn-primary w-100" type="submit"><?= tp($lang, 'dashboard', 'claim') ?></button>
                                <button id="cancelClaimTaskBtn" class="btn btn-sm btn-outline-secondary" type="button"><?= tp($lang, 'dashboard', 'cancel') ?></button>
                            </div>
                        </form>
                        <div id="claimTaskStatus" class="alert d-none mb-3 py-2" role="alert"></div>
                        <div id="claimsContainer" class="table-responsive"></div>
                    </div>
                </section>
            </div>

            <div class="col-lg-6">
                <section class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h2 class="h6 mb-0"><?= tp($lang, 'dashboard', 'family_scoreboard') ?></h2>
                        <button id="refreshScoreboardBtn" class="btn btn-sm btn-outline-primary" type="button"><?= tp($lang, 'dashboard', 'refresh') ?></button>
                    </div>
                    <div class="card-body">
                        <div id="scoreboardContainer" class="table-responsive"></div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script src="/js/auth.js" defer></script>
    <script>window.FamilyLifeTranslations = <?= json_encode(tj($lang, 'dashboard'), JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="/js/dashboard.js" defer></script>
</body>
</html>
