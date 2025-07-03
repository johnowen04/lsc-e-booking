<?php

namespace App\DTOs\Payment;

use App\DTOs\Shared\CreatedByData;
use App\DTOs\Shared\MoneyData;
use App\DTOs\Shared\InvoiceReference;

class CreatePaymentData
{
    public function __construct(
        public MoneyData $amounts,
        public string $method,
        public CreatedByData $createdBy,
        public InvoiceReference $invoice,
    ) {}
}
