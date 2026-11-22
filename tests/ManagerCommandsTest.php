<?php declare(strict_types=1);

use Rush\Contract\CommandInterface;
use Rush\Contract\ToolInterface;
use Rush\Manager\Application;
use Rush\Manager\Command\InstallCommand;
use Rush\Manager\Command\ListCommand;
use Rush\Manager\Command\OperatorCommand;
use Rush\Manager\Input;
use Rush\Manager\Output;
use Rush\Support\FileSystem;
use Rush\Support\OperatorFile;


function managerRoot(): string
{
    $root = sys_get_temp_dir() .'/rush-manager-'. bin2hex(random_bytes(6));
    mkdir($root, 0700, true);

    return $root;
}


/** @return array{output: Output, stream: resource} */
function managerOutput(): array
{
    $stream = fopen('php://memory', 'w+');

    if ($stream === false) {
        throw new RuntimeException('could not open output stream');
    }

    return ['output' => new Output($stream), 'stream' => $stream];
}


/** @param resource $stream */
function managerRead($stream): string
{
    rewind($stream);

    return (string) stream_get_contents($stream);
}


/** @param list<string> $lines */
function fakeTool(string $key, array $lines = []): ToolInterface
{
    return new class ($key, $lines) implements ToolInterface {
        /**
         * @param list<string> $lines
         */
        public function __construct(
            private readonly string $key,
            private readonly array $lines,
        ) {
        }

        public function key(): string
        {
            return $this->key;
        }

        public function name(): string
        {
            return ucfirst($this->key);
        }

        public function description(): string
        {
            return 'Fake tool';
        }

        public function version(): string
        {
            return '1.0.0';
        }

        /** @return array<string, CommandInterface> */
        public function commands(string $projectRoot): array
        {
            return [];
        }

        /** @return list<string> */
        public function install(string $projectRoot): array
        {
            FileSystem::ensureDirectory(rtrim($projectRoot, '/') .'/.rush/'. $this->key);

            return $this->lines;
        }
    };
}


test('operator command configures and shows the identity', function (): void {
    $root = managerRoot();
    $command = new OperatorCommand($root);

    $write = managerOutput();
    $code = $command->run(Input::parse(['--name=Test Operator', '--email=test@example.com'], $command->options()), $write['output']);

    expect($code)->toBe(0)
        ->and(OperatorFile::read($root)?->name)->toBe('Test Operator')
        ->and(managerRead($write['stream']))->toContain('operator configured');

    $show = managerOutput();
    $command->run(Input::parse([], $command->options()), $show['output']);

    expect(managerRead($show['stream']))
        ->toContain('name: Test Operator')
        ->toContain('email: test@example.com');

    FileSystem::removeDir($root);
});


test('operator command validates partial and invalid input', function (): void {
    $root = managerRoot();
    $command = new OperatorCommand($root);
    $cases = [
        [['--name=Test'], '--name and --email are required together'],
        [['--email=test@example.com'], '--name and --email are required together'],
        [['--name= ', '--email=test@example.com'], '--name must be a non-empty string'],
        [['--name=Test', '--email=invalid'], '--email must be a valid email address'],
    ];

    foreach ($cases as [$tokens, $message]) {
        expect(fn () => $command->run(
            Input::parse($tokens, $command->options()),
            managerOutput()['output'],
        ))->toThrow(RuntimeException::class, $message);
    }

    FileSystem::removeDir($root);
});


test('list command reports tools and the empty state', function (): void {
    $root = managerRoot();
    $empty = new Application($root);
    $list = new ListCommand($empty);
    $emptyOutput = managerOutput();

    expect($list->run(Input::parse([], $list->options()), $emptyOutput['output']))->toBe(0)
        ->and(managerRead($emptyOutput['stream']))->toContain('no tools attached');

    $application = new Application($root);
    $application->attach(fakeTool('alpha'));
    $application->attach(fakeTool('beta'));
    $list = new ListCommand($application);
    $output = managerOutput();

    $list->run(Input::parse([], $list->options()), $output['output']);
    $text = managerRead($output['stream']);

    expect($text)->toContain('alpha')
        ->and($text)->toContain('beta')
        ->and($text)->toContain('1.0.0')
        ->and($text)->toContain('Fake tool');

    FileSystem::removeDir($root);
});


test('install works without an operator and calls each tool install', function (): void {
    $root = managerRoot();
    $command = new InstallCommand($root, [
        fakeTool('alpha', ['created .rush/alpha']),
        fakeTool('beta', ['created .rush/beta']),
    ]);
    $fixture = managerOutput();

    expect($command->run(Input::parse([], $command->options()), $fixture['output']))->toBe(0)
        ->and(OperatorFile::exists($root))->toBeFalse()
        ->and(is_dir($root .'/.rush/alpha'))->toBeTrue()
        ->and(is_dir($root .'/.rush/beta'))->toBeTrue()
        ->and(managerRead($fixture['stream']))->toContain('created .rush/alpha')
        ->and(managerRead($fixture['stream']))->toContain('created .rush/beta')
        ->and(managerRead($fixture['stream']))->toContain('rush operator');

    FileSystem::removeDir($root);
});
