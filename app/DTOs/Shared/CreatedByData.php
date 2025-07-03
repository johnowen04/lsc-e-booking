<?php

namespace App\DTOs\Shared;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreatedByData
{
    public function __construct(
        public ?string $type = null,
        public ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['created_by_type'] ?? null,
            id: $data['created_by_id'] ?? null,
        );
    }

    public static function fromModel(?Model $creator): self
    {
        if ($creator instanceof Customer) return CreatedByData::fromCustomer($creator);
        if ($creator instanceof User) return CreatedByData::fromUser($creator);

        return new self();
    }

    public static function fromUser(User $user): self
    {
        return new self(get_class($user), $user->id);
    }

    public static function fromCustomer(Customer $customer): self
    {
        return new self(get_class($customer), $customer->id);
    }
}
