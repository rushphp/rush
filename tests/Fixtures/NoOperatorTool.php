<?php declare(strict_types=1);

namespace Tests\Fixtures;

use Rush\Contract\CommandInterface;
use Rush\Contract\OperatorPolicyInterface;
use Rush\Contract\ToolInterface;


final class NoOperatorTool implements ToolInterface, OperatorPolicyInterface
{
    public function key(): string
    {
        return 'noop';
    }

    public function name(): string
    {
        return 'Noop';
    }

    public function description(): string
    {
        return 'Does not require an operator';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    /** @return array<string, CommandInterface> */
    public function commands(string $projectRoot): array
    {
        return ['noop hello' => new EchoCommand()];
    }

    /** @return list<string> */
    public function install(string $projectRoot): array
    {
        return [];
    }

    public function requiresOperator(): bool
    {
        return false;
    }
}
