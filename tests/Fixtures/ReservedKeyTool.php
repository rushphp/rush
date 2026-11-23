<?php declare(strict_types=1);

namespace Tests\Fixtures;

use Rush\Contract\CommandInterface;
use Rush\Contract\ToolInterface;


final class ReservedKeyTool implements ToolInterface
{
    public function key(): string
    {
        return 'list';
    }

    public function name(): string
    {
        return 'List';
    }

    public function description(): string
    {
        return 'Reserved key';
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
