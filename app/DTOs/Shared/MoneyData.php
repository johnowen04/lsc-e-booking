<?php

namespace App\DTOs\Shared;

class MoneyData
{
    public function __construct(
        public float $total = 0.0,
        public float $paid = 0.0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            total: (float) $data['total'] ?? 0.0,
            paid: (float) ($data['paid'] ?? 0.0),
        );
    }
}
