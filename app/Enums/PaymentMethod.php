<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case QRIS = 'qris';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::QRIS => 'QRIS',
        };
    }

    public static function toArray(): array
    {
        return [
            self::CASH->value => self::CASH->label(),
            self::QRIS->value => self::QRIS->label(),
        ];
    }
}