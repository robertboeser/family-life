<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        jsonResponse(['error' => 'Invalid JSON body'], 400);
    }

    return $decoded;
}

function getBearerToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) === 1) {
        return trim($matches[1]);
    }

    $tokenFromQuery = $_GET['token'] ?? null;
    if (is_string($tokenFromQuery) && $tokenFromQuery !== '') {
        return $tokenFromQuery;
    }

    return null;
}

function requireAuth(PDO $pdo): array
{
    $token = getBearerToken();
    if ($token === null) {
        jsonResponse(['error' => 'Missing auth token'], 401);
    }

    $stmt = $pdo->prepare('SELECT id, family_id, name, score, auth_token FROM family_members WHERE auth_token = :token LIMIT 1');
    $stmt->execute([':token' => $token]);
    $member = $stmt->fetch();

    if ($member === false) {
        jsonResponse(['error' => 'Invalid auth token'], 401);
    }

    return $member;
}

function proceduralRankNameFromIndex(int $index): string
{
    $index = max(0, $index);

    $colors = [
        'Crimson',
        'Azure',
        'Emerald',
        'Amber',
        'Ivory',
        'Onyx',
        'Silver',
        'Golden',
        'Scarlet',
        'Teal',
        'Violet',
        'Copper'
    ];

    $adjectives = [
        'Brave',
        'Swift',
        'Wise',
        'Fierce',
        'Nimble',
        'Radiant',
        'Stalwart',
        'Mighty',
        'Bold',
        'Clever',
        'Steady',
        'Valiant'
    ];

    $animals = [
        'Lion',
        'Falcon',
        'Wolf',
        'Bear',
        'Otter',
        'Fox',
        'Eagle',
        'Tiger',
        'Panther',
        'Stag',
        'Dolphin',
        'Raven'
    ];

    $colorCount = count($colors);
    $adjectiveCount = count($adjectives);

    $color = $colors[$index % $colorCount];
    $adjective = $adjectives[intdiv($index, $colorCount) % $adjectiveCount];
    $animal = $animals[intdiv($index, $colorCount * $adjectiveCount) % count($animals)];

    return $adjective . ' ' . $color . ' ' . $animal;
}

function rankFromScore(int $score): array
{
    $rank = intdiv(max(0, $score), 20) + 1;

    $rankNames = [
        1 => 'Novice',
        2 => 'Apprentice',
        3 => 'Journeyman',
        4 => 'Master',
        5 => 'Grandmaster'
    ];

    $name = $rankNames[$rank] ?? 'Legend';
    $from = ($rank - 1) * 20;
    $to = $rank >= 6 ? null : ($from + 19);

    return [
        'rank' => $rank,
        'name' => $name,
        'from' => $from,
        'to' => $to
    ];
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function assertRequiredString(array $body, string $field, int $maxLength = 120): string
{
    $value = trim((string)($body[$field] ?? ''));
    if ($value === '') {
        jsonResponse(['error' => $field . ' is required'], 422);
    }
    if (strlen($value) > $maxLength) {
        jsonResponse(['error' => $field . ' is too long'], 422);
    }
    return $value;
}

function assertPositiveInt(array $body, string $field): int
{
    $value = $body[$field] ?? null;
    if (!is_numeric($value) || (int)$value <= 0) {
        jsonResponse(['error' => $field . ' must be a positive integer'], 422);
    }

    return (int)$value;
}

function generateToken(): string
{
    return bin2hex(random_bytes(TOKEN_BYTES));
}
