<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;

class MonthlySummaryService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function computeTotalExpenditure(User $user, int $year, int $month): float
    {
        // TODO: compute expenses total for year-month for a given user
        $criteria = [
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month,
        ];

        return $this->expenses->sumAmounts($criteria);
    }

    public function computePerCategoryTotals(User $user, int $year, int $month): array
    {
        // TODO: compute totals for year-month for a given user
        $criteria = [
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month,
        ];

        $totals = $this->expenses->sumAmountsByCategory($criteria);

        $totalExpenditure = $this->computeTotalExpenditure($user, $year, $month);

        $result = [];
        foreach ($totals as $category => $value) {
            $percentage = $totalExpenditure > 0 ? ($value / $totalExpenditure) * 100 : 0;
            $result[$category] = [
                'value' => $value,
                'percentage' => $percentage,
            ];
        }

        return $result;
    }

    public function computePerCategoryAverages(User $user, int $year, int $month): array
    {
        // TODO: compute averages for year-month for a given user
        $criteria = [
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month,
        ];

        $averages = $this->expenses->averageAmountsByCategory($criteria);

        $result = [];
        foreach ($averages as $category => $avgValue) {
            $result[$category] = ['value' => $avgValue];
        }

        return $result;
    }
}
