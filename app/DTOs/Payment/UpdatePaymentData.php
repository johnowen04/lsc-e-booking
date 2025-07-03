<?php

namespace App\DTOs\Payment;

use App\DTOs\Shared\CreatedByData;
use App\DTOs\Shared\MoneyData;
use App\DTOs\Shared\InvoiceReference;
use Carbon\Carbon;

class UpdatePaymentData
{
    public function __construct(
        public string $orderId,
        public MoneyData $amounts,
        public string $method,
        public string $status,
        public InvoiceReference $invoice,
        public CreatedByData $createdBy,
        public ?string $referenceCode = null,
        public ?string $providerName = null,
        public ?string $notes = null,
        public ?Carbon $paidAt = null,
        public ?Carbon $expiresAt = null,
    ) {}
}
