<?php

namespace App\DTOs\Shared;

use App\Models\Customer;

class CustomerInfoData
{
    public function __construct(
        public ?int $id = null,
        public ?string $name = 'Guest',
        public ?string $email = 'user@company.com',
        public ?string $phone = null,
    ) {}

    public static function fromModel(Customer $customer): self
    {
        return new self(
            id: $customer->id,
            name: $customer->name,
            email: $customer->email,
            phone: $customer->phone,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['customer_id'] ?? null,
            name: $data['customer_name'],
            email: $data['customer_email'],
            phone: $data['customer_phone'] ?? null,
        );
    }
}
