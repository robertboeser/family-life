<?php

declare(strict_types=1);

namespace FamilyLife\Backend\Http;

use FamilyLife\Backend\Support\ApiException;

final class Request
{
    public function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new ApiException('Invalid JSON body', 400);
        }

        return $decoded;
    }

    public function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) === 1) {
            return trim($matches[1]);
        }

        $tokenFromQuery = $_GET['token'] ?? null;
        if (is_string($tokenFromQuery) && $tokenFromQuery !== '') {
            return $tokenFromQuery;
        }

        return null;
    }
}
