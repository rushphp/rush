<?php declare(strict_types=1);

namespace Tests\Fixtures;

use Rush\Contract\CommandInterface;
use Rush\Contract\ProvidesConfigInterface;
use Rush\Contract\ToolInterface;

final class EchoTool implements ToolInterface, ProvidesConfigInterface
{
    public function key(): string
    {
        return 'echo';
    }

    public function name(): string
    {
        return 'Echo';
    }

    public function description(): string
    {
        return 'Echo tool';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    /** @return array<string, CommandInterface> */
    public function commands(string $projectRoot): array
    {
        return [
            'echo hello' => new EchoCommand(),
        ];
    }

    /** @return list<string> */
    public function install(string $projectRoot): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function config(): array
    {
        return ['greeting' => 'hello'];
    }
}
