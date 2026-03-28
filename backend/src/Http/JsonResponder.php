<?php

declare(strict_types=1);

namespace FamilyLife\Backend\Http;

final class JsonResponder
{
    public function send(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
        exit;
    }
}
