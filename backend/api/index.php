<?php

declare(strict_types=1);

use FamilyLife\Backend\Http\JsonResponder;
use FamilyLife\Backend\Http\Request;
use FamilyLife\Backend\Services\AuthService;
use FamilyLife\Backend\Services\VotingService;
use FamilyLife\Backend\Services\TaskService;
use FamilyLife\Backend\Services\ClaimService;
use FamilyLife\Backend\Services\RankService;
use FamilyLife\Backend\Services\FamilyService;
use FamilyLife\Backend\Support\ApiException;
use FamilyLife\Backend\Support\Validator;

require_once __DIR__ . '/../../vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = db();
$request = new Request();
$validator = new Validator();
$responder = new JsonResponder();
$authService = new AuthService($pdo, $request);
$votingService = new VotingService($pdo);
$taskService = new TaskService($pdo);
$claimService = new ClaimService($pdo);
$rankService = new RankService();
$familyService = new FamilyService($pdo, $rankService);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

if (str_starts_with($uri, '/api.php')) {
    $uri = substr($uri, strlen('/api.php'));
} elseif (str_starts_with($uri, '/api')) {
    // Keep compatibility with earlier /api/... style URLs.
    $uri = substr($uri, strlen('/api'));
}
$uri = '/' . trim($uri, '/');

try {

    if ($uri === '/families' && $method === 'POST') {
        $body = $request->jsonBody();
        $name = $validator->requiredString($body, 'name');

        $payload = $familyService->createFamily($name);
        $responder->send($payload, 201);
    }

    if (preg_match('#^/families/([^/]+)/members$#', $uri, $matches) === 1) {
        $familyId = $matches[1];

        if ($method === 'GET') {
            $responder->send($familyService->listMembers($familyId));
        }

        if ($method === 'POST') {
            $body = $request->jsonBody();
            $name = $validator->requiredString($body, 'name');

            try {
                $payload = $familyService->createMember($familyId, $name);
                $responder->send($payload, 201);
            } catch (ApiException $exception) {
                $responder->send(['error' => $exception->getMessage()], $exception->statusCode());
            }
        }
    }

    if ($uri === '/me' && $method === 'GET') {
        $member = $authService->requireMember();
        $responder->send($familyService->me($member));
    }

    if ($uri === '/tasks') {
        $member = $authService->requireMember();

        if ($method === 'GET') {
            $responder->send($taskService->listByFamily((string)$member['family_id']));
        }

        if ($method === 'POST') {
            $body = $request->jsonBody();
            $name = $validator->requiredString($body, 'name');
            $points = $validator->positiveInt($body, 'points');

            $payload = $taskService->create((string)$member['family_id'], (int)$member['id'], $name, $points);
            $responder->send($payload, 201);
        }
    }

    if (preg_match('#^/tasks/(\d+)$#', $uri, $matches) === 1 && $method === 'DELETE') {
        $member = $authService->requireMember();
        $taskId = (int)$matches[1];

        try {
            $payload = $taskService->delete((string)$member['family_id'], (int)$member['id'], $taskId);
            $responder->send($payload);
        } catch (ApiException $exception) {
            $responder->send(['error' => $exception->getMessage()], $exception->statusCode());
        }
    }

    if ($uri === '/claims/mine' && $method === 'GET') {
        $member = $authService->requireMember();

        $responder->send($claimService->listMine((string)$member['family_id'], (int)$member['id']));
    }

    if ($uri === '/claims') {
        $member = $authService->requireMember();

        if ($method === 'GET') {
            $status = strtolower((string)($_GET['status'] ?? 'all'));

            try {
                $payload = $claimService->listForFamily((string)$member['family_id'], $status);
                $responder->send($payload);
            } catch (ApiException $exception) {
                $responder->send(['error' => $exception->getMessage()], $exception->statusCode());
            }
        }

        if ($method === 'POST') {
            $body = $request->jsonBody();
            $taskId = $validator->positiveInt($body, 'task_id');

            try {
                $payload = $claimService->create((string)$member['family_id'], (int)$member['id'], $taskId);
                $responder->send($payload, 201);
            } catch (ApiException $exception) {
                $responder->send(['error' => $exception->getMessage()], $exception->statusCode());
            }
        }
    }

    if (preg_match('#^/claims/(\d+)/(approve|reject)$#', $uri, $matches) === 1 && $method === 'PUT') {
        $member = $authService->requireMember();
        $claimId = (int)$matches[1];
        $action = $matches[2];

        try {
            $payload = $claimService->finalize((string)$member['family_id'], (int)$member['id'], $claimId, $action);
            $responder->send($payload);
        } catch (ApiException $exception) {
            $responder->send(['error' => $exception->getMessage()], $exception->statusCode());
        }
    }

    if ($uri === '/scoreboard' && $method === 'GET') {
        $member = $authService->requireMember();

        $stmt = $pdo->prepare(
            'SELECT id, name, score
         FROM family_members
         WHERE family_id = :family_id
         ORDER BY score DESC, name ASC'
        );
        $stmt->execute([':family_id' => (string)$member['family_id']]);

        $rows = $stmt->fetchAll();
        $scoreboard = [];

        foreach ($rows as $index => $row) {
            $rank = $rankService->rankFromScore((int)$row['score']);
            $scoreboard[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'score' => (int)$row['score'],
                'rank' => $rank['name'],
                'position' => $index + 1
            ];
        }

        $responder->send($scoreboard);
    }

    if ($uri === '/voting/rounds/current' && $method === 'GET') {
        $member = $authService->requireMember();
        $responder->send($votingService->getCurrentRound((string)$member['family_id']));
    }

    if ($uri === '/voting/rounds' && $method === 'POST') {
        $member = $authService->requireMember();
        try {
            $payload = $votingService->createRound((string)$member['family_id']);
            $responder->send($payload, 201);
        } catch (ApiException $exception) {
            $responder->send(['error' => $exception->getMessage()], $exception->statusCode());
        }
    }

    if ($uri === '/voting/wishes' && $method === 'GET') {
        $member = $authService->requireMember();
        $responder->send($votingService->listActiveWishes((string)$member['family_id']));
    }

    if ($uri === '/voting/balance' && $method === 'GET') {
        $member = $authService->requireMember();
        $responder->send($votingService->getVotingBalance((int)$member['id'], (int)$member['score']));
    }

    if ($uri === '/voting/winners' && $method === 'GET') {
        $member = $authService->requireMember();
        $responder->send($votingService->listWinningWishes((string)$member['family_id']));
    }

    if ($uri === '/voting/wishes' && $method === 'POST') {
        $member = $authService->requireMember();
        $body = $request->jsonBody();
        $name = $validator->requiredString($body, 'name');

        try {
            $payload = $votingService->createWish((string)$member['family_id'], (int)$member['id'], $name);
            $responder->send($payload, 201);
        } catch (ApiException $exception) {
            $responder->send(['error' => $exception->getMessage()], $exception->statusCode());
        }
    }

    if ($uri === '/voting/votes' && $method === 'POST') {
        $member = $authService->requireMember();
        $body = $request->jsonBody();
        $wishId = $validator->positiveInt($body, 'wish_id');
        $amount = $validator->positiveInt($body, 'amount');

        try {
            $payload = $votingService->placeVote(
                (string)$member['family_id'],
                (int)$member['id'],
                (int)$member['score'],
                $wishId,
                $amount
            );
            $responder->send($payload, 201);
        } catch (ApiException $exception) {
            $responder->send(['error' => $exception->getMessage()], $exception->statusCode());
        }
    }

    if (preg_match('#^/voting/rounds/(\d+)/approve-close$#', $uri, $matches) === 1 && $method === 'POST') {
        $member = $authService->requireMember();
        $roundId = (int)$matches[1];

        try {
            $payload = $votingService->approveCloseRound((string)$member['family_id'], (int)$member['id'], $roundId);
            $responder->send($payload);
        } catch (ApiException $exception) {
            $responder->send(['error' => $exception->getMessage()], $exception->statusCode());
        }
    }

    if (preg_match('#^/voting/rounds/(\d+)/result$#', $uri, $matches) === 1 && $method === 'GET') {
        $member = $authService->requireMember();
        $roundId = (int)$matches[1];

        try {
            $payload = $votingService->getRoundResult((string)$member['family_id'], $roundId);
            $responder->send($payload);
        } catch (ApiException $exception) {
            $responder->send(['error' => $exception->getMessage()], $exception->statusCode());
        }
    }

    $responder->send(['error' => 'Not found'], 404);

} catch (ApiException $exception) {
    $responder->send(['error' => $exception->getMessage()], $exception->statusCode());
}
