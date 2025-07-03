<?php

namespace App\DTOs\Shared;

use Illuminate\Database\Eloquent\Model;

class InvoiceReference
{
    public function __construct(
        public string $type,
        public int $id,
    ) {}

    public function resolveModel(): Model
    {
        return app($this->type)::findOrFail($this->id);
    }
}
