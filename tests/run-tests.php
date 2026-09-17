<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$tests = [
    'tests/unit/StructureTest.php',
    'tests/unit/EncryptionServiceTest.php',
    'tests/unit/ComprobanteDTOTest.php',
    'tests/unit/ResponseHelperTest.php',
    'tests/unit/JwtServiceTest.php',
    'tests/unit/ApiAuthPolicyTest.php',
    'tests/unit/EmpresaAccessPolicyTest.php',
    'tests/unit/MultiEmpresaIsolationTest.php',
    'tests/unit/SvgSanitizerTest.php',
    'tests/unit/SunatBetaStatusTest.php',
    'tests/unit/SunatEnvironmentTest.php',
    'tests/unit/ProjectPathTest.php',
    'tests/security/SecurityCheck.php',
    'tests/integration/ConcurrenciaTest.php',
];

$failed = 0;
$skipped = 0;

foreach ($tests as $relativePath) {
    echo "\n--- {$relativePath} ---\n";
    $process = proc_open(
        [PHP_BINARY, $root . '/' . $relativePath],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root,
        null,
        ['bypass_shell' => true]
    );

    if (!is_resource($process)) {
        echo "[FAIL] No se pudo iniciar el test.\n";
        $failed++;
        continue;
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    echo $stdout;
    if ($stderr !== '') echo $stderr;

    if ($exitCode === 2) {
        $skipped++;
    } elseif ($exitCode !== 0) {
        $failed++;
    }
}

echo "\n=== Suite: {$failed} failed, {$skipped} skipped ===\n";
exit($failed > 0 ? 1 : 0);
