<?php

declare(strict_types=1);

namespace FamilyLife\Backend\Services;

use FamilyLife\Backend\Support\ApiException;
use FamilyLife\Backend\Support\Clock;
use PDO;
use Throwable;

final class ClaimService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listMine(string $familyId, int $memberId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.task_id, c.status, c.approved_by, c.created_at, c.updated_at,
                    t.name AS task_name, t.points
             FROM task_claims c
             INNER JOIN tasks t ON t.id = c.task_id
             WHERE c.claimed_by = :member_id
               AND t.family_id = :family_id
             ORDER BY c.created_at DESC, c.id DESC'
        );
        $stmt->execute([
            ':member_id' => $memberId,
            ':family_id' => $familyId,
        ]);

        $claims = [];
        foreach ($stmt->fetchAll() as $row) {
            $claims[] = [
                'id' => (int)$row['id'],
                'task_id' => (int)$row['task_id'],
                'task_name' => $row['task_name'],
                'points' => (int)$row['points'],
                'status' => $row['status'],
                'approved_by' => $row['approved_by'] !== null ? (int)$row['approved_by'] : null,
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
        }

        return $claims;
    }

    public function listForFamily(string $familyId, string $status): array
    {
        $allowed = ['pending', 'approved', 'rejected', 'all'];
        if (!in_array($status, $allowed, true)) {
            throw new ApiException('Invalid status filter', 422);
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

        $stmt = $this->pdo->prepare($query);
        $params = [':family_id' => $familyId];
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
                'created_at' => $row['created_at'],
            ];
        }

        return $claims;
    }

    public function create(string $familyId, int $memberId, int $taskId): array
    {
        $taskStmt = $this->pdo->prepare('SELECT id, family_id FROM tasks WHERE id = :id LIMIT 1');
        $taskStmt->execute([':id' => $taskId]);
        $task = $taskStmt->fetch();

        if ($task === false || (string)$task['family_id'] !== $familyId) {
            throw new ApiException('Task not found', 404);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO task_claims (task_id, claimed_by, status, updated_at) VALUES (:task_id, :claimed_by, :status, :now)'
        );
        $stmt->execute([
            ':task_id' => $taskId,
            ':claimed_by' => $memberId,
            ':status' => 'pending',
            ':now' => Clock::now(),
        ]);

        return [
            'id' => (int)$this->pdo->lastInsertId(),
            'task_id' => $taskId,
            'status' => 'pending',
            'created_at' => Clock::now(),
        ];
    }

    public function finalize(string $familyId, int $reviewerId, int $claimId, string $action): array
    {
        $claimStmt = $this->pdo->prepare(
            'SELECT c.id, c.task_id, c.status, c.claimed_by, t.points, t.family_id
             FROM task_claims c
             INNER JOIN tasks t ON t.id = c.task_id
             WHERE c.id = :id
             LIMIT 1'
        );
        $claimStmt->execute([':id' => $claimId]);
        $claim = $claimStmt->fetch();

        if ($claim === false || (string)$claim['family_id'] !== $familyId) {
            throw new ApiException('Claim not found', 404);
        }

        if ($claim['status'] !== 'pending') {
            throw new ApiException('Claim is already finalized', 409);
        }

        $newStatus = $action === 'approve' ? 'approved' : 'rejected';

        $this->pdo->beginTransaction();
        try {
            $updateStmt = $this->pdo->prepare(
                'UPDATE task_claims
                 SET status = :status, approved_by = :approved_by, updated_at = :now
                 WHERE id = :id'
            );
            $updateStmt->execute([
                ':status' => $newStatus,
                ':approved_by' => $reviewerId,
                ':now' => Clock::now(),
                ':id' => $claimId,
            ]);

            if ($newStatus === 'approved') {
                $scoreStmt = $this->pdo->prepare(
                    'UPDATE family_members
                     SET score = score + :points, updated_at = :now
                     WHERE id = :member_id'
                );
                $scoreStmt->execute([
                    ':points' => (int)$claim['points'],
                    ':now' => Clock::now(),
                    ':member_id' => (int)$claim['claimed_by'],
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw new ApiException('Unable to update claim', 500);
        }

        return [
            'id' => $claimId,
            'status' => $newStatus,
            'updated_at' => Clock::now(),
        ];
    }
}
