<?php declare(strict_types=1);

namespace Tests\Fixtures;

use Rush\Contract\CommandInterface;
use Rush\Contract\ExitCode;
use Rush\Contract\InputInterface;
use Rush\Contract\Option;
use Rush\Contract\OutputInterface;
use Rush\Contract\ToolInterface;

final class EchoCommand implements CommandInterface
{
    public function name(): string
    {
        return 'echo hello';
    }

    public function description(): string
    {
        return 'Echoes hello';
    }

    public function usage(): string
    {
        return 'rush echo hello';
    }

    /** @return list<Option> */
    public function options(): array
    {
        return [];
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $output->line('hello');

        return ExitCode::Success->value;
    }
}
