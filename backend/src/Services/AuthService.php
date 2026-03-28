<?php

declare(strict_types=1);

namespace FamilyLife\Backend\Services;

use FamilyLife\Backend\Http\Request;
use FamilyLife\Backend\Support\ApiException;
use PDO;

final class AuthService
{
    public function __construct(private PDO $pdo, private Request $request)
    {
    }

    public function requireMember(): array
    {
        $token = $this->request->bearerToken();
        if ($token === null) {
            throw new ApiException('Missing auth token', 401);
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, family_id, name, score, auth_token FROM family_members WHERE auth_token = :token LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        $member = $stmt->fetch();

        if ($member === false) {
            throw new ApiException('Invalid auth token', 401);
        }

        return $member;
    }
}
