<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

$apiBase = '/api';
if (str_starts_with($uri, $apiBase)) {
    $uri = substr($uri, strlen($apiBase));
}
$uri = '/' . trim($uri, '/');

if ($uri === '/rank-name' && $method === 'GET') {
    $rawIndex = $_GET['index'] ?? null;

    if ($rawIndex === null || filter_var($rawIndex, FILTER_VALIDATE_INT) === false || (int)$rawIndex < 0) {
        jsonResponse(['error' => 'index must be a non-negative integer'], 422);
    }

    $index = (int)$rawIndex;

    jsonResponse([
        'index' => $index,
        'name' => proceduralRankNameFromIndex($index)
    ]);
}

if ($uri === '/families' && $method === 'POST') {
    $body = getJsonBody();
    $name = assertRequiredString($body, 'name');

    $stmt = $pdo->prepare('INSERT INTO families (name, updated_at) VALUES (:name, :now)');
    $stmt->execute([':name' => $name, ':now' => now()]);

    $id = (int)$pdo->lastInsertId();
    jsonResponse([
        'id' => $id,
        'name' => $name,
        'created_at' => now()
    ], 201);
}

if (preg_match('#^/families/(\d+)/members$#', $uri, $matches) === 1) {
    $familyId = (int)$matches[1];

    if ($method === 'GET') {
        $stmt = $pdo->prepare('SELECT id, name, score FROM family_members WHERE family_id = :family_id ORDER BY score DESC, name ASC');
        $stmt->execute([':family_id' => $familyId]);
        $rows = $stmt->fetchAll();

        $data = [];
        foreach ($rows as $row) {
            $rank = rankFromScore((int)$row['score']);
            $data[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'score' => (int)$row['score'],
                'rank' => $rank['rank'],
                'rank_name' => $rank['name']
            ];
        }

        jsonResponse($data);
    }

    if ($method === 'POST') {
        $body = getJsonBody();
        $name = assertRequiredString($body, 'name');

        $familyStmt = $pdo->prepare('SELECT id FROM families WHERE id = :id LIMIT 1');
        $familyStmt->execute([':id' => $familyId]);
        if ($familyStmt->fetch() === false) {
            jsonResponse(['error' => 'Family not found'], 404);
        }

        $token = generateToken();
        $stmt = $pdo->prepare(
            'INSERT INTO family_members (family_id, name, auth_token, updated_at) VALUES (:family_id, :name, :auth_token, :now)'
        );
        $stmt->execute([
            ':family_id' => $familyId,
            ':name' => $name,
            ':auth_token' => $token,
            ':now' => now()
        ]);

        jsonResponse([
            'id' => (int)$pdo->lastInsertId(),
            'name' => $name,
            'auth_token' => $token,
            'score' => 0
        ], 201);
    }
}

if ($uri === '/me' && $method === 'GET') {
    $member = requireAuth($pdo);
    $rank = rankFromScore((int)$member['score']);

    jsonResponse([
        'id' => (int)$member['id'],
        'name' => $member['name'],
        'family_id' => (int)$member['family_id'],
        'score' => (int)$member['score'],
        'rank' => $rank['rank'],
        'rank_name' => $rank['name'],
        'rank_from' => $rank['from'],
        'rank_to' => $rank['to']
    ]);
}

if ($uri === '/tasks') {
    $member = requireAuth($pdo);

    if ($method === 'GET') {
        $stmt = $pdo->prepare(
            'SELECT id, name, points, created_by, created_at
             FROM tasks
             WHERE family_id = :family_id
             ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([':family_id' => (int)$member['family_id']]);

        $tasks = [];
        foreach ($stmt->fetchAll() as $row) {
            $tasks[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'points' => (int)$row['points'],
                'created_by' => (int)$row['created_by'],
                'created_at' => $row['created_at']
            ];
        }

        jsonResponse($tasks);
    }

    if ($method === 'POST') {
        $body = getJsonBody();
        $name = assertRequiredString($body, 'name');
        $points = assertPositiveInt($body, 'points');

        $stmt = $pdo->prepare(
            'INSERT INTO tasks (family_id, name, points, created_by, updated_at)
             VALUES (:family_id, :name, :points, :created_by, :now)'
        );
        $stmt->execute([
            ':family_id' => (int)$member['family_id'],
            ':name' => $name,
            ':points' => $points,
            ':created_by' => (int)$member['id'],
            ':now' => now()
        ]);

        jsonResponse([
            'id' => (int)$pdo->lastInsertId(),
            'name' => $name,
            'points' => $points,
            'created_by' => (int)$member['id'],
            'created_at' => now()
        ], 201);
    }
}

if ($uri === '/claims') {
    $member = requireAuth($pdo);

    if ($method === 'GET') {
        $status = strtolower((string)($_GET['status'] ?? 'all'));
        $allowed = ['pending', 'approved', 'rejected', 'all'];
        if (!in_array($status, $allowed, true)) {
            jsonResponse(['error' => 'Invalid status filter'], 422);
        }

        $query =
            'SELECT c.id, c.task_id, c.claimed_by, c.status, c.approved_by, c.created_at,
                    t.name AS task_name, t.points,
                    m.name AS claimed_by_name
             FROM task_claims c
             INNER JOIN tasks t ON t.id = c.task_id
             INNER JOIN family_members m ON m.id = c.claimed_by
             WHERE t.family_id = :family_id';

        if ($status !== 'all') {
            $query .= ' AND c.status = :status';
        }

        $query .= ' ORDER BY c.created_at DESC, c.id DESC';

        $stmt = $pdo->prepare($query);
        $params = [':family_id' => (int)$member['family_id']];
        if ($status !== 'all') {
            $params[':status'] = $status;
        }
        $stmt->execute($params);

        $claims = [];
        foreach ($stmt->fetchAll() as $row) {
            $claims[] = [
                'id' => (int)$row['id'],
                'task_id' => (int)$row['task_id'],
                'task_name' => $row['task_name'],
                'points' => (int)$row['points'],
                'claimed_by' => (int)$row['claimed_by'],
                'claimed_by_name' => $row['claimed_by_name'],
                'status' => $row['status'],
                'approved_by' => $row['approved_by'] !== null ? (int)$row['approved_by'] : null,
                'created_at' => $row['created_at']
            ];
        }

        jsonResponse($claims);
    }

    if ($method === 'POST') {
        $body = getJsonBody();
        $taskId = assertPositiveInt($body, 'task_id');

        $taskStmt = $pdo->prepare('SELECT id, family_id FROM tasks WHERE id = :id LIMIT 1');
        $taskStmt->execute([':id' => $taskId]);
        $task = $taskStmt->fetch();

        if ($task === false || (int)$task['family_id'] !== (int)$member['family_id']) {
            jsonResponse(['error' => 'Task not found'], 404);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO task_claims (task_id, claimed_by, status, updated_at) VALUES (:task_id, :claimed_by, :status, :now)'
        );
        $stmt->execute([
            ':task_id' => $taskId,
            ':claimed_by' => (int)$member['id'],
            ':status' => 'pending',
            ':now' => now()
        ]);

        jsonResponse([
            'id' => (int)$pdo->lastInsertId(),
            'task_id' => $taskId,
            'status' => 'pending',
            'created_at' => now()
        ], 201);
    }
}

if (preg_match('#^/claims/(\d+)/(approve|reject)$#', $uri, $matches) === 1 && $method === 'PUT') {
    $member = requireAuth($pdo);
    $claimId = (int)$matches[1];
    $action = $matches[2];

    $claimStmt = $pdo->prepare(
        'SELECT c.id, c.task_id, c.status, c.claimed_by, t.points, t.family_id
         FROM task_claims c
         INNER JOIN tasks t ON t.id = c.task_id
         WHERE c.id = :id
         LIMIT 1'
    );
    $claimStmt->execute([':id' => $claimId]);
    $claim = $claimStmt->fetch();

    if ($claim === false || (int)$claim['family_id'] !== (int)$member['family_id']) {
        jsonResponse(['error' => 'Claim not found'], 404);
    }

    if ($claim['status'] !== 'pending') {
        jsonResponse(['error' => 'Claim is already finalized'], 409);
    }

    $newStatus = $action === 'approve' ? 'approved' : 'rejected';

    $pdo->beginTransaction();
    try {
        $updateStmt = $pdo->prepare(
            'UPDATE task_claims
             SET status = :status, approved_by = :approved_by, updated_at = :now
             WHERE id = :id'
        );
        $updateStmt->execute([
            ':status' => $newStatus,
            ':approved_by' => (int)$member['id'],
            ':now' => now(),
            ':id' => $claimId
        ]);

        if ($newStatus === 'approved') {
            $scoreStmt = $pdo->prepare(
                'UPDATE family_members
                 SET score = score + :points, updated_at = :now
                 WHERE id = :member_id'
            );
            $scoreStmt->execute([
                ':points' => (int)$claim['points'],
                ':now' => now(),
                ':member_id' => (int)$claim['claimed_by']
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['error' => 'Unable to update claim'], 500);
    }

    jsonResponse([
        'id' => $claimId,
        'status' => $newStatus,
        'updated_at' => now()
    ]);
}

if ($uri === '/scoreboard' && $method === 'GET') {
    $member = requireAuth($pdo);

    $stmt = $pdo->prepare(
        'SELECT id, name, score
         FROM family_members
         WHERE family_id = :family_id
         ORDER BY score DESC, name ASC'
    );
    $stmt->execute([':family_id' => (int)$member['family_id']]);

    $rows = $stmt->fetchAll();
    $scoreboard = [];

    foreach ($rows as $index => $row) {
        $rank = rankFromScore((int)$row['score']);
        $scoreboard[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'score' => (int)$row['score'],
            'rank' => $rank['name'],
            'rank_from' => $rank['from'],
            'rank_to' => $rank['to'],
            'position' => $index + 1
        ];
    }

    jsonResponse($scoreboard);
}

jsonResponse(['error' => 'Not found'], 404);
