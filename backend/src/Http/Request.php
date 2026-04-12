<?php

declare(strict_types=1);

namespace FamilyLife\Backend\Http;

use FamilyLife\Backend\Support\ApiException;

final class Request
{
    private function headerValue(string $name): string
    {
        $normalized = strtolower($name);

        $serverCandidates = [
            'HTTP_' . strtoupper(str_replace('-', '_', $name)),
            strtoupper(str_replace('-', '_', $name)),
            'REDIRECT_HTTP_' . strtoupper(str_replace('-', '_', $name)),
            'REDIRECT_' . strtoupper(str_replace('-', '_', $name)),
        ];

        foreach ($serverCandidates as $candidate) {
            $value = $_SERVER[$candidate] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $headerName => $value) {
                    if (strtolower((string)$headerName) === $normalized && is_string($value) && trim($value) !== '') {
                        return trim($value);
                    }
                }
            }
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (is_array($headers)) {
                foreach ($headers as $headerName => $value) {
                    if (strtolower((string)$headerName) === $normalized && is_string($value) && trim($value) !== '') {
                        return trim($value);
                    }
                }
            }
        }

        return '';
    }

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
        $header = $this->headerValue('Authorization');
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) === 1) {
            return trim($matches[1]);
        }

        $headerToken = $this->headerValue('X-Auth-Token');
        if ($headerToken !== '') {
            return $headerToken;
        }

        $tokenFromQuery = $_GET['token'] ?? null;
        if (is_string($tokenFromQuery) && $tokenFromQuery !== '') {
            return trim($tokenFromQuery);
        }

        return null;
    }
}
