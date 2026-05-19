<?php

namespace App\Support;

class StationToken
{
    public static function lookupHash(string $token): string
    {
        return hash('sha256', $token);
    }
}
