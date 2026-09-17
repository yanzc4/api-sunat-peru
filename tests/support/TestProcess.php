<?php

declare(strict_types=1);

final class TestProcess
{
    /** @return array{output: string, exit_code: int} */
    public static function response(string $scenario): array
    {
        $script = __DIR__ . '/ResponseScenario.php';
        $command = escapeshellarg(PHP_BINARY) . ' ' .
            escapeshellarg($script) . ' ' . escapeshellarg($scenario);

        $lines = [];
        $exitCode = 0;
        exec($command, $lines, $exitCode);

        return [
            'output' => implode("\n", $lines),
            'exit_code' => $exitCode,
        ];
    }
}
