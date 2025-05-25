<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;

class ExpenseService
{
    private ExpenseRepositoryInterface $expenses;

    public function __construct(ExpenseRepositoryInterface $expenses) 
    {
        $this->expenses = $expenses;
    }

    public function list(User $user, $year, $month, int $pageNumber, int $pageSize): array
    {
        // TODO: implement this and call from controller to obtain paginated list of expenses
        $criteria = ['user_id' => $user->id];

        if ($year !== null) {
            $criteria['year'] = $year;
        }
         if ($month !== null) {
            $criteria['month'] = $month;
        }

        $offset = ($pageNumber - 1) * $pageSize;

        $expenses = $this->expenses->findBy($criteria, $offset, $pageSize);
        $total = $this->expenses->countBy($criteria);

        return [$expenses, $total];
    }

    public function listExpenditureYears(User $user): array
    {
        return $this->expenses->listExpenditureYears($user);
    }

    public function create(User $user,float $amount,string $description,DateTimeImmutable $date,string $category,): Expense {

        
        // TODO: here is a code sample to start with
        $expense = new Expense(null, $user->id, $date, $category, (int)$amount, $description);

        // TODO: implement this to create a new expense entity, perform validation, and persist
        // if ($this->$expense->find($id) !== null) {
        //     throw new \RuntimeException('Expense already exists');
        // }

        $this->expenses->save($expense);
        return $expense;
    }

    public function update(Expense $expense,float $amount,string $description,DateTimeImmutable $date,string $category,): void {
        // TODO: implement this to update expense entity, perform validation, and persist
        $expense->update((int)$amount , $description, $date, $category);

        $this->expenses->save($expense); 
    }

    /* public function importCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        //return 0; // number of imported rows
        $this->expenses->beginTransaction();

        try {
            $rows = $this->parseCsv($csvFile); // corectam aici cu $csvFile, nu $csvFilePath

            foreach ($rows as $row) {
                $expense = $this->createExpenseFromRow($row, $user);

                if (!$this->expenses->exists([
                    'user_id' => $expense->userId,
                    'date' => $expense->date->format('Y-m-d H:i:s'),
                    'description' => $expense->description,
                    'amount_cents' => $expense->amountCents,
                    'category' => $expense->category,
                ])) {
                    $this->expenses->save($expense);
                }
            }

            $this->expenses->commitTransaction();

        } catch (\Exception $e) {
            $this->expenses->rollBackTransaction();
            throw $e;
        }

        // Optional: return number of imported rows
        return count($rows);
    }
 */

 public function importCsv(User $user, UploadedFileInterface $csvFile): int
{
    $validCategories = ['Groceries', 'Transport', 'Entertainment', 'Utilities'];
    $importedCount = 0;

    $stream = $csvFile->getStream();
    $contents = $stream->getContents();

    // Normalizează line endings
    $contents = str_replace(["\r\n", "\r"], "\n", $contents);
    $lines = explode("\n", $contents);
    //error_log("CSV contents:\n" . $contents);
    foreach ($lines as $lineNumber => $line) {
        try {
            $line = trim($line);
            if ($line === '') {
                continue; 
            }
            
            $row = str_getcsv($line, ',');

            //$row = str_getcsv($line, "\t");
            $row = array_map('trim', $row);

            if (count($row) !== 4) {
                throw new \Exception("Invalid column count (" . count($row) . ")");
            }

            [$dateStr, $amountStr, $description, $category] = $row;

            $date = \DateTimeImmutable::createFromFormat('n/j/Y H:i', $dateStr);
            if (!$date) {
                throw new \Exception("Invalid date format: '$dateStr'");
            }

            if (!is_numeric($amountStr)) {
                throw new \Exception("Invalid amount: '$amountStr'");
            }

            if (empty($description)) {
                throw new \Exception("Empty description");
            }

            if (!in_array($category, $validCategories, true)) {
                throw new \Exception("Unknown category: '$category'");
            }

            $amount = (float)$amountStr;
            $this->create($user, $amount, $description, $date, $category);
            $importedCount++;

        } catch (\Throwable $e) {
            error_log("Skipped line " . ($lineNumber + 1) . ": " . $e->getMessage());
            continue;
        }
    }

    return $importedCount;
}


    public function findId(int $id): ?Expense
    {
        return $this->expenses->find($id);
    }

    public function delete(int $id): void
    {
        $this->expenses->delete($id);
    }

    private function parseCsv(UploadedFileInterface $csvFile): array
    {
        $csvFilePath = $csvFile->getStream()->getMetadata('uri');
        $rows = [];

        $expectedColumns = ['date', 'amount_cents', 'description', 'category'];

        if (($handle = fopen($csvFilePath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, "\t")) !== false) {
                if (count($data) !== count($expectedColumns)) {
                    throw new \RuntimeException('CSV row does not have expected number of columns');
                }

                $rows[] = array_combine($expectedColumns, $data);
            }
            fclose($handle);
        }

        return $rows;
    }


    private function createExpenseFromRow(array $row, User $user): Expense
    {
        if (!isset($row['date']) || empty($row['date'])) {
            throw new \InvalidArgumentException('CSV row missing required "date" field.');
        }
        if (!isset($row['category']) || !isset($row['amount_cents']) || !isset($row['description'])) {
            throw new \InvalidArgumentException('CSV row missing required fields.');
        }

        return new Expense(
            null,
            $user->id,
            new DateTimeImmutable($row['date']),
            $row['category'],
            (int)$row['amount_cents'],
            $row['description']
        );
    }
}
