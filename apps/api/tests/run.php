<?php
declare(strict_types=1);

// npm run test:api -> corre todas las pruebas de tests/unit sin instalar librerías.
// Cada archivo *Test.php regresa un arreglo ['nombre de la prueba' => function () { ... }].

require dirname(__DIR__) . '/src/bootstrap.php';

function check(bool $condition, string $message = 'La condición no se cumplió'): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function same(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        $detail = 'Se esperaba ' . var_export($expected, true) . ' y llegó ' . var_export($actual, true);
        throw new RuntimeException(trim($message . ' ' . $detail));
    }
}

function throwsApi(callable $fn, int $status): void
{
    try {
        $fn();
    } catch (ReFood\ApiException $e) {
        same($status, $e->httpStatus, 'Código HTTP distinto.');
        return;
    }
    throw new RuntimeException("Se esperaba un error {$status} y no hubo error");
}

$passed = 0;
$failed = 0;
$files = glob(__DIR__ . '/unit/*Test.php') ?: [];
sort($files);
foreach ($files as $file) {
    echo PHP_EOL . basename($file, '.php') . PHP_EOL;
    $tests = require $file;
    foreach ($tests as $name => $test) {
        try {
            $test();
            $passed++;
            echo "  [OK]    {$name}" . PHP_EOL;
        } catch (Throwable $e) {
            $failed++;
            echo "  [FALLA] {$name}: " . $e->getMessage() . PHP_EOL;
        }
    }
}
echo PHP_EOL . "Pruebas aprobadas: {$passed}  |  Fallidas: {$failed}" . PHP_EOL;
exit($failed === 0 && $passed > 0 ? 0 : 1);
