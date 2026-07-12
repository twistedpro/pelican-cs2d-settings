<?php

namespace JasonDyer\Cs2dSettings\Services;

class Cs2dServerDetector
{
    public static function isCs2dServer(mixed $server): bool
    {
        $egg = $server->egg ?? null;

        $haystack = strtolower(implode(' ', array_filter([
            (string) ($server->name ?? ''),
            (string) ($server->startup ?? ''),
            (string) ($server->image ?? ''),
            (string) ($egg->name ?? ''),
            (string) ($egg->author ?? ''),
            (string) ($egg->description ?? ''),
        ])));

        return str_contains($haystack, 'cs2d')
            || str_contains($haystack, 'counter-strike 2d')
            || str_contains($haystack, 'cs2d_dedicated');
    }
}
