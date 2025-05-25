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

    $logPath = 'C:/Users/andre/Desktop/intership/hackathon-2025/var/expense_import.log';
    $logFile = fopen($logPath, 'a');
    /* if ($logFile === false) {
        throw new \RuntimeException("Nu se poate deschide fișierul de log $logPath");
    } */
    fwrite($logFile, "=== Starting CSV import at " . date('Y-m-d H:i:s') . " ===\n");

    $stream = $csvFile->getStream();
    $contents = $stream->getContents();

    $contents = str_replace(["\r\n", "\r"], "\n", $contents);
    $lines = explode("\n", $contents);

    $seenRows = [];
    //error_log("CSV contents:\n" . $contents);
    //error_log("Total lines in CSV: " . count($lines));
    foreach ($lines as $lineNumber => $line) {
         //error_log("Processing line " . ($lineNumber + 1) . ": $line");
            try {
                $line = trim($line);
                if ($line === '') {
                    //error_log("Skipped empty line " . ($lineNumber + 1));
                    fwrite($logFile, "Line " . ($lineNumber + 1) . ": Skipped empty line\n");
                    continue; 
                }
                
                $row = str_getcsv($line, ',');

                //$row = str_getcsv($line, "\t");
                $row = array_map('trim', $row);
                //error_log("Parsed CSV row: " . print_r($row, true));
             

                if (count($row) !== 4) {
                    //error_log("Invalid column count on line " . ($lineNumber + 1) . ": " . count($row));
                    throw new \Exception("Invalid column count (" . count($row) . ")");
                    //error_log("Row reparsed (tab): " . print_r($row, true));
                }

                [$dateStr, $amountStr, $description, $category] = $row;
                //error_log("Parsed values: date=$dateStr, amount=$amountStr, description=$description, category=$category");

                $dateFormats = ['Y-m-d H:i:s', 'n/j/Y H:i', 'Y-m-d'];

                $date = false;
                foreach ($dateFormats as $format) {
                    $date = \DateTimeImmutable::createFromFormat($format, $dateStr);
                    if ($date !== false) {
                        break;
                    }
                }
                if ($date === false) {
                    throw new \Exception("Invalid date format: '$dateStr'");
                }

                if (!is_numeric($amountStr)) {
                    throw new \Exception("Invalid amount: '$amountStr'");
                }

               

                if ($description === null) {
                    $description = '(no description)';
                }

                if (!in_array($category, $validCategories, true)) {
                    //throw new \Exception("Unknown category: '$category'");
                    fwrite($logFile, "Line " . ($lineNumber + 1) . ": Unknown category '$category'\n");
                    continue;
                }


                $amount = (float)$amountStr;
 //error_log("Calling create() with: amount=$amount, description=$description, date=$dateStr, category=$category");
                $key = $date->format('Y-m-d H:i:s') . '|' . ((int)round($amount * 100)) . '|' . $description . '|' . $category;
                if (isset($seenRows[$key])) {
                    //error_log("Skipped duplicate row in CSV on line " . ($lineNumber + 1));
                    fwrite($logFile, "Line " . ($lineNumber + 1) . ": Skipped duplicate row in CSV\n");
                    continue;
                }
                $seenRows[$key] = true;

                if ($this->expenses->exists([
                    'user_id' => $user->id,
                    'date' => $date->format('Y-m-d H:i:s'),
                    'description' => $description,
                    'amount_cents' => (int)round($amount * 100),
                    'category' => $category,
                ])) {
                    //error_log("Skipped duplicate expense in DB on line " . ($lineNumber + 1));
                    fwrite($logFile, "Line " . ($lineNumber + 1) . ": Skipped duplicate expense in DB\n");
                    continue;
                }
//error_log("Calling create() with: amount=$amount, description=$description, date=" . $date->format('Y-m-d H:i:s') . ", category=$category");

                $this->create($user, $amount, $description, $date, $category);
                //error_log("Line " . ($lineNumber + 1) . " imported successfully");
                 fwrite($logFile, "Line " . ($lineNumber + 1) . ": Imported successfully\n");
                $importedCount++;
  //error_log("Line $lineNumber imported successfully");
            } catch (\Throwable $e) {
                  //error_log("Skipped line " . ($lineNumber + 1) . ": " . $e->getMessage());
                   fwrite($logFile, "Line " . ($lineNumber + 1) . ": Skipped due to error: " . $e->getMessage() . "\n");
                continue;
            }
        }

        fwrite($logFile, "=== Finished import. Total imported: $importedCount ===\n\n");
        fclose($logFile);

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
