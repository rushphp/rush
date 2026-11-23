<?php declare(strict_types=1);

namespace Tests\Fixtures;

use Rush\Contract\CommandInterface;
use Rush\Contract\ToolInterface;


final class UnscopedTool implements ToolInterface
{
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
        return 'Unscoped commands';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    /** @return array<string, CommandInterface> */
    public function commands(string $projectRoot): array
    {
        return ['other' => new EchoCommand()];
    }

    /** @return list<string> */
    public function install(string $projectRoot): array
    {
        return [];
    }
}
