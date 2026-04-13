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
    <title><?= tp($lang, 'tasks', 'title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/member.css">
</head>
<body>
    <nav class="navbar navbar-expand bg-white border-bottom sticky-top">
        <div class="container">
            <span class="navbar-brand fw-semibold">Family Life</span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <a href="/index.php" class="btn btn-sm btn-outline-secondary"><?= tp($lang, 'tasks', 'start_page') ?></a>
                <button id="logoutBtn" class="btn btn-sm btn-outline-danger" type="button"><?= tp($lang, 'tasks', 'logout') ?></button>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div id="authError" class="alert alert-warning d-none" role="alert"></div>

        <section class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <a id="backBtn" href="/dashboard.php" class="btn btn-sm btn-outline-secondary mb-3"><?= tp($lang, 'tasks', 'back_to_dashboard') ?></a>
                    <h1 class="h4 mb-2"><?= tp($lang, 'tasks', 'heading') ?></h1>
                    <p id="familyInfo" class="mb-0 text-muted"><?= tp($lang, 'tasks', 'loading_family_info') ?></p>
                </div>
                <button id="showCreateTaskBtn" class="btn btn-sm btn-outline-primary" type="button"><?= tp($lang, 'tasks', 'create_new_task') ?></button>
            </div>
        </section>

        <section id="createTaskCard" class="card shadow-sm mb-4 d-none">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0"><?= tp($lang, 'tasks', 'create_task') ?></h2>
            </div>
            <div class="card-body">
                <form id="createTaskForm" class="row g-3" novalidate>
                    <div class="col-md-8">
                        <label for="taskName" class="form-label"><?= tp($lang, 'tasks', 'task_name') ?></label>
                        <input id="taskName" type="text" class="form-control" placeholder="<?= tp($lang, 'tasks', 'task_placeholder') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="taskPoints" class="form-label"><?= tp($lang, 'tasks', 'points') ?></label>
                        <input id="taskPoints" type="number" class="form-control" min="1" step="1" value="10" required>
                    </div>
                    <div class="col-12 d-flex flex-column flex-md-row gap-2">
                        <button id="createTaskSubmitBtn" class="btn btn-primary" type="submit"><?= tp($lang, 'tasks', 'create_task') ?></button>
                        <button id="cancelCreateTaskBtn" class="btn btn-outline-secondary" type="button"><?= tp($lang, 'tasks', 'cancel') ?></button>
                    </div>
                </form>
                <div id="status" class="alert d-none mt-3 mb-0" role="alert"></div>
            </div>
        </section>

        <section class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><?= tp($lang, 'tasks', 'all_family_tasks') ?></h2>
                <button id="refreshTasksBtn" class="btn btn-sm btn-outline-primary" type="button"><?= tp($lang, 'tasks', 'refresh') ?></button>
            </div>
            <div class="card-body">
                <div id="tasksContainer" class="table-responsive"></div>
            </div>
        </section>
    </main>

    <script src="/js/auth.js" defer></script>
    <script>window.FamilyLifeTranslations = <?= json_encode(tj($lang, 'tasks'), JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="/js/tasks.js" defer></script>
</body>
</html>
