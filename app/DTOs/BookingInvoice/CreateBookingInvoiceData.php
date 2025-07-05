<?php

namespace App\DTOs\BookingInvoice;

use App\DTOs\Shared\CreatedByData;
use App\DTOs\Shared\CustomerInfoData;
use App\DTOs\Shared\MoneyData;
use Carbon\Carbon;

class CreateBookingInvoiceData
{
    public function __construct(
        public CustomerInfoData $customer,
        public MoneyData $amount,
        public string $status,
        public bool $isWalkIn,
        public CreatedByData $createdBy,
        public Carbon $issuedAt,
        public ?Carbon $dueAt,
        public ?int $replacesInvoiceId = null,
    ) {}
}
