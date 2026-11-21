<?php declare(strict_types=1);

namespace Tests\Fixtures;

use Rush\Contract\CommandInterface;
use Rush\Contract\ToolInterface;


final class DuplicateEchoTool implements ToolInterface
{
    public function key(): string
    {
        return 'echo';
    }

    public function name(): string
    {
        return 'Duplicate Echo';
    }

    public function description(): string
    {
        return 'Duplicate echo tool';
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
        return [];
    }
}
