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
    <title><?= tp($lang, 'index', 'title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/member.css">
</head>
<body>
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <section class="hero card shadow-sm mb-4">
                    <div class="card-body p-4 p-md-5">
                        <p class="eyebrow mb-2">Family Life</p>
                        <h1 class="display-6 mb-3"><?= tp($lang, 'index', 'hero_heading') ?></h1>
                        <p class="lead mb-0"><?= tp($lang, 'index', 'hero_text') ?></p>
                    </div>
                </section>

                <section class="card shadow-sm">
                    <div class="card-body p-4">
                        <form id="onboardForm" class="row g-3" novalidate>
                            <div class="col-md-6">
                                <label for="familyName" class="form-label"><?= tp($lang, 'index', 'family_name_label') ?></label>
                                <input id="familyName" type="text" class="form-control" placeholder="<?= tp($lang, 'index', 'family_name_placeholder') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="memberName" class="form-label"><?= tp($lang, 'index', 'first_member_name_label') ?></label>
                                <input id="memberName" type="text" class="form-control" placeholder="Alex" required>
                            </div>
                            <div class="col-12 d-flex flex-column flex-md-row gap-2">
                                <button id="submitBtn" class="btn btn-primary" type="submit"><?= tp($lang, 'index', 'create_family_member_btn') ?></button>
                                <a href="/dashboard.php" class="btn btn-outline-secondary"><?= tp($lang, 'index', 'open_dashboard_btn') ?></a>
                            </div>
                        </form>

                        <div id="status" class="alert d-none mt-3 mb-0" role="alert"></div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script src="/js/auth.js" defer></script>
    <script>window.FamilyLifeTranslations = <?= json_encode(tj($lang, 'index'), JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="/js/index.js" defer></script>
</body>
</html>
