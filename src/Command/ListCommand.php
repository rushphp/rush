<?php declare(strict_types=1);

namespace Rush\Manager\Command;

use Rush\Contract\CommandInterface;
use Rush\Contract\ExitCode;
use Rush\Contract\InputInterface;
use Rush\Contract\Option;
use Rush\Contract\OutputInterface;
use Rush\Contract\ToolManagerInterface;


final class ListCommand implements CommandInterface
{
    public function __construct(
        private readonly ToolManagerInterface $manager,
    ) {
    }


    public function name(): string
    {
        return 'list';
    }

    public function description(): string
    {
        return 'List attached tools';
    }

    public function usage(): string
    {
        return 'rush list';
    }

    /** @return list<Option> */
    public function options(): array
    {
        return [];
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $tools = $this->manager->tools();

        if ($tools === []) {
            $output->warning('no tools attached');

            return ExitCode::Success->value;
        }

        foreach ($tools as $key => $tool) {
            $output->line(str_pad($key, 12) . str_pad($tool->version(), 10) . $tool->description());
        }

        return ExitCode::Success->value;
    }
}
