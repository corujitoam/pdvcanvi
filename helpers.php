<?php
session_start();

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_check(string $token): void {
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(400);
        exit('CSRF inválido.');
    }
}

function h(?string $v): string {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function br_money_to_float(string $v): float {
    // aceita “10,50” ou “10.50”
    $v = str_replace(['.', ','], ['', '.'], $v); // remove milhares e troca vírgula por ponto
    return is_numeric($v) ? (float)$v : 0.0;
}
function money_fmt(float $v): string {
    return number_format($v, 2, ',', '.');
}
