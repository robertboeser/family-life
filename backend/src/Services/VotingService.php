<?php

declare(strict_types=1);

namespace FamilyLife\Backend\Services;

use FamilyLife\Backend\Support\ApiException;
use FamilyLife\Backend\Support\Clock;
use PDO;
use Throwable;

final class VotingService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getCurrentRound(string $familyId): array
    {
        $round = $this->getOpenRound($familyId);

        if ($round === null) {
            return [
                'id' => null,
                'status' => 'none',
                'created_at' => null,
                'closure_approvals_count' => 0,
            ];
        }

        return [
            'id' => (int)$round['id'],
            'status' => $round['status'],
            'created_at' => $round['created_at'],
            'closure_approvals_count' => $this->getRoundClosureApprovalsCount((int)$round['id']),
        ];
    }

    public function createRound(string $familyId): array
    {
        if ($this->getOpenRound($familyId) !== null) {
            throw new ApiException('An open voting round already exists', 409);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO voting_rounds (family_id, status, updated_at)
             VALUES (:family_id, :status, :now)'
        );
        $stmt->execute([
            ':family_id' => $familyId,
            ':status' => 'open',
            ':now' => Clock::now(),
        ]);

        return [
            'id' => (int)$this->pdo->lastInsertId(),
            'status' => 'open',
            'created_at' => Clock::now(),
        ];
    }

    public function listActiveWishes(string $familyId): array
    {
        if ($this->getOpenRound($familyId) === null) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT w.id, w.name, w.score, w.created_by, w.is_active,
                    m.name AS created_by_name
             FROM wishes w
             INNER JOIN family_members m ON m.id = w.created_by
             WHERE w.family_id = :family_id
               AND w.is_active = 1
             ORDER BY w.score DESC, w.created_at ASC, w.id ASC'
        );
        $stmt->execute([':family_id' => $familyId]);

        $wishes = [];
        foreach ($stmt->fetchAll() as $row) {
            $wishes[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'score' => (int)$row['score'],
                'created_by' => (int)$row['created_by'],
                'created_by_name' => $row['created_by_name'],
                'is_active' => (int)$row['is_active'],
            ];
        }

        return $wishes;
    }

    public function createWish(string $familyId, int $memberId, string $name): array
    {
        $round = $this->getOpenRound($familyId);
        if ($round === null) {
            throw new ApiException('No open voting round', 409);
        }

        $existingWishStmt = $this->pdo->prepare(
            'SELECT id
             FROM wishes
             WHERE round_id = :round_id
               AND created_by = :member_id
               AND is_active = 1
             LIMIT 1'
        );
        $existingWishStmt->execute([
            ':round_id' => (int)$round['id'],
            ':member_id' => $memberId,
        ]);

        if ($existingWishStmt->fetch() !== false) {
            throw new ApiException('You already created an active wish in this round', 409);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO wishes (family_id, round_id, name, score, created_by, is_active, updated_at)
             VALUES (:family_id, :round_id, :name, 0, :created_by, 1, :now)'
        );
        $stmt->execute([
            ':family_id' => $familyId,
            ':round_id' => (int)$round['id'],
            ':name' => $name,
            ':created_by' => $memberId,
            ':now' => Clock::now(),
        ]);

        return [
            'id' => (int)$this->pdo->lastInsertId(),
            'name' => $name,
            'score' => 0,
            'created_by' => $memberId,
            'round_id' => (int)$round['id'],
        ];
    }

    public function placeVote(string $familyId, int $memberId, int $memberScore, int $wishId, int $amount): array
    {
        $round = $this->getOpenRound($familyId);
        if ($round === null) {
            throw new ApiException('No open voting round', 409);
        }

        $wishStmt = $this->pdo->prepare(
            'SELECT id, family_id, score, is_active
             FROM wishes
             WHERE id = :id
             LIMIT 1'
        );
        $wishStmt->execute([':id' => $wishId]);
        $wish = $wishStmt->fetch();

        if ($wish === false || (string)$wish['family_id'] !== $familyId) {
            throw new ApiException('Wish not found', 404);
        }

        if ((int)$wish['is_active'] !== 1) {
            throw new ApiException('Wish is no longer active', 409);
        }

        $available = $this->getMemberVotingBalance($memberId, $memberScore);
        if ($amount > $available) {
            throw new ApiException('Insufficient voting balance', 422);
        }

        $this->pdo->beginTransaction();
        try {
            $voteStmt = $this->pdo->prepare(
                'INSERT INTO wish_votes (round_id, wish_id, member_id, amount)
                 VALUES (:round_id, :wish_id, :member_id, :amount)'
            );
            $voteStmt->execute([
                ':round_id' => (int)$round['id'],
                ':wish_id' => $wishId,
                ':member_id' => $memberId,
                ':amount' => $amount,
            ]);

            $updateWishStmt = $this->pdo->prepare(
                'UPDATE wishes
                 SET score = score + :amount, updated_at = :now
                 WHERE id = :id'
            );
            $updateWishStmt->execute([
                ':amount' => $amount,
                ':now' => Clock::now(),
                ':id' => $wishId,
            ]);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw new ApiException('Unable to place vote', 500);
        }

        $wishScoreStmt = $this->pdo->prepare('SELECT score FROM wishes WHERE id = :id LIMIT 1');
        $wishScoreStmt->execute([':id' => $wishId]);
        $wishScore = (int)($wishScoreStmt->fetch()['score'] ?? 0);

        return [
            'wish_id' => $wishId,
            'amount' => $amount,
            'member_available_after_vote' => $available - $amount,
            'wish_score_after_vote' => $wishScore,
        ];
    }

    public function approveCloseRound(string $familyId, int $memberId, int $roundId): array
    {
        $roundStmt = $this->pdo->prepare(
            'SELECT id, family_id, status
             FROM voting_rounds
             WHERE id = :id
             LIMIT 1'
        );
        $roundStmt->execute([':id' => $roundId]);
        $round = $roundStmt->fetch();

        if ($round === false || (string)$round['family_id'] !== $familyId) {
            throw new ApiException('Round not found', 404);
        }

        if ($round['status'] !== 'open') {
            throw new ApiException('Round is already closed', 409);
        }

        $insertApprovalStmt = $this->pdo->prepare(
            'INSERT OR IGNORE INTO voting_round_closure_approvals (round_id, member_id)
             VALUES (:round_id, :member_id)'
        );
        $insertApprovalStmt->execute([
            ':round_id' => $roundId,
            ':member_id' => $memberId,
        ]);

        $approvalsCount = $this->getRoundClosureApprovalsCount($roundId);
        if ($approvalsCount < 2) {
            return [
                'round_id' => $roundId,
                'status' => 'open',
                'approvals_count' => $approvalsCount,
            ];
        }

        $winner = $this->findRoundWinnerCandidate($familyId);
        if ($winner === null) {
            throw new ApiException('Cannot close round without active wishes', 409);
        }

        $this->pdo->beginTransaction();
        try {
            $closeRoundStmt = $this->pdo->prepare(
                'UPDATE voting_rounds
                 SET status = :status, closed_at = :closed_at, closed_wish_id = :closed_wish_id, updated_at = :now
                 WHERE id = :id'
            );
            $closeRoundStmt->execute([
                ':status' => 'closed',
                ':closed_at' => Clock::now(),
                ':closed_wish_id' => (int)$winner['id'],
                ':now' => Clock::now(),
                ':id' => $roundId,
            ]);

            $deactivateWinnerStmt = $this->pdo->prepare(
                'UPDATE wishes
                 SET is_active = 0, updated_at = :now
                 WHERE id = :id'
            );
            $deactivateWinnerStmt->execute([
                ':now' => Clock::now(),
                ':id' => (int)$winner['id'],
            ]);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw new ApiException('Unable to close round', 500);
        }

        return [
            'round_id' => $roundId,
            'status' => 'closed',
            'approvals_count' => $approvalsCount,
        ];
    }

    public function getRoundResult(string $familyId, int $roundId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.id, r.family_id, r.status, r.closed_wish_id,
                    w.name AS winning_wish_name, w.score AS winning_score, w.created_by AS winning_created_by
             FROM voting_rounds r
             LEFT JOIN wishes w ON w.id = r.closed_wish_id
             WHERE r.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $roundId]);
        $result = $stmt->fetch();

        if ($result === false || (string)$result['family_id'] !== $familyId) {
            throw new ApiException('Round not found', 404);
        }

        if ($result['status'] !== 'closed') {
            throw new ApiException('Round is not closed yet', 409);
        }

        return [
            'winning_wish_id' => $result['closed_wish_id'] !== null ? (int)$result['closed_wish_id'] : null,
            'winning_wish_name' => $result['winning_wish_name'],
            'winning_score' => $result['winning_score'] !== null ? (int)$result['winning_score'] : null,
            'winning_created_by' => $result['winning_created_by'] !== null ? (int)$result['winning_created_by'] : null,
        ];
    }

    private function getOpenRound(string $familyId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, family_id, status, created_at
             FROM voting_rounds
             WHERE family_id = :family_id
               AND status = :status
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([
            ':family_id' => $familyId,
            ':status' => 'open',
        ]);

        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    private function getRoundClosureApprovalsCount(int $roundId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM voting_round_closure_approvals
             WHERE round_id = :round_id'
        );
        $stmt->execute([':round_id' => $roundId]);

        return (int)($stmt->fetch()['total'] ?? 0);
    }

    private function getMemberVotingBalance(int $memberId, int $score): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0) AS spent
             FROM wish_votes
             WHERE member_id = :member_id'
        );
        $stmt->execute([':member_id' => $memberId]);

        $spent = (int)($stmt->fetch()['spent'] ?? 0);
        return max(0, $score - $spent);
    }

    private function findRoundWinnerCandidate(string $familyId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, score, created_by, created_at
             FROM wishes
             WHERE family_id = :family_id
               AND is_active = 1
             ORDER BY score DESC, created_at ASC, id ASC
             LIMIT 1'
        );
        $stmt->execute([':family_id' => $familyId]);

        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
