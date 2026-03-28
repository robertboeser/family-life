<?php

declare(strict_types=1);

namespace FamilyLife\Backend\Support;

final class Clock
{
    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
