<?php declare(strict_types=1);

namespace Tests;

use RuntimeException;
use Rush\Contract\CommandInterface;
use Rush\Contract\InputInterface;
use Rush\Contract\Option;
use Rush\Contract\OutputInterface;
use Rush\Contract\ToolInterface;
use Rush\Manager\Application;
use Rush\Support\FileSystem;
use Rush\Support\Operator;
use Rush\Support\OperatorFile;


final class RecordingCommand implements CommandInterface
{
    /** @param list<Option> $options */
    public function __construct(
        protected string $commandName,
        protected array $options = [],
        protected int $exitCode = 0,
    ) {
    }


    public function name(): string
    {
        return $this->commandName;
    }

    public function description(): string
    {
        return 'recording command';
    }

    public function usage(): string
    {
        return 'rush '. $this->commandName;
    }

    /** @return list<Option> */
    public function options(): array
    {
        return $this->options;
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $output->line('command='. $this->commandName);
        $output->line('name='. ($input->string('name') ?? ''));
        $output->line('flag='. ($input->bool('flag') ? '1' : '0'));
        $output->line('args='. implode(',', $input->arguments()));

        return $this->exitCode;
    }
}


final class CliFixtures
{
    /**
     * @param array<string, CommandInterface> $commands
     * @param list<string> $argv
     * @return array{code: int, stdout: string, stderr: string}
     */
    public static function run(array $commands, array $argv): array
    {
        $root = sys_get_temp_dir() .'/rush-cli-'. bin2hex(random_bytes(6));
        mkdir($root, 0700, true);
        OperatorFile::save($root, new Operator('Test', 'test@example.com'));

        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');

        if ($stdout === false || $stderr === false) {
            throw new RuntimeException('could not open test streams');
        }

        $tool = new class ($commands) implements ToolInterface {
            /** @param array<string, CommandInterface> $commands */
            public function __construct(private readonly array $commands)
            {
            }

            public function key(): string
            {
                return 'demo';
            }

            public function name(): string
            {
                return 'Demo';
            }

            public function description(): string
            {
                return 'Demo tool';
            }

            public function version(): string
            {
                return '1.2.3';
            }

            /** @return array<string, CommandInterface> */
            public function commands(string $projectRoot): array
            {
                return $this->commands;
            }

            /** @return list<string> */
            public function install(string $projectRoot): array
            {
                return [];
            }
        };

        $application = new Application($root, '1.2.3', $stdout, $stderr);
        $application->attach($tool);

        $code = $application->run($argv);

        rewind($stdout);
        rewind($stderr);

        $result = [
            'code' => $code,
            'stdout' => (string) stream_get_contents($stdout),
            'stderr' => (string) stream_get_contents($stderr),
        ];

        FileSystem::removeDir($root);

        return $result;
    }
}
