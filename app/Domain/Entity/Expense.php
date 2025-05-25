<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

final class Expense
{
    public function __construct(
        public ?int $id,
        public int $userId,
        public DateTimeImmutable $date,
        public string $category,
        public int $amountCents,
        public string $description,
    ) {}

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function update(int $amountCents, string $description, DateTimeImmutable $date, string $category): void {
        $this->amountCents = $amountCents;
        $this->description = $description;
        $this->date = $date;
        $this->category = $category;
    }
}
