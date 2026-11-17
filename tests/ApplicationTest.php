<?php declare(strict_types=1);

use Rush\Contract\CommandInterface;
use Rush\Contract\ExitCode;
use Rush\Contract\InputInterface;
use Rush\Contract\Option;
use Rush\Contract\OutputInterface;
use Rush\Manager\Application;
use Rush\Support\FileSystem;
use Rush\Support\Operator;
use Rush\Support\OperatorFile;
use Tests\CliFixtures;
use Tests\Fixtures\EchoTool;
use Tests\RecordingCommand;


function recordingCommands(): array
{
    return [
        'demo' => new RecordingCommand('demo', [
            new Option('name', 'A name', true),
            new Option('flag', 'A flag'),
        ]),
        'demo status' => new RecordingCommand('demo status', [
            new Option('name', 'A name', true),
        ]),
    ];
}


test('parses options with equals and space', function (): void {
    $equals = CliFixtures::run(recordingCommands(), ['demo', '--name=Ana', '--flag']);
    $space = CliFixtures::run(recordingCommands(), ['demo', '--name', 'Ana', '--flag']);

    expect($equals['code'])->toBe(0)
        ->and($equals['stdout'])->toContain('name=Ana')
        ->and($equals['stdout'])->toContain('flag=1')
        ->and($space['stdout'])->toBe($equals['stdout']);
});


test('resolves the longest command prefix', function (): void {
    $result = CliFixtures::run(recordingCommands(), ['demo', 'status', '--name=Ana']);

    expect($result['stdout'])->toContain('command=demo status')
        ->and($result['stdout'])->toContain('name=Ana');
});


test('collects positional arguments and stops option parsing after --', function (): void {
    $result = CliFixtures::run(recordingCommands(), ['demo', '--flag', '--', '--name', 'Ana']);

    expect($result['stdout'])->toContain('flag=1')
        ->and($result['stdout'])->toContain('args=--name,Ana');
});


test('shows global help and rejects command-level help', function (): void {
    $global = CliFixtures::run(recordingCommands(), []);
    $command = CliFixtures::run(recordingCommands(), ['demo', 'status', '--help']);

    expect($global['stdout'])->toContain('Tools:')
        ->and($global['stdout'])->toContain('demo')
        ->and($command['code'])->toBe(ExitCode::Invalid->value)
        ->and($command['stderr'])->toContain('unknown option');
});


test('shows the version', function (): void {
    $result = CliFixtures::run(recordingCommands(), ['--version']);

    expect($result['stdout'])->toContain('1.2.3');
});


test('rejects unknown commands and options', function (): void {
    $command = CliFixtures::run(recordingCommands(), ['nope']);
    $option = CliFixtures::run(recordingCommands(), ['demo', '--nope']);
    $missing = CliFixtures::run(recordingCommands(), ['demo', '--name']);

    expect($command['code'])->toBe(ExitCode::Invalid->value)
        ->and($command['stderr'])->toContain('unknown command')
        ->and($option['code'])->toBe(ExitCode::Invalid->value)
        ->and($option['stderr'])->toContain('unknown option')
        ->and($missing['code'])->toBe(ExitCode::Invalid->value)
        ->and($missing['stderr'])->toContain('requires a value');
});


test('returns the command exit code', function (): void {
    $commands = ['demo' => new RecordingCommand('demo', [], ExitCode::CheckFailed->value)];
    $result = CliFixtures::run($commands, ['demo']);

    expect($result['code'])->toBe(ExitCode::CheckFailed->value);
});


test('returns failure when a command throws', function (): void {
    $commands = [
        'boom' => new class implements CommandInterface {
            public function name(): string
            {
                return 'boom';
            }

            public function description(): string
            {
                return 'boom';
            }

            public function usage(): string
            {
                return 'rush boom';
            }

            public function options(): array
            {
                return [];
            }

            public function run(InputInterface $input, OutputInterface $output): int
            {
                throw new RuntimeException('boom');
            }
        },
    ];

    $result = CliFixtures::run($commands, ['boom']);

    expect($result['code'])->toBe(ExitCode::Failure->value)
        ->and($result['stderr'])->toContain('boom');
});


test('shows help for a command group', function (): void {
    $commands = ['demo status' => new RecordingCommand('demo status', [new Option('name', 'A name', true)])];
    $result = CliFixtures::run($commands, ['demo']);

    expect($result['stdout'])->toContain('status')
        ->and($result['stdout'])->toContain('Usage:')
        ->and($result['stdout'])->toContain('--name=<value>')
        ->and($result['stdout'])->toContain('recording command');
});


test('help and version are imperative and must be alone', function (): void {
    $help = CliFixtures::run(recordingCommands(), ['--help', 'demo']);
    $version = CliFixtures::run(recordingCommands(), ['--version', 'demo']);
    $group = CliFixtures::run(recordingCommands(), ['demo', '--version']);

    expect($help['code'])->toBe(ExitCode::Invalid->value)
        ->and($help['stderr'])->toContain('--help must be used alone')
        ->and($version['code'])->toBe(ExitCode::Invalid->value)
        ->and($version['stderr'])->toContain('--version must be used alone')
        ->and($group['stdout'])->toContain('demo 1.2.3');
});


test('requires an operator for tool commands', function (): void {
    $root = makeProject([]);

    $stdout = fopen('php://memory', 'w+');
    $stderr = fopen('php://memory', 'w+');

    if ($stdout === false || $stderr === false) {
        throw new RuntimeException('could not open test streams');
    }

    $application = new Application($root, stdout: $stdout, stderr: $stderr);
    $application->attach(new EchoTool());

    $code = $application->run(['echo', 'hello']);

    rewind($stderr);

    expect($code)->toBe(ExitCode::Failure->value)
        ->and(stream_get_contents($stderr))->toContain('operator not configured');

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


test('prints the package version', function (): void {
    $root = makeProject([]);

    $stdout = fopen('php://memory', 'w+');
    $stderr = fopen('php://memory', 'w+');

    if ($stdout === false || $stderr === false) {
        throw new RuntimeException('could not open test streams');
    }

    $application = new Application($root, stdout: $stdout, stderr: $stderr);
    $code = $application->run(['--version']);

    rewind($stdout);

    expect($code)->toBe(ExitCode::Success->value)
        ->and(stream_get_contents($stdout))->toContain('rush 0.1.0');

    FileSystem::removeDir($root);
});


test('runs a tool command after operator configuration', function (): void {
    $root = makeProject([]);
    OperatorFile::save($root, new Operator('Developer', 'developer@example.com'));

    $stdout = fopen('php://memory', 'w+');
    $stderr = fopen('php://memory', 'w+');

    if ($stdout === false || $stderr === false) {
        throw new RuntimeException('could not open test streams');
    }

    $application = new Application($root, stdout: $stdout, stderr: $stderr);
    $application->attach(new EchoTool());

    $code = $application->run(['echo', 'hello']);

    rewind($stdout);

    expect($code)->toBe(ExitCode::Success->value)
        ->and(stream_get_contents($stdout))->toContain('hello');

    FileSystem::removeDir($root);
});
