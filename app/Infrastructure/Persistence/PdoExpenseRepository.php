<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $query = 'SELECT * FROM expenses WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return $this->createExpenseFromData($data);
    }

    /* public function save(Expense $expense): void
    {
        // TODO: Implement save() method.
        $query = 'Insert INTO expenses (id, user_id, date, category, amount_cents, description) VALUES (:id, :user_id, :date, :category, :amount_cents, :description)';
        $statement = $this->pdo->prepare($query);
        $success = $statement->execute([
            'id' => $expense->id,
            'user_id' => $expense->userId,
            'date' => $expense->date->format('Y-m-d H:i:s'),
            'category' => $expense->category,
            'amount_cents' => $expense->amountCents,
            'description' => $expense->description,
        ]);

        if ($success) {
            error_log("Expense '{$expense->id}' saved successfully.");
        } else {
            $errorInfo = $statement->errorInfo();
            error_log("Failed to save expense '{$expense->id}': " . implode(', ', $errorInfo));
        }
    } */

    public function save(Expense $expense): void
    {
        // TODO: Implement save() method.
        $existsQuery = 'SELECT COUNT(*) FROM expenses WHERE id = :id';
        $stmt = $this->pdo->prepare($existsQuery);
        $stmt->execute(['id' => $expense->id]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            $query = 'UPDATE expenses 
                    SET user_id = :user_id, date = :date, category = :category, 
                        amount_cents = :amount_cents, description = :description 
                    WHERE id = :id';
        } else {
            $query = 'INSERT INTO expenses (id, user_id, date, category, amount_cents, description) 
                    VALUES (:id, :user_id, :date, :category, :amount_cents, :description)';
        }

        $statement = $this->pdo->prepare($query);
        $success = $statement->execute([
            'id' => $expense->id,
            'user_id' => $expense->userId,
            'date' => $expense->date->format('Y-m-d H:i:s'),
            'category' => $expense->category,
            'amount_cents' => $expense->amountCents,
            'description' => $expense->description,
        ]);

        if ($success) {
            error_log("Expense '{$expense->id}' saved successfully.");
        } else {
            $errorInfo = $statement->errorInfo();
            error_log("Failed to save expense '{$expense->id}': " . implode(', ', $errorInfo));
        }
    }


    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $statement->execute([$id]);
    }

    public function findBy(array $criteria, int $offset, int $limit): array
    {
         // TODO: Implement findBy() method.
        $query = "SELECT * FROM expenses WHERE 1=1";

        if (isset($criteria['year'])) {
            $query .= " AND strftime('%Y', date) = :year";
        }

        if (isset($criteria['month'])) {
            $query .= " AND strftime('%m', date) = :month";
        }

        if (isset($criteria['user_id'])) {
            $query .= " AND user_id = :userId";
        }

        $query .= " ORDER BY date DESC LIMIT :limit OFFSET :offset";

        $statement = $this->pdo->prepare($query);

        if (isset($criteria['year'])) {
            $statement->bindValue(':year', strval($criteria['year']), PDO::PARAM_STR);
        }

        if (isset($criteria['month'])) {
            $monthStr = str_pad((string)$criteria['month'], 2, '0', STR_PAD_LEFT);
            $statement->bindValue(':month', $monthStr, PDO::PARAM_STR);
        }

        if (isset($criteria['user_id'])) {
            $statement->bindValue(':userId', $criteria['user_id'], PDO::PARAM_INT);
        }

        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);

        $statement->execute();

        $expenses = [];
        while ($row = $statement->fetch()) {
            $expenses[] = $this->createExpenseFromData($row);
        }

        return $expenses;
    }

    public function countBy(array $criteria): int
    {
        // TODO: Implement countBy() method.
        $query = "SELECT COUNT(*) FROM expenses WHERE 1=1";

        if (isset($criteria['year'])) {
            $query .= " AND strftime('%Y', date) = :year";
        }

        if (isset($criteria['month'])) {
            $query .= " AND strftime('%m', date) = :month";
        }

        if (isset($criteria['user_id'])) {
            $query .= " AND user_id = :userId";
        }

        $statement = $this->pdo->prepare($query);

        if (isset($criteria['year'])) {
            $statement->bindValue(':year', strval($criteria['year']), PDO::PARAM_STR);
        }

        if (isset($criteria['month'])) {
            $monthStr = str_pad((string)$criteria['month'], 2, '0', STR_PAD_LEFT);
            $statement->bindValue(':month', $monthStr, PDO::PARAM_STR);
        }

        if (isset($criteria['user_id'])) {
            $statement->bindValue(':userId', $criteria['user_id'], PDO::PARAM_INT);
        }

        $statement->execute();

        return (int) $statement->fetchColumn();
        //return 0;
    }

    public function listExpenditureYears(User $user): array
    {
        // TODO: Implement listExpenditureYears() method.
        $query = "SELECT DISTINCT strftime('%Y', date) AS year FROM expenses WHERE user_id = :userId ORDER BY year DESC";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':userId', $user->id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_COLUMN);
        //return [];
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        // TODO: Implement sumAmountsByCategory() method.
        $query = "
            SELECT category, SUM(amount_cents) as total
            FROM expenses
            WHERE user_id = :userId
            AND strftime('%Y', date) = :year
            AND strftime('%m', date) = :month
            GROUP BY category
        ";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':userId', $criteria['user_id'], \PDO::PARAM_INT);
        $statement->bindValue(':year', sprintf('%04d', $criteria['year']), \PDO::PARAM_STR);
        $statement->bindValue(':month', sprintf('%02d', $criteria['month']), \PDO::PARAM_STR);
        $statement->execute();

        $results = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $results[$row['category']] = (float)$row['total'] / 100;
        }

        return $results;
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        // TODO: Implement averageAmountsByCategory() method.
        $query = "
            SELECT category, AVG(amount_cents) as avg_amount
            FROM expenses
            WHERE user_id = :userId
            AND strftime('%Y', date) = :year
            AND strftime('%m', date) = :month
            GROUP BY category
        ";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':userId', $criteria['user_id'], \PDO::PARAM_INT);
        $statement->bindValue(':year', sprintf('%04d', $criteria['year']), \PDO::PARAM_STR);
        $statement->bindValue(':month', sprintf('%02d', $criteria['month']), \PDO::PARAM_STR);
        $statement->execute();

        $results = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $results[$row['category']] = (float)$row['avg_amount'] / 100;
        }

        return $results;
    }

    public function sumAmounts(array $criteria): float
    {
        // TODO: Implement sumAmounts() method.
        $query = "SELECT SUM(amount_cents) as total FROM expenses WHERE user_id = :userId AND strftime('%Y', date) = :year AND strftime('%m', date) = :month";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':userId', $criteria['user_id'], \PDO::PARAM_INT);
        $statement->bindValue(':year', sprintf('%04d', $criteria['year']), \PDO::PARAM_STR);
        $statement->bindValue(':month', sprintf('%02d', $criteria['month']), \PDO::PARAM_STR);
        $statement->execute();
        $totalCents = $statement->fetchColumn();

        return $totalCents !== false ? $totalCents / 100 : 0.0;
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense(
            $data['id'],
            $data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            (int)$data['amount_cents'],
            $data['description'],
        );
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commitTransaction(): void
    {
        $this->pdo->commit();
    }

    public function rollBackTransaction(): void
    {
        $this->pdo->rollBack();
    }

    public function exists(array $criteria): bool
    {
        $query = 'SELECT COUNT(*) FROM expenses WHERE user_id = :user_id AND date = :date AND description = :description AND amount_cents = :amount_cents AND category = :category';

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            'user_id' => $criteria['user_id'],
            'date' => $criteria['date'],
            'description' => $criteria['description'],
            'amount_cents' => $criteria['amount_cents'],
            'category' => $criteria['category'],
        ]);
        return $stmt->fetchColumn() > 0;
    }
}
