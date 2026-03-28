<?php

declare(strict_types=1);

namespace FamilyLife\Backend\Services;

use FamilyLife\Backend\Support\ApiException;
use FamilyLife\Backend\Support\Clock;
use PDO;

final class FamilyService
{
    public function __construct(private PDO $pdo, private RankService $rankService)
    {
    }

    public function createFamily(string $name): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO families (name, updated_at) VALUES (:name, :now)');
        $stmt->execute([':name' => $name, ':now' => Clock::now()]);

        return [
            'id' => (int)$this->pdo->lastInsertId(),
            'name' => $name,
            'created_at' => Clock::now(),
        ];
    }

    public function listMembers(int $familyId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, score
             FROM family_members
             WHERE family_id = :family_id
             ORDER BY score DESC, name ASC'
        );
        $stmt->execute([':family_id' => $familyId]);

        $data = [];
        foreach ($stmt->fetchAll() as $row) {
            $rank = $this->rankService->rankFromScore((int)$row['score']);
            $data[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'score' => (int)$row['score'],
                'rank' => $rank['rank'],
                'rank_name' => $rank['name'],
            ];
        }

        return $data;
    }

    public function createMember(int $familyId, string $name): array
    {
        $familyStmt = $this->pdo->prepare('SELECT id FROM families WHERE id = :id LIMIT 1');
        $familyStmt->execute([':id' => $familyId]);
        if ($familyStmt->fetch() === false) {
            throw new ApiException('Family not found', 404);
        }

        $token = $this->generateToken();
        $stmt = $this->pdo->prepare(
            'INSERT INTO family_members (family_id, name, auth_token, updated_at) VALUES (:family_id, :name, :auth_token, :now)'
        );
        $stmt->execute([
            ':family_id' => $familyId,
            ':name' => $name,
            ':auth_token' => $token,
            ':now' => Clock::now(),
        ]);

        return [
            'id' => (int)$this->pdo->lastInsertId(),
            'name' => $name,
            'auth_token' => $token,
            'score' => 0,
        ];
    }

    public function me(array $member): array
    {
        $rank = $this->rankService->rankFromScore((int)$member['score']);

        return [
            'id' => (int)$member['id'],
            'name' => $member['name'],
            'family_id' => (int)$member['family_id'],
            'score' => (int)$member['score'],
            'rank' => $rank['rank'],
            'rank_name' => $rank['name'],
            'rank_from' => $rank['from'],
            'rank_to' => $rank['to'],
        ];
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(TOKEN_BYTES));
    }
}
