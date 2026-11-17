<?php declare(strict_types=1);

namespace Rush\Manager\Command;

use Rush\Contract\CommandInterface;
use Rush\Contract\ExitCode;
use Rush\Contract\InputInterface;
use Rush\Contract\Option;
use Rush\Contract\OutputInterface;
use Rush\Contract\ToolInterface;
use Rush\Support\FileSystem;


final class InstallCommand implements CommandInterface
{
    /**
     * @param list<ToolInterface> $tools
     */
    public function __construct(
        private readonly string $projectRoot,
        private readonly array $tools,
    ) {
    }


    public function name(): string
    {
        return 'install';
    }

    public function description(): string
    {
        return 'Install the local .rush control layout';
    }

    public function usage(): string
    {
        return 'rush install';
    }

    /** @return list<Option> */
    public function options(): array
    {
        return [];
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        FileSystem::ensureDirectory(rtrim($this->projectRoot, '/') .'/.rush');

        foreach ($this->tools as $tool) {
            foreach ($tool->install($this->projectRoot) as $line) {
                $output->line($line);
            }
        }

        $output->line();
        $output->line('next:');
        $output->line('  rush operator --name="..." --email="..."');
        $output->line('  rush list');
        $output->line('  rush <tool> --help');

        return ExitCode::Success->value;
    }
}
