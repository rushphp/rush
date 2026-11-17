<?php declare(strict_types=1);

use Rush\Contract\ExitCode;
use Rush\Manager\Application;
use Rush\Support\FileSystem;
use Rush\Support\Operator;
use Rush\Support\OperatorFile;
use Tests\Fixtures\EchoTool;

function memoryStream(): mixed
{
    $stream = fopen('php://memory', 'w+');

    if ($stream === false) {
        throw new RuntimeException('could not open memory stream');
    }

    return $stream;
}

test('runs a tool command after operator configuration', function (): void {
    $root = makeProject([]);
    OperatorFile::save($root, new Operator('Developer', 'developer@example.com'));

    $stdout = memoryStream();
    $stderr = memoryStream();
    $application = new Application($root, stdout: $stdout, stderr: $stderr);
    $application->attach(new EchoTool());

    $code = $application->run(['echo', 'hello']);

    rewind($stdout);

    expect($code)->toBe(ExitCode::Success->value)
        ->and(stream_get_contents($stdout))->toContain('hello');

    FileSystem::removeDir($root);
});

test('requires an operator for tool commands', function (): void {
    $root = makeProject([]);

    $stdout = memoryStream();
    $stderr = memoryStream();
    $application = new Application($root, stdout: $stdout, stderr: $stderr);
    $application->attach(new EchoTool());

    $code = $application->run(['echo', 'hello']);

    rewind($stderr);

    expect($code)->toBe(ExitCode::Failure->value)
        ->and(stream_get_contents($stderr))->toContain('operator not configured');

    FileSystem::removeDir($root);
});

test('prints the rush version', function (): void {
    $root = makeProject([]);

    $stdout = memoryStream();
    $stderr = memoryStream();
    $application = new Application($root, stdout: $stdout, stderr: $stderr);

    $code = $application->run(['--version']);

    rewind($stdout);

    expect($code)->toBe(ExitCode::Success->value)
        ->and(stream_get_contents($stdout))->toContain('rush 1.0.0');

    FileSystem::removeDir($root);
});

test('exports config from tools that provide it', function (): void {
    $root = makeProject([]);

    $application = new Application($root);
    $application->attach(new EchoTool());

    expect($application->exportConfig())->toBe([
        'echo' => ['greeting' => 'hello'],
    ]);

    FileSystem::removeDir($root);
});
