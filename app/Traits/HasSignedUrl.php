<?php

namespace App\Traits;

use Illuminate\Support\Facades\URL;

trait HasSignedUrl
{
    public static function getSignedUrl(int $minutes = 30, array $parameters = []): string
    {
        return URL::temporarySignedRoute(
            static::getRouteName(panel: 'customer'),
            now()->addMinutes($minutes),
            $parameters,
        );
    }
}
