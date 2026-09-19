<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$root = __DIR__;
$buildMeta = [];
$buildPath = $root . '/mahda-build.json';
if (is_file($buildPath)) {
    $decoded = json_decode((string)file_get_contents($buildPath), true);
    if (is_array($decoded)) {
        $buildMeta = $decoded;
    }
}
$out = [
    'ok' => true,
    'app' => 'mahda',
    'build' => is_string($buildMeta['buildId'] ?? null) ? $buildMeta['buildId'] : null,
    'migrationStage' => is_int($buildMeta['migrationStage'] ?? null) ? $buildMeta['migrationStage'] : null,
    'apiVersion' => is_int($buildMeta['apiVersion'] ?? null) ? $buildMeta['apiVersion'] : null,
    'architecture' => 'modular-php',
    'serverTime' => date(DATE_ATOM),
    'routes' => [
        'home' => '/',
        'contentBank' => '/content',
        'contentDetail' => '/content/item/{id}',
        'contentNew' => '/content/new',
        'contentChin' => '/contentchin',
        'showcase' => '/showcase',
        'profile' => '/profile',
        'atlas' => '/atlas',
        'library' => '/library',
        'search' => '/search',
        'notifications' => '/notifications',
    ],
    'modules' => ['Auth','Profile','Content','Library','Social','ContentChin','Atlas','Showcase','Media','System','Portal','Admin','Studio'],
    'frontend' => [
        'frontController' => is_file($root . '/index.php'),
        'runtime' => is_file($root . '/assets/mahda-runtime.js'),
        'staticHtmlPages' => count(glob($root . '/*/*.html') ?: []) + count(glob($root . '/*.html') ?: []),
    ],
];
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
