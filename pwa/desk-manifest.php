<?php
/**
 * Home-screen manifest for the back office. start_url is the back-office
 * folder only when that folder is the real one on this server.
 */
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: no-store');

$start = '/';
$name = isset($_GET['dir']) ? (string) $_GET['dir'] : '';
$root = dirname(__DIR__);
if (preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
    $dir = $root.DIRECTORY_SEPARATOR.$name;
    $real = realpath($dir);
    $rootReal = realpath($root);
    $inside = $real && $rootReal && strpos($real, $rootReal.DIRECTORY_SEPARATOR) === 0;
    if ($inside && is_file($real.DIRECTORY_SEPARATOR.'init.php') && is_dir($real.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'default')) {
        $start = '/'.$name.'/';
    }
}

echo json_encode(array(
    'id' => $start,
    'name' => 'Salisberg Desk',
    'short_name' => 'Desk',
    'description' => 'Salisberg Hotels back office.',
    'start_url' => $start,
    'scope' => '/',
    'display' => 'standalone',
    'background_color' => '#12352c',
    'theme_color' => '#12352c',
    'icons' => array(
        array('src' => '/pwa/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'),
        array('src' => '/pwa/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'),
        array('src' => '/pwa/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'),
    ),
), JSON_UNESCAPED_SLASHES);
