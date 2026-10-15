<?php
declare(strict_types=1);
$config = require (is_file(__DIR__.'/config.php') ? __DIR__.'/config.php' : __DIR__.'/config.example.php');
date_default_timezone_set($config['timezone']);
require_once __DIR__.'/Database.php';
require_once __DIR__.'/Auth.php';
require_once __DIR__.'/Booking.php';
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly'=>true, 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite'=>'Lax']);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
    header('Cache-Control: no-store');
}
function e(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $page = 'home', array $query = []): string { return 'index.php?'.http_build_query(['page'=>$page] + $query); }
function redirect(string $page): never { header('Location: '.url($page)); exit; }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrfField(): string { return '<input type="hidden" name="csrf" value="'.e(csrf()).'">'; }
function post(string $key): string { return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : ''; }
function rawPost(string $key): string { return is_string($_POST[$key] ?? null) ? $_POST[$key] : ''; }
function dateRu(string $date): string { return (new DateTimeImmutable($date))->format('d.m.Y · H:i'); }
function statusLabel(string $status): string { return ['new'=>'Новая','scheduled'=>'Мероприятие назначено','completed'=>'Завершено'][$status] ?? $status; }
function paymentLabel(string $payment): string { return $payment === 'sbp' ? 'Перевод по СБП' : 'При очном посещении'; }
