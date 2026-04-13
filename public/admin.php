<?php

declare(strict_types=1);

chdir(dirname(__DIR__));
require_once 'backend/config/constants.php';
require_once 'backend/config/database.php';
require_once 'public/includes/i18n.php';

session_start();

$error = '';
$message = '';
$families = [];
$lang = uiLang($_SERVER);

// Handle logout
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_destroy();
    header('Location: /admin.php');
    exit;
}

// Handle login
if (!isset($_SESSION['admin_authenticated'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (hash_equals(ADMIN_PASSWORD, (string)$_POST['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_authenticated'] = true;
        } else {
            $error = tm($lang, 'admin', 'invalid_password');
        }
    }
}

// Handle authenticated actions
if (isset($_SESSION['admin_authenticated'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'reinitialize') {
            reinitializeDatabase();
            $message = tm($lang, 'admin', 'db_reinitialized');
        }
    }

    $families = listFamilies();
}

$isAuthenticated = isset($_SESSION['admin_authenticated']);
?><!doctype html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= tp($lang, 'admin', 'title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
    <div class="container">
        <span class="navbar-brand fw-bold">🏡 Family Life - Admin</span>
        <?php if ($isAuthenticated): ?>
        <form method="POST" class="ms-auto">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="btn btn-outline-light btn-sm"><?= tp($lang, 'admin', 'logout') ?></button>
        </form>
        <?php endif; ?>
    </div>
</nav>

<div class="container" style="max-width: 800px;">

<?php if (!$isAuthenticated): ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h4 class="card-title mb-3"><?= tp($lang, 'admin', 'login_title') ?></h4>
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="password" class="form-label"><?= tp($lang, 'admin', 'password') ?></label>
                    <input type="password" class="form-control" id="password" name="password" autofocus required>
                </div>
                <button type="submit" class="btn btn-dark"><?= tp($lang, 'admin', 'login') ?></button>
            </form>
        </div>
    </div>

<?php else: ?>

    <?php if ($message !== ''): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Families -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold"><?= tp($lang, 'admin', 'families') ?> (<?= count($families) ?>)</div>
        <div class="card-body p-0">
            <?php if ($families === []): ?>
                <p class="text-muted p-3 mb-0"><?= tp($lang, 'admin', 'no_families') ?></p>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th><?= tp($lang, 'admin', 'name') ?></th>
                            <th><?= tp($lang, 'admin', 'members') ?></th>
                            <th><?= tp($lang, 'admin', 'created') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($families as $family): ?>
                        <tr>
                            <td><code><?= htmlspecialchars((string)$family['id']) ?></code></td>
                            <td><?= htmlspecialchars((string)$family['name']) ?></td>
                            <td><?= (int)$family['member_count'] ?></td>
                            <td><?= htmlspecialchars((string)$family['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Danger zone -->
    <div class="card shadow-sm border-danger">
        <div class="card-header bg-danger text-white fw-semibold"><?= tp($lang, 'admin', 'danger_zone') ?></div>
        <div class="card-body">
            <h6 class="card-title"><?= tp($lang, 'admin', 'reinit_db') ?></h6>
            <p class="card-text text-muted small">
                <?= tp($lang, 'admin', 'drop_warning_prefix') ?><strong><?= tp($lang, 'admin', 'drop_warning_strong') ?></strong>
            </p>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reinitModal">
                <?= tp($lang, 'admin', 'reinit_db') ?>
            </button>
        </div>
    </div>

<?php endif; ?>

</div>

<!-- Confirm modal -->
<div class="modal fade" id="reinitModal" tabindex="-1" aria-labelledby="reinitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="reinitialize">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reinitModalLabel"><?= tp($lang, 'admin', 'confirm_reinit') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= tp($lang, 'admin', 'confirm_prefix') ?><strong><?= tp($lang, 'admin', 'confirm_strong') ?></strong><?= tp($lang, 'admin', 'confirm_suffix') ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= tp($lang, 'admin', 'cancel') ?></button>
                    <button type="submit" class="btn btn-danger"><?= tp($lang, 'admin', 'yes_reinit') ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
