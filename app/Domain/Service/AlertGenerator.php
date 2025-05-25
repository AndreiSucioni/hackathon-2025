<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;

class AlertGenerator
{
    private array $categoryBudgets;

    public function __construct(
        private readonly ExpenseRepositoryInterface $expenseRepository, 
        array $categoryBudgets
    ) {
         $this->categoryBudgets = $categoryBudgets;
    }
    // TODO: refactor the array below and make categories and their budgets configurable in .env
    // Hint: store them as JSON encoded in .env variable, inject them manually in a dedicated service,
    // then inject and use use that service wherever you need category/budgets information.
    /* private array $categoryBudgets = [
        'Groceries' => 300.00,
        'Utilities' => 200.00,
        'Transport' => 500.00,
        // ...
    ]; */

    public function generate(User $user, int $year, int $month): array
    {
        // TODO: implement this to generate alerts for overspending by category
        $alerts = [];

        foreach ($this->categoryBudgets as $category => $budget) {
            $criteria = [
                'user_id' => $user->id,
                'year' => $year,
                'month' => $month,
                'category' => $category,
            ];

            $spentByCategory = $this->expenseRepository->sumAmountsByCategory($criteria);
            $spent = $spentByCategory[$category] ?? 0.0;

            if ($spent > $budget) {
                $alerts[] = sprintf(
                    "⚠ %s budget exceeded by €%.2f",
                    $category,
                    $spent - $budget
                );
            }
        }

        return $alerts;
    }

}
