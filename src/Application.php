<?php declare(strict_types=1);

namespace Rush\Manager;

use InvalidArgumentException;
use Rush\Contract\CommandInterface;
use Rush\Contract\ExitCode;
use Rush\Contract\Option;
use Rush\Contract\ProvidesConfigInterface;
use Rush\Contract\ToolInterface;
use Rush\Contract\ToolManagerInterface;
use Rush\Manager\Command\InstallCommand;
use Rush\Manager\Command\ListCommand;
use Rush\Manager\Command\OperatorCommand;
use Rush\Manager\Command\OperatorRequiredCommand;
use Throwable;


final class Application implements ToolManagerInterface
{
    public const VERSION = '1.0.0';

    /** @var array<string, ToolInterface> */
    private array $tools = [];

    /** @var array<string, CommandInterface> */
    private array $commands = [];

    /** @var array<string, array{description: string, version: string}> */
    private array $groups = [];

    private bool $booted = false;

    /** @var resource|null */
    private $stdout;

    /** @var resource|null */
    private $stderr;


    /**
     * @param resource|null $stdout
     * @param resource|null $stderr
     */
    public function __construct(
        private readonly string $projectRoot,
        private readonly string $version = self::VERSION,
        $stdout = null,
        $stderr = null,
    ) {
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }


    public function attach(ToolInterface $tool): void
    {
        $key = $tool->key();

        if (isset($this->tools[$key])) {
            throw new InvalidArgumentException("tool '{$key}' is already attached");
        }

        $this->tools[$key] = $tool;
        $this->booted = false;
    }


    /** @return array<string, ToolInterface> */
    public function tools(): array
    {
        return $this->tools;
    }


    /** @return array<string, mixed> */
    public function exportConfig(): array
    {
        $config = [];

        foreach ($this->tools as $key => $tool) {
            if ($tool instanceof ProvidesConfigInterface) {
                $config[$key] = $tool->config();
            }
        }

        return $config;
    }


    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->commands = [
            'install' => new InstallCommand($this->projectRoot, array_values($this->tools)),
            'list' => new ListCommand($this),
            'operator' => new OperatorCommand($this->projectRoot),
        ];

        foreach ($this->tools as $key => $tool) {
            foreach ($tool->commands($this->projectRoot) as $name => $command) {
                $this->commands[$name] = new OperatorRequiredCommand($command, $this->projectRoot);
            }

            $this->groups[$key] = [
                'description' => $tool->description(),
                'version' => $tool->version(),
            ];
        }

        ksort($this->commands);
        ksort($this->groups);
        $this->booted = true;
    }


    /** @param list<string> $argv */
    public function run(array $argv): int
    {
        $this->boot();

        $tokens = $argv;

        if ($tokens === []) {
            HelpRenderer::global($this->commands, self::globalOptions(), $this->output(), $this->version, $this->groups);

            return ExitCode::Success->value;
        }

        if ($tokens[0] === '--help' || $tokens[0] === '-h') {
            return $this->imperativeHelp($tokens, null);
        }

        if ($tokens[0] === '--version') {
            return $this->imperativeVersion($tokens, null);
        }

        if (isset($this->groups[$tokens[0]]) && in_array($tokens[1] ?? null, ['--help', '--version'], true)) {
            if ($tokens[1] === '--help') {
                return $this->imperativeHelp($tokens, $tokens[0]);
            }

            return $this->imperativeVersion($tokens, $tokens[0]);
        }

        [$command, $consumed] = $this->resolve($tokens);

        if ($command === null) {
            $output = $this->output();

            if (isset($this->groups[$tokens[0]]) && count($tokens) === 1) {
                $this->renderToolHelp($tokens[0], $output);

                return ExitCode::Success->value;
            }

            $output->error('unknown command: '. implode(' ', $tokens));
            HelpRenderer::global($this->commands, self::globalOptions(), $output, $this->version, $this->groups);

            return ExitCode::Invalid->value;
        }

        try {
            $input = Input::parse(array_slice($tokens, $consumed), $command->options());
        } catch (InvalidArgumentException $exception) {
            $this->output()->error($exception->getMessage());

            return ExitCode::Invalid->value;
        }

        try {
            return $command->run($input, $this->output());
        } catch (Throwable $exception) {
            $this->output()->error($exception->getMessage());

            return ExitCode::Failure->value;
        }
    }


    public function terminate(): void
    {
    }


    /** @param list<string> $tokens */
    private function imperativeHelp(array $tokens, ?string $group): int
    {
        $maximum = $group === null ? 1 : 2;

        if (count($tokens) !== $maximum) {
            $this->output()->error('--help must be used alone');

            return ExitCode::Invalid->value;
        }

        $output = $this->output();

        if ($group === null) {
            HelpRenderer::global($this->commands, self::globalOptions(), $output, $this->version, $this->groups);
        } else {
            $this->renderToolHelp($group, $output);
        }

        return ExitCode::Success->value;
    }


    /** @param list<string> $tokens */
    private function imperativeVersion(array $tokens, ?string $group): int
    {
        $maximum = $group === null ? 1 : 2;

        if (count($tokens) !== $maximum) {
            $this->output()->error('--version must be used alone');

            return ExitCode::Invalid->value;
        }

        if ($group === null) {
            $this->output()->line("rush {$this->version}");
        } else {
            $this->output()->line($group .' '. $this->groups[$group]['version']);
        }

        return ExitCode::Success->value;
    }


    private function renderToolHelp(string $group, Output $output): void
    {
        HelpRenderer::group(
            $group,
            $this->commands,
            self::globalOptions(),
            $output,
            $this->groups[$group]['description'],
            $this->groups[$group]['version'],
        );
    }


    /**
     * @param list<string> $tokens
     * @return array{0: ?CommandInterface, 1: int}
     */
    private function resolve(array $tokens): array
    {
        $matched = null;
        $consumed = 0;

        foreach ($this->commands as $name => $command) {
            $parts = explode(' ', $name);

            if (count($parts) <= $consumed) {
                continue;
            }

            if (array_slice($tokens, 0, count($parts)) !== $parts) {
                continue;
            }

            $matched = $command;
            $consumed = count($parts);
        }

        return [$matched, $consumed];
    }


    private function output(): Output
    {
        return new Output($this->stdout, $this->stderr);
    }


    /** @return list<Option> */
    public static function globalOptions(): array
    {
        return [
            new Option('help', 'Show this help'),
            new Option('version', 'Show the version'),
        ];
    }
}
