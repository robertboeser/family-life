<?php

declare(strict_types=1);

function acceptsGerman(array $server): bool
{
    $acceptLanguage = $server['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if ($acceptLanguage === '') {
        return false;
    }

    $parts = explode(',', $acceptLanguage);
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }

        [$langTag] = explode(';', $part, 2);
        $primary = strtolower(trim(explode('-', $langTag, 2)[0]));

        if ($primary === 'de') {
            return true;
        }
    }

    return false;
}

function uiLang(array $server): string
{
    return acceptsGerman($server) ? 'de' : 'en';
}

function translations(): array
{
    static $translations = null;

    if ($translations === null) {
        $translations = require __DIR__ . '/translations.php';
    }

    return $translations;
}

function tp(string $lang, string $page, string $key): string
{
    $all = translations();

    if (isset($all[$lang]['pages'][$page][$key])) {
        return $all[$lang]['pages'][$page][$key];
    }

    if (isset($all['en']['pages'][$page][$key])) {
        return $all['en']['pages'][$page][$key];
    }

    return $key;
}

function tm(string $lang, string $scope, string $key): string
{
    $all = translations();

    if (isset($all[$lang]['messages'][$scope][$key])) {
        return $all[$lang]['messages'][$scope][$key];
    }

    if (isset($all['en']['messages'][$scope][$key])) {
        return $all['en']['messages'][$scope][$key];
    }

    return $key;
}

function tj(string $lang, string $page): array
{
    $all = translations();

    $global = $all[$lang]['js']['global'] ?? $all['en']['js']['global'] ?? [];
    $pageTranslations = $all[$lang]['js'][$page] ?? $all['en']['js'][$page] ?? [];

    return array_merge($global, $pageTranslations);
}
