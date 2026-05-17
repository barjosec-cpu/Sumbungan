<?php

declare(strict_types=1);

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));

if (preg_match('#/(public|admin|dashboard|api)$#', $scriptDir)) {
    $webBase = dirname($scriptDir);
} else {
    $webBase = $scriptDir;
}

$webBase = rtrim($webBase === '/' || $webBase === '\\' ? '' : $webBase, '/');

if (!defined('WEB_BASE')) {
    define('WEB_BASE', $webBase);
}

function url(string $path): string
{
    $path = ltrim($path, '/');
    if (WEB_BASE === '') {
        return '/' . $path;
    }
    return WEB_BASE . '/' . $path;
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}
