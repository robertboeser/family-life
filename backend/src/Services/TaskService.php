<?php

declare(strict_types=1);

namespace FamilyLife\Backend\Services;

use FamilyLife\Backend\Support\ApiException;
use FamilyLife\Backend\Support\Clock;
use PDO;

final class TaskService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listByFamily(int $familyId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, points, created_by, created_at
             FROM tasks
             WHERE family_id = :family_id
             ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([':family_id' => $familyId]);

        $tasks = [];
        foreach ($stmt->fetchAll() as $row) {
            $tasks[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'points' => (int)$row['points'],
                'created_by' => (int)$row['created_by'],
                'created_at' => $row['created_at'],
            ];
        }

        return $tasks;
    }

    public function create(int $familyId, int $memberId, string $name, int $points): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks (family_id, name, points, created_by, updated_at)
             VALUES (:family_id, :name, :points, :created_by, :now)'
        );
        $stmt->execute([
            ':family_id' => $familyId,
            ':name' => $name,
            ':points' => $points,
            ':created_by' => $memberId,
            ':now' => Clock::now(),
        ]);

        return [
            'id' => (int)$this->pdo->lastInsertId(),
            'name' => $name,
            'points' => $points,
            'created_by' => $memberId,
            'created_at' => Clock::now(),
        ];
    }

    public function delete(int $familyId, int $memberId, int $taskId): array
    {
        $taskStmt = $this->pdo->prepare(
            'SELECT id, family_id, created_by
             FROM tasks
             WHERE id = :id
             LIMIT 1'
        );
        $taskStmt->execute([':id' => $taskId]);
        $task = $taskStmt->fetch();

        if ($task === false || (int)$task['family_id'] !== $familyId) {
            throw new ApiException('Task not found', 404);
        }

        if ((int)$task['created_by'] !== $memberId) {
            throw new ApiException('Only the task creator can delete this task', 403);
        }

        $deleteStmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id');
        $deleteStmt->execute([':id' => $taskId]);

        return ['success' => true];
    }
}
