<?php
declare(strict_types=1);

// npm run check:php -> revisa que tu PHP tenga lo que necesita la API.
$checks = [
    'PHP 8.2 o superior' => PHP_VERSION_ID >= 80200,
    'Extensión json' => extension_loaded('json'),
    'Extensión curl' => extension_loaded('curl') && function_exists('curl_init'),
    'cURL con HTTPS' => extension_loaded('curl') && !empty(curl_version()['ssl_version'] ?? ''),
    'Extensión mbstring' => extension_loaded('mbstring'),
];

echo 'ReFood Connect - requisitos de PHP' . PHP_EOL;
echo 'PHP ' . PHP_VERSION . ' | php.ini: ' . (php_ini_loaded_file() ?: '(ninguno)') . PHP_EOL;
foreach ($checks as $label => $ok) {
    echo ($ok ? '[OK]    ' : '[FALTA] ') . $label . PHP_EOL;
}
$ok = !in_array(false, $checks, true);
echo $ok ? 'Todo listo.' . PHP_EOL : 'Activa lo que falta en tu php.ini y vuelve a correr.' . PHP_EOL;
exit($ok ? 0 : 1);
