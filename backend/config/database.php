<?php

declare(strict_types=1);

require_once __DIR__ . '/constants.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    initializeSchema($pdo);

    return $pdo;
}

function initializeSchema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS families (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS family_members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            family_id TEXT NOT NULL,
            name TEXT NOT NULL,
            auth_token TEXT UNIQUE NOT NULL,
            score INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            family_id TEXT NOT NULL,
            name TEXT NOT NULL,
            points INTEGER NOT NULL,
            created_by INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES family_members(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS task_claims (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            task_id INTEGER NOT NULL,
            claimed_by INTEGER NOT NULL,
            status TEXT NOT NULL CHECK(status IN ('pending', 'approved', 'rejected')) DEFAULT 'pending',
            approved_by INTEGER DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (claimed_by) REFERENCES family_members(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES family_members(id) ON DELETE SET NULL
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS voting_rounds (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            family_id TEXT NOT NULL,
            status TEXT NOT NULL CHECK(status IN ('open', 'closed')) DEFAULT 'open',
            closed_at DATETIME DEFAULT NULL,
            closed_wish_id INTEGER DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS wishes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            family_id TEXT NOT NULL,
            round_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            score INTEGER NOT NULL DEFAULT 0,
            created_by INTEGER NOT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
            FOREIGN KEY (round_id) REFERENCES voting_rounds(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES family_members(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS wish_votes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            round_id INTEGER NOT NULL,
            wish_id INTEGER NOT NULL,
            member_id INTEGER NOT NULL,
            amount INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (round_id) REFERENCES voting_rounds(id) ON DELETE CASCADE,
            FOREIGN KEY (wish_id) REFERENCES wishes(id) ON DELETE CASCADE,
            FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS voting_round_closure_approvals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            round_id INTEGER NOT NULL,
            member_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (round_id) REFERENCES voting_rounds(id) ON DELETE CASCADE,
            FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE,
            UNIQUE(round_id, member_id)
        )"
    );

    ensureColumn($pdo, 'wishes', 'is_active', 'INTEGER NOT NULL DEFAULT 1');
    ensureColumn($pdo, 'voting_rounds', 'closed_wish_id', 'INTEGER DEFAULT NULL');
    upgradeFamilyIdsToText($pdo);
    cleanupLegacyFamilyMemberRankColumns($pdo);

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_task_claims_task_id ON task_claims(task_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_tasks_family_id ON tasks(family_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_wishes_family_active ON wishes(family_id, is_active)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_wish_votes_member ON wish_votes(member_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_voting_rounds_family_status ON voting_rounds(family_id, status)');
}

function upgradeFamilyIdsToText(PDO $pdo): void
{
    if (!requiresFamilyIdTextMigration($pdo)) {
        return;
    }

    $pdo->exec('PRAGMA foreign_keys = OFF');
    $pdo->beginTransaction();

    try {
        $pdo->exec('ALTER TABLE wishes RENAME TO wishes_legacy');
        $pdo->exec('ALTER TABLE voting_rounds RENAME TO voting_rounds_legacy');
        $pdo->exec('ALTER TABLE tasks RENAME TO tasks_legacy');
        $pdo->exec('ALTER TABLE family_members RENAME TO family_members_legacy');
        $pdo->exec('ALTER TABLE families RENAME TO families_legacy');

        $pdo->exec(
            "CREATE TABLE families (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        );

        $pdo->exec(
            "CREATE TABLE family_members (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                family_id TEXT NOT NULL,
                name TEXT NOT NULL,
                auth_token TEXT UNIQUE NOT NULL,
                score INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE
            )"
        );

        $pdo->exec(
            "CREATE TABLE tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                family_id TEXT NOT NULL,
                name TEXT NOT NULL,
                points INTEGER NOT NULL,
                created_by INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES family_members(id) ON DELETE CASCADE
            )"
        );

        $pdo->exec(
            "CREATE TABLE voting_rounds (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                family_id TEXT NOT NULL,
                status TEXT NOT NULL CHECK(status IN ('open', 'closed')) DEFAULT 'open',
                closed_at DATETIME DEFAULT NULL,
                closed_wish_id INTEGER DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE
            )"
        );

        $pdo->exec(
            "CREATE TABLE wishes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                family_id TEXT NOT NULL,
                round_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                score INTEGER NOT NULL DEFAULT 0,
                created_by INTEGER NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
                FOREIGN KEY (round_id) REFERENCES voting_rounds(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES family_members(id) ON DELETE CASCADE
            )"
        );

        $pdo->exec(
            'INSERT INTO families (id, name, created_at, updated_at)
             SELECT CAST(id AS TEXT), name, created_at, updated_at FROM families_legacy'
        );

        $pdo->exec(
            'INSERT INTO family_members (id, family_id, name, auth_token, score, created_at, updated_at)
             SELECT id, CAST(family_id AS TEXT), name, auth_token, score, created_at, updated_at
             FROM family_members_legacy'
        );

        $pdo->exec(
            'INSERT INTO tasks (id, family_id, name, points, created_by, created_at, updated_at)
             SELECT id, CAST(family_id AS TEXT), name, points, created_by, created_at, updated_at
             FROM tasks_legacy'
        );

        $pdo->exec(
            'INSERT INTO voting_rounds (id, family_id, status, closed_at, closed_wish_id, created_at, updated_at)
             SELECT id, CAST(family_id AS TEXT), status, closed_at, closed_wish_id, created_at, updated_at
             FROM voting_rounds_legacy'
        );

        $pdo->exec(
            'INSERT INTO wishes (id, family_id, round_id, name, score, created_by, is_active, created_at, updated_at)
             SELECT id, CAST(family_id AS TEXT), round_id, name, score, created_by, COALESCE(is_active, 1), created_at, updated_at
             FROM wishes_legacy'
        );

        $pdo->exec('DROP TABLE wishes_legacy');
        $pdo->exec('DROP TABLE voting_rounds_legacy');
        $pdo->exec('DROP TABLE tasks_legacy');
        $pdo->exec('DROP TABLE family_members_legacy');
        $pdo->exec('DROP TABLE families_legacy');

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $pdo->exec('PRAGMA foreign_keys = ON');
        throw $exception;
    }

    $pdo->exec('PRAGMA foreign_keys = ON');
}

function requiresFamilyIdTextMigration(PDO $pdo): bool
{
    if (tableColumns($pdo, 'families') === []) {
        return false;
    }

    $familiesIdType = tableColumnType($pdo, 'families', 'id');
    if ($familiesIdType !== null && $familiesIdType !== 'INTEGER') {
        return false;
    }

    return true;
}

function ensureColumn(PDO $pdo, string $tableName, string $columnName, string $definition): void
{
    $stmt = $pdo->query('PRAGMA table_info(' . $tableName . ')');
    if ($stmt === false) {
        return;
    }

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
        if (($column['name'] ?? '') === $columnName) {
            return;
        }
    }

    $pdo->exec('ALTER TABLE ' . $tableName . ' ADD COLUMN ' . $columnName . ' ' . $definition);
}

function cleanupLegacyFamilyMemberRankColumns(PDO $pdo): void
{
    $existingColumns = tableColumns($pdo, 'family_members');
    if ($existingColumns === []) {
        return;
    }

    $legacyColumns = ['rank', 'rank_name', 'rank_from', 'rank_to', 'rank_index'];
    if (array_intersect($legacyColumns, $existingColumns) === []) {
        return;
    }

    $copyableColumns = ['id', 'family_id', 'name', 'auth_token', 'score', 'created_at', 'updated_at'];
    $columnsToCopy = array_values(array_intersect($copyableColumns, $existingColumns));
    if ($columnsToCopy === []) {
        return;
    }

    $columnList = implode(', ', $columnsToCopy);

    $pdo->exec('PRAGMA foreign_keys = OFF');
    $pdo->beginTransaction();

    try {
        $pdo->exec('ALTER TABLE family_members RENAME TO family_members_legacy');

        $pdo->exec(
            "CREATE TABLE family_members (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                family_id TEXT NOT NULL,
                name TEXT NOT NULL,
                auth_token TEXT UNIQUE NOT NULL,
                score INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE
            )"
        );

        $pdo->exec(
            'INSERT INTO family_members (' . $columnList . ') SELECT ' . $columnList . ' FROM family_members_legacy'
        );

        $pdo->exec('DROP TABLE family_members_legacy');
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $pdo->exec('PRAGMA foreign_keys = ON');
        throw $exception;
    }

    $pdo->exec('PRAGMA foreign_keys = ON');
}

function tableColumns(PDO $pdo, string $tableName): array
{
    $stmt = $pdo->query('PRAGMA table_info(' . $tableName . ')');
    if ($stmt === false) {
        return [];
    }

    $columns = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $name = $column['name'] ?? null;
        if (is_string($name) && $name !== '') {
            $columns[] = $name;
        }
    }

    return $columns;
}

function tableColumnType(PDO $pdo, string $tableName, string $columnName): ?string
{
    $stmt = $pdo->query('PRAGMA table_info(' . $tableName . ')');
    if ($stmt === false) {
        return null;
    }

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
        if (($column['name'] ?? '') !== $columnName) {
            continue;
        }

        $type = $column['type'] ?? null;
        return is_string($type) ? strtoupper($type) : null;
    }

    return null;
}
