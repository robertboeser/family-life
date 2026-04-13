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
    <title><?= tp($lang, 'voting', 'title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/member.css">
</head>
<body>
    <nav class="navbar navbar-expand bg-white border-bottom sticky-top">
        <div class="container">
            <span class="navbar-brand fw-semibold">Family Life</span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="/index.php" class="btn btn-sm btn-outline-secondary"><?= tp($lang, 'voting', 'start_page') ?></a>
                <button id="logoutBtn" class="btn btn-sm btn-outline-danger" type="button"><?= tp($lang, 'voting', 'logout') ?></button>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div id="authError" class="alert alert-warning d-none" role="alert"></div>

        <section class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <a id="backBtn" href="/dashboard.php" class="btn btn-sm btn-outline-secondary mb-3"><?= tp($lang, 'voting', 'back_to_dashboard') ?></a>
                    <h1 class="h4 mb-2"><?= tp($lang, 'voting', 'heading') ?></h1>
                    <p id="votingSummary" class="mb-0 text-muted"><?= tp($lang, 'voting', 'loading_voting_info') ?></p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                    <span id="votingBalance" class="badge text-bg-light border text-dark px-3 py-2"><?= tp($lang, 'voting', 'usable_points') ?></span>
                    <button id="showCreateWishBtn" class="btn btn-sm btn-outline-primary d-none" type="button"><?= tp($lang, 'voting', 'create_wish') ?></button>
                </div>
            </div>
        </section>

        <section id="createWishCard" class="card shadow-sm mb-4 d-none">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0"><?= tp($lang, 'voting', 'create_wish_title') ?></h2>
            </div>
            <div class="card-body">
                <form id="createWishForm" class="row g-3" novalidate>
                    <div class="col-12">
                        <label for="wishName" class="form-label"><?= tp($lang, 'voting', 'wish_name') ?></label>
                        <input id="wishName" type="text" class="form-control" placeholder="<?= tp($lang, 'voting', 'wish_placeholder') ?>" required>
                    </div>
                    <div class="col-12 d-flex flex-column flex-md-row gap-2">
                        <button id="createWishSubmitBtn" class="btn btn-primary" type="submit"><?= tp($lang, 'voting', 'create_wish_title') ?></button>
                        <button id="cancelCreateWishBtn" class="btn btn-outline-secondary" type="button"><?= tp($lang, 'voting', 'cancel') ?></button>
                    </div>
                </form>
                <div id="wishStatus" class="alert d-none mt-3 mb-0" role="alert"></div>
            </div>
        </section>

        <section class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><?= tp($lang, 'voting', 'active_wishes') ?></h2>
                <button id="refreshVotingBtn" class="btn btn-sm btn-outline-primary" type="button"><?= tp($lang, 'voting', 'refresh') ?></button>
            </div>
            <div class="card-body">
                <div id="voteStatus" class="alert d-none mb-3" role="alert"></div>
                <div id="wishesContainer" class="table-responsive"></div>
            </div>
        </section>

        <section class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><?= tp($lang, 'voting', 'close_current_round') ?></h2>
                <button id="closeRoundBtn" class="btn btn-sm btn-outline-danger" type="button"><?= tp($lang, 'voting', 'approve_closing_round') ?></button>
            </div>
            <div class="card-body">
                <p id="closeRoundInfo" class="small text-muted mb-2"><?= tp($lang, 'voting', 'loading_close_round') ?></p>
                <div id="closeRoundStatus" class="alert d-none mb-0" role="alert"></div>
            </div>
        </section>

        <section class="card shadow-sm mt-4">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0"><?= tp($lang, 'voting', 'winning_wishes') ?></h2>
            </div>
            <div class="card-body">
                <div id="winnersContainer" class="table-responsive"></div>
            </div>
        </section>
    </main>

    <script src="/js/auth.js" defer></script>
    <script>window.FamilyLifeTranslations = <?= json_encode(tj($lang, 'voting'), JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="/js/voting.js" defer></script>
</body>
</html>
